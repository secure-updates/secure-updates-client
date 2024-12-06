<?php

/*
Plugin Name: Secure Updates Client
Description: Allows specifying a custom host for plugin updates.
Version: 3.1
Author: Secure Updates Foundation
Text Domain: secure-updates-client
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Secure_Updates_Client')) {
    class Secure_Updates_Client {
        /**
         * Custom host URL for plugin updates.
         *
         * @var string
         */
        private $custom_host;

        /**
         * Flag to determine if custom host is enabled.
         *
         * @var bool
         */
        private $custom_host_enabled;

        /**
         * Constructor to initialize the plugin.
         */
        public function __construct() {
            // Load plugin options
            $this->custom_host = get_option('custom_update_host_url', '');
            $this->custom_host_enabled = get_option('custom_update_host_enabled', false);

            // Setup hooks
            $this->setup_hooks();

            // Schedule daily sync
            $this->schedule_daily_sync();
        }

        /**
         * Setup WordPress hooks.
         */
        private function setup_hooks() {
            // Existing hooks
            add_action('admin_menu', [$this, 'setup_admin_menu']);
	        add_action('admin_menu', [$this, 'secure_updates_client_add_log_page']);
            add_action('admin_init', [$this, 'register_settings']);
            add_filter('pre_set_site_transient_update_plugins', [$this, 'override_plugin_update_url']);
            add_filter('plugins_api', [$this, 'modify_plugin_information'], 10, 3);
	        add_filter('plugin_row_meta',  [$this,  'secure_updates_client_plugin_indicator', 10, 2]);
            add_action('wp_ajax_test_custom_host', [$this, 'test_custom_host_connection']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

            // New hooks for plugin sync
            add_action('activated_plugin', [$this, 'plugin_activated']);
            add_action('deactivated_plugin', [$this, 'plugin_deactivated']);
            add_action('upgrader_process_complete', [$this, 'handle_plugin_changes'], 10, 2);
            add_action('secure_updates_client_daily_sync', [$this, 'send_installed_plugins_to_server']);

            // Activation/Deactivation hooks
            register_activation_hook(__FILE__, [$this, 'activate_plugin']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate_plugin']);
	        register_activation_hook(__FILE__, 'secure_updates_client_create_logs_table');
        }


	    function secure_updates_client_create_logs_table() {
		    global $wpdb;
		    $table_name = $wpdb->prefix . 'secure_update_client_logs';
		    $charset_collate = $wpdb->get_charset_collate();

		    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        timestamp datetime NOT NULL,
        action varchar(100) NOT NULL,
        result varchar(20) NOT NULL,
        message text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

		    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		    dbDelta($sql);
	    }


        /**
         * Set up the admin menu in WordPress dashboard.
         */
        public function setup_admin_menu() {
            add_options_page(
                __('Secure Updates Client Settings', 'secure-updates-client'),
                __('Secure Updates Client', 'secure-updates-client'),
                'manage_options',
                'secure-updates-client-settings',
                [$this, 'settings_page']
            );
        }

        /**
         * Register plugin settings.
         */
        public function register_settings() {
            // Existing settings
            register_setting('secure_update_client_options_group', 'custom_update_host_url', [
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => '',
            ]);
            register_setting('secure_update_client_options_group', 'custom_update_host_enabled', [
                'type' => 'boolean',
                'sanitize_callback' => [$this, 'sanitize_boolean'],
                'default' => false,
            ]);

            // New API key setting
            register_setting('secure_update_client_options_group', 'custom_update_host_api_key', [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]);
        }

        private function verify_plugin_compatibility($plugin_data) {
            global $wp_version;
            if (isset($plugin_data['requires'])) {
                return version_compare($wp_version, $plugin_data['requires'], '>=');
            }
            return true;
        }

        private function check_rate_limit() {
            $last_request = get_transient('secure_updates_last_request');
            if ($last_request) {
                if (time() - $last_request < 5) { // 5 second cooldown
                    return false;
                }
            }
            set_transient('secure_updates_last_request', time(), HOUR_IN_SECONDS);
            return true;
        }

        private function verify_plugin_checksum($file_path, $expected_checksum) {
            $actual_checksum = hash_file('sha256', $file_path);
            return hash_equals($expected_checksum, $actual_checksum);
        }

        /**
         * Settings page HTML.
         */
        public function settings_page() {
            ?>
            <div class="wrap">
                <h1><?php esc_html_e('Secure Updates Client Settings', 'secure-updates-client'); ?></h1>

                <form method="post" action="options.php">
                    <?php
                    settings_fields('secure_update_client_options_group');
                    do_settings_sections('secure_update_client_options_group');
                    ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label for="custom_update_host_url"><?php esc_html_e('Custom Host URL', 'secure-updates-client'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="custom_update_host_url" name="custom_update_host_url" value="<?php echo esc_attr($this->custom_host); ?>" class="regular-text" required>
                                <p class="description"><?php esc_html_e('Enter your custom host URL where plugin updates are hosted.', 'secure-updates-client'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label for="custom_update_host_api_key"><?php esc_html_e('API Key', 'secure-updates-client'); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="custom_update_host_api_key"
                                       name="custom_update_host_api_key"
                                       value="<?php echo esc_attr(get_option('custom_update_host_api_key', '')); ?>"
                                       class="regular-text"
                                       required>
                                <p class="description"><?php esc_html_e('Enter the API key provided by the secure updates server.', 'secure-updates-client'); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label><?php esc_html_e('Test Custom Host Connection', 'secure-updates-client'); ?></label>
                            </th>
                            <td>
                                <button type="button" id="test-custom-host" class="button-secondary">
                                    <?php esc_html_e('Test Connection', 'secure-updates-client'); ?>
                                </button>
                                <p id="test-result" class="description"></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label for="custom_host_enabled"><?php esc_html_e('Enable Custom Host', 'secure-updates-client'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="custom_host_enabled" name="custom_update_host_enabled" value="1" <?php checked(1, $this->custom_host_enabled); ?> />
                                <label for="custom_host_enabled"><?php esc_html_e('Enable updates from the custom host.', 'secure-updates-client'); ?></label>
                                <p class="description"><?php esc_html_e('Ensure that the connection test is successful before enabling.', 'secure-updates-client'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        /**
         * Schedule daily sync of plugins
         */
        public function schedule_daily_sync() {
            if (!wp_next_scheduled('secure_updates_client_daily_sync')) {
                wp_schedule_event(time(), 'daily', 'secure_updates_client_daily_sync');
            }
        }

        /**
         * Plugin activation handler
         */
        public function activate_plugin() {
            $this->schedule_daily_sync();
        }

        /**
         * Plugin deactivation handler
         */
        public function deactivate_plugin() {
            wp_clear_scheduled_hook('secure_updates_client_daily_sync');
        }

        /**
         * Handle plugin activation
         */
        public function plugin_activated($plugin) {
            if (!$this->has_custom_update($plugin)) {
                $slug = dirname($plugin);
                if ($slug !== '.') {
                    $this->send_installed_plugins_to_server([$slug]);
                }
            }
        }

        /**
         * Handle plugin deactivation
         */
        public function plugin_deactivated($plugin) {
            if (!$this->has_custom_update($plugin)) {
                $slug = dirname($plugin);
                if ($slug !== '.') {
                    // Optionally notify server of deactivation
                    $this->send_installed_plugins_to_server(); // Resend full list
                }
            }
        }

        /**
         * Handle plugin installation and updates
         */
        public function handle_plugin_changes($upgrader_object, $options) {
            if ($options['type'] === 'plugin' && !empty($options['plugins'])) {
                $plugin_slugs = [];
                foreach ($options['plugins'] as $plugin_file) {
                    if (!$this->has_custom_update($plugin_file)) {
                        $slug = dirname($plugin_file);
                        if ($slug !== '.') {
                            $plugin_slugs[] = $slug;
                        }
                    }
                }
                if (!empty($plugin_slugs)) {
                    $this->send_installed_plugins_to_server($plugin_slugs);
                }
            }
        }

        /**
         * Send installed plugins to server
         */
        public function send_installed_plugins_to_server($plugin_slugs = []) {
            if (!$this->custom_host_enabled || empty($this->custom_host)) {
                return;
            }

            $api_key = get_option('custom_update_host_api_key', '');
            if (empty($api_key)) {
                return;
            }

            if (empty($plugin_slugs)) {
                $all_plugins = get_plugins();
                $plugin_slugs = [];

                foreach ($all_plugins as $plugin_file => $plugin_data) {
                    if (!$this->has_custom_update($plugin_file)) {
                        $slug = dirname($plugin_file);
                        if ($slug !== '.') {
                            $plugin_slugs[] = $slug;
                        }
                    }
                }
            }

	        $response = wp_remote_post(
		        trailingslashit($this->custom_host) . 'wp-json/secure-updates-server/v1/plugins',
		        [
			        'headers' => [
				        'Content-Type'  => 'application/json',
				        'Authorization' => 'Bearer ' . $api_key,
				        'X-Client-Home' => home_url() // include the site's home_url
			        ],
			        'body'    => json_encode(['plugins' => $plugin_slugs]),
			        'timeout' => 15,
			        'sslverify' => true,
		        ]
	        );

	        if (is_wp_error($response)) {
                error_log('Secure Updates Client: Error sending plugins to server - ' . $response->get_error_message());
            }
        }

        /**
         * Sanitize boolean values.
         *
         * @param mixed $value The value to sanitize.
         * @return bool
         */
        public function sanitize_boolean($value) {
            return (bool) $value;
        }

        /**
         * Enqueue admin scripts.
         */
        public function enqueue_admin_scripts($hook) {
            if ($hook !== 'settings_page_secure-updates-client-settings') {
                return;
            }

            wp_enqueue_script(
                'secure-updates-client-admin-js',
                trailingslashit(plugin_dir_url(__FILE__)) . 'js/admin.js',
                ['jquery'],
                '1.0',
                true
            );

            wp_localize_script('secure-updates-client-admin-js', 'SecureUpdatesClient', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('test_custom_host_nonce'),
                'success_message' => __('Connection successful.', 'secure-updates-client'),
                'error_message' => __('Connection failed.', 'secure-updates-client'),
            ]);
        }



        /**
         * Override plugin update URLs to point to the custom host.
         *
         * @param stdClass $transient The update transient.
         * @return stdClass
         */
        public function override_plugin_update_url($transient) {
            if (empty($transient->response) || !$this->custom_host_enabled) {
                return $transient;
            }

            foreach ($transient->response as $plugin_file => $plugin_data) {
                // Skip plugins with custom update mechanisms
                if ($this->has_custom_update($plugin_file)) {
                    continue;
                }

                // Only override if the plugin is from WordPress.org
                if ($this->is_from_wordpress_org($plugin_data)) {
                    $plugin_slug = $plugin_data->slug;
                    $transient->response[$plugin_file]->package = trailingslashit($this->custom_host) . 'wp-json/secure-updates-server/v1/download/' . sanitize_title($plugin_slug);
                }
            }

            return $transient;
        }


        /**
         * Check if a plugin has a custom update mechanism.
         *
         * @param string $plugin_file The plugin file path.
         * @return bool
         */
        private function has_custom_update($plugin_file) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);

            // Check for a custom update URI in plugin headers
            if (!empty($plugin_data['UpdateURI'])) {
                return true;
            }

            // Additional checks can be added here (e.g., checking for known custom updater classes)

            return false;
        }

        /**
         * Determine if a plugin is from WordPress.org.
         *
         * @param stdClass $plugin_data The plugin data object.
         * @return bool
         */
        private function is_from_wordpress_org($plugin_data) {
            // WordPress.org plugins typically do not have a 'package' set
            return empty($plugin_data->package);
        }

        /**
         * Modify plugin information to include secure update messages.
         *
         * @param mixed    $result The plugin information.
         * @param string   $action The type of request.
         * @param stdClass $args   The query arguments.
         * @return mixed
         */
        public function modify_plugin_information($result, $action, $args) {
            if ($action !== 'plugin_information' || !$this->custom_host_enabled) {
                return $result;
            }

            // Check if the plugin is using the custom host
            if ($this->is_using_custom_host($args->slug)) {
                // Fetch checksum from the custom host's API
                $response = wp_remote_get(trailingslashit($this->custom_host) . 'wp-json/secure-updates-server/v1/download/' . sanitize_title($args->slug));
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $body = wp_remote_retrieve_body($response);
                    $data = json_decode($body, true);
                    if (isset($data['checksum'])) {
                        $checksum = sanitize_text_field($data['checksum']);
                        if ($checksum) {
                            // Add a secure update section
                            $result->sections['secure_update'] = '<span style="color: green;">' . esc_html__('Secure update available.', 'secure-updates-client') . '</span>';
                        }
                    }
                }
            }

            return $result;
        }

        /**
         * Check if a plugin is using the custom host for updates.
         *
         * @param string $slug The plugin slug.
         * @return bool
         */
        private function is_using_custom_host($slug) {
            // Logic to determine if the plugin update is served by the custom host
            $mirrored_plugins = get_option('secure_updates_plugins', []);
            return isset($mirrored_plugins[$slug]);
        }

        /**
         * Test the connection to the custom host URL via AJAX.
         */
        public function test_custom_host_connection() {
            // Verify nonce
            check_ajax_referer('test_custom_host_nonce', 'security');

            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(__('Insufficient permissions.', 'secure-updates-client'));
            }

            $custom_host_url = isset($_POST['custom_host_url']) ? esc_url_raw($_POST['custom_host_url']) : '';

            if (empty($custom_host_url)) {
                wp_send_json_error(__('Invalid URL provided.', 'secure-updates-client'));
            }

            // Make a GET request to the /connected endpoint
            $response = wp_remote_get(trailingslashit($custom_host_url) . 'wp-json/secure-updates-server/v1/connected', [
                'timeout' => 15,
                'sslverify' => true,
            ]);

            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                wp_send_json_error(__('Connection failed. Please check the URL.', 'secure-updates-client'));
            }

            wp_send_json_success(__('Connection successful.', 'secure-updates-client'));
        }

	    function secure_updates_client_log($action, $result, $message) {
		    global $wpdb;
		    $table_name = $wpdb->prefix . 'secure_update_client_logs';
		    $wpdb->insert(
			    $table_name,
			    [
				    'timestamp' => current_time('mysql'),
				    'action'    => sanitize_text_field($action),
				    'result'    => sanitize_text_field($result),
				    'message'   => sanitize_textarea_field($message)
			    ],
			    ['%s','%s','%s','%s']
		    );
	    }


	    function secure_updates_client_add_log_page() {
		    add_submenu_page(
			    'secure-updates-client-settings',
			    __('Secure Updates Client Logs', 'secure-updates-client'),
			    __('Logs', 'secure-updates-client'),
			    'manage_options',
			    'secure-updates-client-logs',
			    'secure_updates_client_logs_page'
		    );
	    }

	    function secure_updates_client_logs_page() {
		    global $wpdb;
		    $table_name = $wpdb->prefix . 'secure_update_client_logs';
		    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC LIMIT 50");
		    ?>
            <div class="wrap">
                <h1><?php esc_html_e('Secure Updates Client Logs', 'secure-updates-client'); ?></h1>
                <table class="widefat fixed striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e('Timestamp', 'secure-updates-client'); ?></th>
                        <th><?php esc_html_e('Action', 'secure-updates-client'); ?></th>
                        <th><?php esc_html_e('Result', 'secure-updates-client'); ?></th>
                        <th><?php esc_html_e('Message', 'secure-updates-client'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
				    <?php if ($logs): ?>
					    <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->timestamp); ?></td>
                                <td><?php echo esc_html($log->action); ?></td>
                                <td><?php echo esc_html($log->result); ?></td>
                                <td><?php echo esc_html($log->message); ?></td>
                            </tr>
					    <?php endforeach; ?>
				    <?php else: ?>
                        <tr><td colspan="4"><?php esc_html_e('No logs found.', 'secure-updates-client'); ?></td></tr>
				    <?php endif; ?>
                    </tbody>
                </table>
            </div>
		    <?php
	    }

	    function secure_updates_client_plugin_indicator($plugin_meta, $plugin_file) {
		    // Check if this plugin is handled by the secure update client
		    // For instance, if we know $plugin_file matches a plugin served by our custom host:
		    if (secure_updates_client_is_plugin_securely_updated($plugin_file)) {
			    $plugin_meta[] = '<span style="color: green; font-weight: bold;">' . __('Secure Updates Active', 'secure-updates-client') . '</span>';
		    }
		    return $plugin_meta;
	    }

	    function secure_updates_client_is_plugin_securely_updated($plugin_file) {
		    // Logic to determine if the plugin update is routed through the secure server
		    // For example, check if transient update URL is replaced or if plugin slug in a known mirrored list.
		    // Simplified example:
		    $mirrored_plugins = get_option('secure_updates_plugins', []);
		    $slug = dirname($plugin_file);
		    return isset($mirrored_plugins[$slug]);
	    }



    }

    // Initialize the plugin
    new Secure_Updates_Client();
}