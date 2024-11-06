# API Integration Guide

## Overview
This guide details the integration points between the Secure Updates Client and Server.

## Authentication

### API Key Setup
```php
// Example API key configuration
define('SECURE_UPDATES_API_KEY', 'your-api-key-here');
```

### Request Headers
```php
$headers = [
    'Authorization' => 'Bearer ' . SECURE_UPDATES_API_KEY,
    'Content-Type' => 'application/json'
];
```

## Endpoints

### 1. Connection Test
```php
GET /wp-json/secure-updates-server/v1/connected
```
- No authentication required
- Tests basic connectivity
- Returns `{"status": "connected"}`

### 2. Plugin Download
```php
GET /wp-json/secure-updates-server/v1/download/{slug}
```
- Requires valid plugin slug
- Returns plugin ZIP file
- Includes checksum verification

### 3. Plugin Information
```php
GET /wp-json/secure-updates-server/v1/verify_file/{slug}
```
- Returns plugin metadata
- Includes security verification
- Contains update information

### 4. Plugin Sync
```php
POST /wp-json/secure-updates-server/v1/plugins
```
- Requires authentication
- Accepts plugin list
- Returns sync status

## Error Handling

### Response Codes
- 200: Success
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 429: Too Many Requests
- 500: Server Error

### Error Response Format
```json
{
    "code": "error_code",
    "message": "Human readable message",
    "data": {
        "status": 400
    }
}
```

## Rate Limiting

### Limits
- 60 requests per minute per IP
- 1000 requests per day per API key
- 5 failed attempts before temporary block

### Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1635959876
```

## Example Implementations

### Connection Test
```php
public function test_connection() {
    $response = wp_remote_get(
        $this->api_url . '/connected',
        [
            'timeout' => 15,
            'sslverify' => true
        ]
    );
    
    return !is_wp_error($response) && 
           wp_remote_retrieve_response_code($response) === 200;
}
```

### Plugin Download
```php
public function download_plugin($slug) {
    $response = wp_remote_get(
        $this->api_url . '/download/' . $slug,
        [
            'headers' => $this->get_headers(),
            'timeout' => 30,
            'sslverify' => true
        ]
    );
    
    if (is_wp_error($response)) {
        return $response;
    }
    
    return wp_remote_retrieve_body($response);
}
```

### Plugin Sync
```php
public function sync_plugins($plugins) {
    return wp_remote_post(
        $this->api_url . '/plugins',
        [
            'headers' => $this->get_headers(),
            'body' => json_encode(['plugins' => $plugins]),
            'timeout' => 15,
            'sslverify' => true
        ]
    );
}
```

## Testing

### Endpoint Testing
```bash
# Test connection
curl -I https://your-server.com/wp-json/secure-updates-server/v1/connected

# Test authentication
curl -H "Authorization: Bearer your-api-key" \
     https://your-server.com/wp-json/secure-updates-server/v1/plugins
```

### Integration Testing
```php
public function test_api_integration() {
    // Test connection
    $this->assertTrue($this->test_connection());
    
    // Test authentication
    $this->assertTrue($this->verify_credentials());
    
    // Test plugin sync
    $this->assertNotWPError($this->sync_plugins(['test-plugin']));
}
```

## Debugging

### Enable Debug Mode
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Log API Requests
```php
public function log_api_request($response, $url) {
    if (is_wp_error($response)) {
        error_log(sprintf(
            'API Request Failed: %s - %s',
            $url,
            $response->get_error_message()
        ));
    }
}
```

## Security Considerations

1. Always use HTTPS
2. Validate API responses
3. Implement request timeouts
4. Handle rate limiting
5. Secure API keys
6. Log security events