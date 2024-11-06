# Secure Updates Client-Server Integration

## Overview

The Secure Updates Client plugin provides functionality to route WordPress plugin updates through a custom update server instead of WordPress.org. It interacts with the Secure Updates Server through several endpoints and maintains plugin synchronization.

## Configuration

### Client Settings
Configure in WordPress admin under Settings > Secure Updates Client:
- Custom Host URL: The Secure Updates Server URL
- API Key: Bearer token for authenticated requests
- Enable Custom Host: Toggle to activate custom update system

## Server Endpoint Interactions

### 1. Connection Test Endpoint
Tests basic connectivity with the server.

```php
// Client Implementation
public function test_custom_host_connection() {
    $response = wp_remote_get(
        trailingslashit($custom_host_url) . 'wp-json/secure-updates-server/v1/connected',
        [
            'timeout' => 15,
            'sslverify' => true,
        ]
    );
}
```
- **Endpoint**: `GET /wp-json/secure-updates-server/v1/connected`
- **Authentication**: None required
- **Used**: During connection testing and initial setup

### 2. Plugin Download Endpoint
Used to download plugin updates from the custom server.

```php
// Client Implementation in override_plugin_update_url()
$transient->response[$plugin_file]->package = trailingslashit($this->custom_host) 
    . 'wp-json/secure-updates-server/v1/download/' 
    . sanitize_title($plugin_slug);
```
- **Endpoint**: `GET /wp-json/secure-updates-server/v1/download/{slug}`
- **Authentication**: None required
- **Used**: When WordPress checks for and performs plugin updates

### 3. Plugin Information Endpoint
Fetches plugin metadata and verifies secure updates.

```php
// Client Implementation in modify_plugin_information()
$response = wp_remote_get(
    trailingslashit($this->custom_host) 
    . 'wp-json/secure-updates-server/v1/verify_file/' 
    . sanitize_title($args->slug)
);
```
- **Endpoint**: `GET /wp-json/secure-updates-server/v1/verify_file/{slug}`
- **Authentication**: None required
- **Used**: When viewing plugin details in WordPress admin

### 4. Plugin List Synchronization
Sends installed plugins to the server for mirroring.

```php
// Client Implementation
public function send_installed_plugins_to_server($plugin_slugs = []) {
    $response = wp_remote_post(
        trailingslashit($this->custom_host) . 'wp-json/secure-updates-server/v1/plugins',
        [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => json_encode(['plugins' => $plugin_slugs]),
            'timeout' => 15,
            'sslverify' => true,
        ]
    );
}
```
- **Endpoint**: `POST /wp-json/secure-updates-server/v1/plugins`
- **Authentication**: Bearer token required
- **Used**: During daily sync and plugin activation/deactivation

## Synchronization Events

The client syncs with the server on several events:

1. **Plugin Activation**:
```php
public function plugin_activated($plugin) {
    if (!$this->has_custom_update($plugin)) {
        $slug = dirname($plugin);
        if ($slug !== '.') {
            $this->send_installed_plugins_to_server([$slug]);
        }
    }
}
```

2. **Plugin Deactivation**:
```php
public function plugin_deactivated($plugin) {
    if (!$this->has_custom_update($plugin)) {
        $this->send_installed_plugins_to_server(); // Resend full list
    }
}
```

3. **Daily Scheduled Sync**:
```php
public function schedule_daily_sync() {
    if (!wp_next_scheduled('secure_updates_client_daily_sync')) {
        wp_schedule_event(time(), 'daily', 'secure_updates_client_daily_sync');
    }
}
```

## Update Process Override

The client intercepts WordPress's update process:

```php
public function override_plugin_update_url($transient) {
    foreach ($transient->response as $plugin_file => $plugin_data) {
        if ($this->is_from_wordpress_org($plugin_data)) {
            $plugin_slug = $plugin_data->slug;
            $transient->response[$plugin_file]->package = trailingslashit($this->custom_host) 
                . 'wp-json/secure-updates-server/v1/download/' 
                . sanitize_title($plugin_slug);
        }
    }
}
```

## Security Considerations

1. **API Authentication**:
    - Bearer token required for plugin list synchronization
    - Token stored securely using WordPress options API

2. **SSL Verification**:
    - All requests enforce SSL verification (`sslverify => true`)
    - Connections timeout after 15 seconds

3. **Input Sanitization**:
    - Plugin slugs are sanitized using `sanitize_title()`
    - URLs are validated using `esc_url_raw()`

4. **Capability Checks**:
    - Admin capabilities required for settings management
    - Nonce verification for AJAX requests

## Error Handling

The client implements error logging for various failure scenarios:

```php
if (is_wp_error($response)) {
    error_log('Secure Updates Client: Error sending plugins to server - ' 
        . $response->get_error_message());
}
```