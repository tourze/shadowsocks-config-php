# Workerman Shadowsocks Config

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/shadowsocks-config-php.svg?style=flat-square)](https://packagist.org/packages/tourze/shadowsocks-config-php)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

This package provides configuration classes for Shadowsocks services, supporting parsing and generating Shadowsocks
configurations.

## Features

- Support for loading configurations from JSON files
- Support for standard URI format (`ss://method:password@hostname:port#tag`)
- Support for Base64 encoded URI format (`ss://BASE64-ENCODED-STRING-WITHOUT-PADDING#TAG`)
- Support for SIP002 URI format (with plugin support)
- Support for SIP008 online configuration delivery
- Various configuration format conversions

## Installation

```bash
composer require tourze/shadowsocks-config-php
```

## Usage

### Creating from JSON Config

```php
use Shadowsocks\Config\SIP002;

// Create config from JSON file
$sip002 = SIP002::fromJsonFile('/path/to/config.json');
$config = $sip002->getConfig();

// Example JSON config
// {
//    "server":"my_server_ip",
//    "server_port":8388,
//    "local_port":1080,
//    "password":"barfoo!",
//    "method":"chacha20-ietf-poly1305",
//    "plugin":"obfs-local;obfs=http" // Optional
// }
```

### Creating from Standard URI

```php
use Shadowsocks\Config\SIP002;

// Create config from standard URI
$sip002 = SIP002::fromUri('ss://bf-cfb:test/!@#:@192.168.100.1:8888#example-server');
$config = $sip002->getConfig();
```

### Creating from Base64 Encoded URI

```php
use Shadowsocks\Config\SIP002;

// Create config from Base64 URI
$sip002 = SIP002::fromBase64Uri('ss://YmYtY2ZiOnRlc3QvIUAjOkAxOTIuMTY4LjEwMC4xOjg4ODg#example-server');
$config = $sip002->getConfig();
```

### Using SIP002 URI Format

```php
use Shadowsocks\Config\SIP002;
use Shadowsocks\Config\ClientConfig;

// Create from SIP002 URI
$sip002 = SIP002::fromUri('ss://YWVzLTI1Ni1nY206cGFzc3dvcmQ@192.168.100.1:8888/?plugin=obfs-local%3Bobfs%3Dhttp#Example');

// Get the config object
$config = $sip002->getConfig();

// Get plugin information
$plugin = $sip002->getPlugin();

// Create a new SIP002 URI
$config = new ClientConfig('192.168.100.1', 8888, 1080, 'password', 'aes-256-gcm');
$sip002 = new SIP002($config);
$sip002->setPlugin('v2ray-plugin;server');

// Generate SIP002 URI
$uri = $sip002->toUri();
```

### Using SIP008 Online Configuration

```php
use Shadowsocks\Config\SIP008;
use Shadowsocks\Config\ServerConfig;

// Create from SIP008 JSON
$jsonContent = '{
    "version": 1,
    "servers": [
        {
            "id": "27b8a625-4f4b-4428-9f0f-8a2317db7c79",
            "remarks": "Server 1",
            "server": "example1.com",
            "server_port": 8388,
            "password": "password1",
            "method": "aes-256-gcm",
            "plugin": "v2ray-plugin",
            "plugin_opts": "server"
        },
        {
            "id": "7842c068-c667-41f2-8f7d-04feece3cb67",
            "remarks": "Server 2",
            "server": "example2.com",
            "server_port": 8389,
            "password": "password2",
            "method": "chacha20-ietf-poly1305"
        }
    ],
    "bytes_used": 274877906944,
    "bytes_remaining": 824633720832
}';

$sip008 = SIP008::fromJson($jsonContent);

// Or load from URL (HTTPS required)
try {
    $sip008 = SIP008::fromUrl('https://example.com/config.json');
} catch (\InvalidArgumentException $e) {
    // Handle error
}

// Get servers from SIP008
$servers = $sip008->getServers();
foreach ($servers as $server) {
    $id = $server->getId();
    $remarks = $server->getRemarks();
    $config = $server->toConfig(); // Convert to Config object
    
    // Check for plugin
    $plugin = $server->getPlugin();
    $pluginOpts = $server->getPluginOpts();
    
    // Use the server...
}

// Get data usage information (optional)
$bytesUsed = $sip008->getBytesUsed();
$bytesRemaining = $sip008->getBytesRemaining();

// Convert SIP008 to list of SIP002 objects
$sip002List = $sip008->toSIP002List();

// Create SIP008 from Config
$config = new Config('example.com', 8388, 1080, 'password', 'aes-256-gcm');
$sip008 = SIP008::fromConfig($config, 'v2ray-plugin', 'server');

// Create SIP008 from SIP002
$sip002 = new SIP002($config);
$sip002->setPlugin('v2ray-plugin;server');
$sip008 = SIP008::fromSIP002($sip002);

// Create SIP008 from multiple SIP002 objects
$sip002List = [
    new SIP002($config1),
    new SIP002($config2)
];
$sip008 = SIP008::fromSIP002List($sip002List);

// Convert SIP008 to JSON
$jsonString = $sip008->toJson();
```

### Direct Configuration Creation

```php
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\SIP002;

// Create a config instance directly
$config = new ClientConfig(
    '192.168.100.1',  // Server address
    8888,             // Server port
    1080,             // Local port
    'password123',    // Password
    'aes-256-gcm'     // Encryption method
);

// Set tag
$config->setTag('my-server');

// Wrap with SIP002 for URI generation
$sip002 = new SIP002($config);
```

### Converting to Different Formats

```php
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\SIP002;

$config = new ClientConfig('192.168.100.1', 8888, 1080, 'password123', 'aes-256-gcm');
$sip002 = new SIP002($config);

// Convert to JSON config
$jsonConfig = $config->toJson();

// Convert to SIP002 URI (recommended)
$sip002Uri = $sip002->toUri();

// Convert to standard URI format
$standardUri = $sip002->toStandardUri();

// Convert to Base64 encoded URI format
$base64Uri = $sip002->toBase64Uri();
```

## SIP002 Specification

SIP002 defines a standard Shadowsocks URI format with plugin support:

```
SS-URI = "ss://" userinfo "@" hostname ":" port [ "/" ] [ "?" plugin ] [ "#" tag ]
userinfo = websafe-base64-encode-utf8(method ":" password)
           method ":" password
```

- User info can be Base64URL encoded or plain text (which must be percent-encoded)
- For AEAD-2022 encryption methods, plain text user info must be used
- Plugin parameters use URL encoding

## SIP008 Specification

SIP008 defines a standard JSON document format for online configuration sharing and delivery:

```json
{
  "version": 1,
  "servers": [
    {
      "id": "server-uuid",
      "remarks": "Server name",
      "server": "example.com",
      "server_port": 8388,
      "password": "password",
      "method": "aes-256-gcm",
      "plugin": "plugin-name",
      "plugin_opts": "plugin-options"
    }
  ],
  "bytes_used": 274877906944,
  "bytes_remaining": 824633720832
}
```

- The `version` and `servers` fields are mandatory
- Each server must have the fields: `id`, `server`, `server_port`, `password`, and `method`
- Optional fields include `remarks`, `plugin`, and `plugin_opts`
- Data usage fields `bytes_used` and `bytes_remaining` are optional

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## References

- [Shadowsocks Configuration Documentation](https://shadowsocks.org/doc/configs.html)
- [SIP002 URI Scheme](https://shadowsocks.org/doc/sip002.html)
- [SIP008 Online Configuration Delivery](https://shadowsocks.org/doc/sip008.html)
