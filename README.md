# Secure Updates Client

**Contributors:** Secure Updates Foundation  
**Tags:** plugin updates, custom host, WordPress  
**Requires at least:** 5.0  
**Tested up to:** 6.6.2  
**Stable tag:** 2.0  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

## In Active Development, Not Production-Ready

## Description

Secure Updates Client allows you to specify a custom host for receiving plugin updates. This is particularly useful if you're using a mirrored plugin repository or hosting plugins on your own server. The plugin intelligently overrides the update URLs for plugins hosted on WordPress.org while leaving custom-hosted plugins unaffected.

### Features

- **Custom Update URL:** Redirects plugin update checks to your specified custom host for supported plugins.
- **Selective Overriding:** Only overrides update URLs for plugins hosted on WordPress.org, ensuring custom-hosted plugins remain unaffected.
- **Secure Update Notifications:** Displays a secure update message indicating the integrity of available updates.
- **Admin Settings:** Easily configure your custom host URL via the WordPress admin dashboard.
- **** **API Key Integration:** Securely authenticate with your custom update host using an API key.
- **Connection Testing:** Test the connection to your custom host directly from the settings page.
- **Automated Plugin Sync:** Automatically synchronize installed plugins with your custom host on a daily basis.
- **Real-time Plugin Change Handling:** Notify the custom host immediately when plugins are activated, deactivated, installed, or updated.

## Installation

1. **Download the Plugin:**
   - Clone the repository or download the ZIP file.

2. **Install via WordPress Admin:**
   - Go to `Plugins` > `Add New` > `Upload Plugin`.
   - Upload the `secure-updates-client.zip` file.
   - Click `Install Now` and then `Activate`.

3. **Configure Custom Host URL:**
   - Navigate to `Settings` > `Secure Updates Client`.
   - Enter your custom host URL (e.g., `https://your-mirror-site.com`).
   - Enter your API key provided by the secure updates server.
   - Test the connection to ensure it's successful.
   - Enable updates from the custom host.
   - Save the settings.

## Usage

1. **Automatic Override:**
   - Once activated and configured, the plugin will automatically override the update URLs for all plugins hosted on WordPress.org.
   - Custom-hosted plugins (those with a `UpdateURI` in their headers or known custom update mechanisms) will remain unaffected.

2. **Secure Update Messages:**
   - When viewing plugin information, a secure update message (e.g., “Secure update available”) will be displayed if the mirrored plugin's checksum matches the WordPress.org version.

3. **API Key Configuration:**
   - Enter the API key provided by your secure updates server to authenticate update requests.

4. **Connection Testing:**
   - Use the "Test Connection" button in the settings page to verify that the custom host is reachable and properly configured.

5. **Automated Sync:**
   - The plugin schedules a daily synchronization of installed plugins to ensure the custom host has the latest information.

6. **Real-time Notifications:**
   - The plugin listens for plugin activations, deactivations, installations, and updates, and notifies the custom host accordingly.

## Frequently Asked Questions

### How does the plugin determine which plugins to override?
The plugin checks if a plugin has a `UpdateURI` defined in its headers. Plugins without a `UpdateURI` and presumed to be from WordPress.org will have their update URLs overridden to point to your custom host.

### Can I add exceptions for certain plugins?
Currently, the plugin automatically determines which plugins to override based on their update mechanisms. To add exceptions, you may need to extend the plugin's functionality by modifying the `is_from_wordpress_org` or `has_custom_update` methods.

### What should my custom host support?
Your custom host should provide a REST API endpoint compatible with the Secure Updates Client plugin to serve plugin ZIP files and their checksums. Ensure that the endpoint is secure and accessible to client sites.

### How do I obtain an API key for the custom host?
The API key should be generated and provided by the administrator of your secure updates server. Ensure it is kept confidential and securely stored.

## Changelog

### 2.0
- **API Key Integration:** Added support for API keys to securely authenticate with the custom update host.
- **Connection Testing:** Implemented an AJAX-based connection test feature in the admin settings to verify the custom host connectivity.
- **Automated Plugin Sync:** Introduced a daily scheduled task to synchronize installed plugins with the custom host automatically.
- **Real-time Plugin Change Handling:** Added hooks to notify the custom host immediately upon plugin activation, deactivation, installation, or updates.
- **Enhanced Admin Settings:** Improved the settings interface to include API key configuration and connection testing functionalities.
- **Secure Update Messages:** Enhanced the update messages to include checksum verification for added security.
- **Error Logging:** Implemented error logging for failed communications with the custom host to aid in troubleshooting.

### 1.3
- Updated plugin name to Secure Updates Client.
- Improved code consistency and renamed all text domains to reflect the new plugin name.
- Added enhanced secure update messages.

### 1.2
- Added secure update messages indicating checksum verification.
- Improved detection of custom-hosted plugins.

### 1.1
- Enhanced admin settings interface.
- Fixed issues with plugin slug validation.

### 1.0
- Initial release.
- Basic functionality to override plugin update URLs for WordPress.org hosted plugins.

## Upgrade Notice

### 2.0
Version 2.0 introduces significant enhancements including API key authentication, connection testing, automated plugin synchronization, and real-time plugin change handling. Please follow the updated configuration steps to ensure seamless integration with your custom update host. Verify that your API key is correctly entered and test the connection to your custom host after upgrading.

### 1.3
Updated plugin to Secure Updates Client for improved integration and naming consistency. Please verify that your custom update host URL is configured correctly after upgrading.

## License

This plugin is licensed under the GPLv2 or later. You can find the license [here](https://www.gnu.org/licenses/gpl-2.0.html).

---

### Summary of Updates in Version 2.0

- **API Key Integration:** Secure authentication with the custom update host using an API key.
- **Connection Testing:** Added functionality to test the connection to the custom host directly from the admin settings.
- **Automated Plugin Sync:** Scheduled daily synchronization of installed plugins with the custom host to ensure up-to-date information.
- **Real-time Plugin Change Handling:** Immediate notification to the custom host upon plugin activation, deactivation, installation, or updates.
- **Enhanced Admin Settings:** Improved interface to include new settings such as API key input and connection testing.
- **Secure Update Messages:** Enhanced security by verifying plugin checksums and displaying secure update notifications.
- **Error Logging:** Added error logging for better troubleshooting and reliability.

These enhancements make **Secure Updates Client** more robust, secure, and easier to manage, providing better integration with custom update hosts and ensuring the integrity of plugin updates.