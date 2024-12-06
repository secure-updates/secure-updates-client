# Secure Updates Client Changelog

## [3.2] - 2024-04-27
### Added
- Logging Functionality:
  - Database Table: Introduced a custom database table wp_secure_update_client_logs to store detailed logs of plugin-server communications.
  - Logging Mechanism: Implemented functions to record actions such as connection tests, plugin synchronization, and update checks with their respective results and messages.
  - Admin Logs Page: Added a new administrative page under Secure Updates Client > Logs to view recent communication logs in a structured table format.
- Visual Indicators:
  - Plugins Page Badges: Added "Secure Updates Active" badges next to plugins that are managed via the Secure Updates Server on the Plugins page.
  - Dashboard Widget: Introduced a dashboard widget displaying the connection status to the Secure Updates Server, the timestamp of the last successful sync, and a "Test Connection" button for quick diagnostics.
- Client Identification:
  - Home URL Transmission: Modified client requests to include the site's home_url in the X-Client-Home header, allowing the server to uniquely identify and log client sites.

### Changed
- Plugin Update Mechanism:
  - Enhanced the logic for overriding plugin update URLs to ensure accurate tracking and display of plugins managed via the Secure Updates Server.

##$ Fixed
- N/A

## [3.1] - 2024-11-05
### Added
- Version compatibility checking functionality
- Rate limiting for API requests
- Checksum verification before plugin installation
- Enhanced error handling and logging
- Additional security checks

### Changed
- Updated documentation structure
- Improved error messages
- Enhanced API response handling

### Fixed
- Various minor bug fixes
- Improved error handling
- Documentation updates

## [3.0] - 2024-10-15
### Added
- API Key Integration
- Connection Testing
- Automated Plugin Sync
- Real-time Plugin Change Handling
- Enhanced Admin Settings
- Secure Update Messages
- Error Logging

### Changed
- Complete rewrite of update handling system
- Improved security measures
- Enhanced user interface

## [2.0] - 2024-09-01
### Added
- API Key Integration
- Connection Testing functionality
- Automated Plugin Sync
- Real-time Plugin Change Handling
- Enhanced Admin Settings
- Secure Update Messages
- Error Logging

### Changed
- Improved settings interface
- Enhanced security features
- Better error handling

## [1.3] - 2024-08-15
### Changed
- Updated plugin name to Secure Updates Client
- Improved code consistency
- Renamed all text domains

### Added
- Enhanced secure update messages

## [1.2] - 2024-08-01
### Added
- Secure update messages with checksum verification
- Improved detection of custom-hosted plugins

## [1.1] - 2024-07-15
### Changed
- Enhanced admin settings interface
- Fixed issues with plugin slug validation

## [1.0] - 2024-07-01
### Added
- Initial release
- Basic functionality to override plugin update URLs