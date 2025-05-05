# Workerman Shadowsocks 配置

[English](README.md) | [中文](README.zh-CN.md)

这个包提供了用于 Shadowsocks 服务的配置类，用于解析和生成 Shadowsocks 配置。

## 安装

```bash
composer require tourze/shadowsocks-config-php
```

## 功能

- 支持从 JSON 配置文件加载
- 支持标准 URI 格式 (`ss://method:password@hostname:port#tag`)
- 支持 Base64 编码的 URI 格式 (`ss://BASE64-ENCODED-STRING-WITHOUT-PADDING#TAG`)
- 支持 SIP002 URI 格式 (包含插件支持)
- 支持 SIP008 在线配置传递
- 提供各种格式的配置转换

## 使用

### 从 JSON 配置创建

```php
use Shadowsocks\Config\SIP002;

// 从 JSON 文件创建配置
$sip002 = SIP002::fromJsonFile('/path/to/config.json');
$config = $sip002->getConfig();

// JSON 配置示例
// {
//    "server":"my_server_ip",
//    "server_port":8388,
//    "local_port":1080,
//    "password":"barfoo!",
//    "method":"chacha20-ietf-poly1305",
//    "plugin":"obfs-local;obfs=http" // 可选
// }
```

### 从标准 URI 创建

```php
use Shadowsocks\Config\SIP002;

// 从标准 URI 创建配置
$sip002 = SIP002::fromUri('ss://bf-cfb:test/!@#:@192.168.100.1:8888#example-server');
$config = $sip002->getConfig();
```

### 从 Base64 编码的 URI 创建

```php
use Shadowsocks\Config\SIP002;

// 从 Base64 URI 创建配置
$sip002 = SIP002::fromBase64Uri('ss://YmYtY2ZiOnRlc3QvIUAjOkAxOTIuMTY4LjEwMC4xOjg4ODg#example-server');
$config = $sip002->getConfig();
```

### 使用 SIP002 URI 格式

```php
use Shadowsocks\Config\SIP002;
use Shadowsocks\Config\ClientConfig;

// 从 SIP002 URI 创建配置
$sip002 = SIP002::fromUri('ss://YWVzLTI1Ni1nY206cGFzc3dvcmQ@192.168.100.1:8888/?plugin=obfs-local%3Bobfs%3Dhttp#Example');

// 获取配置对象
$config = $sip002->getConfig();

// 获取插件信息
$plugin = $sip002->getPlugin();

// 创建一个新的 SIP002 URI
$config = new ClientConfig('192.168.100.1', 8888, 1080, 'password', 'aes-256-gcm');
$sip002 = new SIP002($config);
$sip002->setPlugin('v2ray-plugin;server');

// 生成 SIP002 URI
$uri = $sip002->toUri();
```

### 使用 SIP008 在线配置

```php
use Shadowsocks\Config\SIP008;
use Shadowsocks\Config\ServerConfig;

// 从 SIP008 JSON 创建
$jsonContent = '{
    "version": 1,
    "servers": [
        {
            "id": "27b8a625-4f4b-4428-9f0f-8a2317db7c79",
            "remarks": "服务器 1",
            "server": "example1.com",
            "server_port": 8388,
            "password": "password1",
            "method": "aes-256-gcm",
            "plugin": "v2ray-plugin",
            "plugin_opts": "server"
        },
        {
            "id": "7842c068-c667-41f2-8f7d-04feece3cb67",
            "remarks": "服务器 2",
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

// 或从 URL 加载（必须是 HTTPS）
try {
    $sip008 = SIP008::fromUrl('https://example.com/config.json');
} catch (\InvalidArgumentException $e) {
    // 处理错误
}

// 从 SIP008 获取服务器
$servers = $sip008->getServers();
foreach ($servers as $server) {
    $id = $server->getId();
    $remarks = $server->getRemarks();
    $config = $server->toConfig(); // 转换为 Config 对象
    
    // 检查插件
    $plugin = $server->getPlugin();
    $pluginOpts = $server->getPluginOpts();
    
    // 使用服务器...
}

// 获取流量使用信息（可选）
$bytesUsed = $sip008->getBytesUsed();
$bytesRemaining = $sip008->getBytesRemaining();

// 将 SIP008 转换为 SIP002 对象列表
$sip002List = $sip008->toSIP002List();

// 从 Config 创建 SIP008
$config = new Config('example.com', 8388, 1080, 'password', 'aes-256-gcm');
$sip008 = SIP008::fromConfig($config, 'v2ray-plugin', 'server');

// 从 SIP002 创建 SIP008
$sip002 = new SIP002($config);
$sip002->setPlugin('v2ray-plugin;server');
$sip008 = SIP008::fromSIP002($sip002);

// 从多个 SIP002 对象创建 SIP008
$sip002List = [
    new SIP002($config1),
    new SIP002($config2)
];
$sip008 = SIP008::fromSIP002List($sip002List);

// 将 SIP008 转换为 JSON
$jsonString = $sip008->toJson();
```

### 直接创建配置

```php
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\SIP002;

// 直接创建配置实例
$config = new ClientConfig(
    '192.168.100.1',  // 服务器地址
    8888,             // 服务器端口
    1080,             // 本地端口
    'password123',    // 密码
    'aes-256-gcm'     // 加密方法
);

// 设置标签
$config->setTag('my-server');

// 使用 SIP002 包装，用于生成 URI
$sip002 = new SIP002($config);
```

### 转换为不同格式

```php
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\SIP002;

$config = new ClientConfig('192.168.100.1', 8888, 1080, 'password123', 'aes-256-gcm');
$sip002 = new SIP002($config);

// 转换为 JSON 配置
$jsonConfig = $config->toJson();

// 转换为 SIP002 URI（推荐）
$sip002Uri = $sip002->toUri();

// 转换为标准 URI 格式
$standardUri = $sip002->toStandardUri();

// 转换为 Base64 编码的 URI 格式
$base64Uri = $sip002->toBase64Uri();
```

## SIP002 规范说明

SIP002 定义了一种标准的 Shadowsocks URI 格式，支持插件参数：

```
SS-URI = "ss://" userinfo "@" hostname ":" port [ "/" ] [ "?" plugin ] [ "#" tag ]
userinfo = websafe-base64-encode-utf8(method ":" password)
           method ":" password
```

- 用户信息可以是 Base64URL 编码的，也可以是明文(需要进行百分比编码)
- 对于 AEAD-2022 加密方法，必须使用明文用户信息
- 插件参数使用 URL 编码

## SIP008 规范说明

SIP008 定义了一种标准的 JSON 文档格式，用于在线配置共享和传递：

```json
{
  "version": 1,
  "servers": [
    {
      "id": "服务器uuid",
      "remarks": "服务器名称",
      "server": "example.com",
      "server_port": 8388,
      "password": "密码",
      "method": "aes-256-gcm",
      "plugin": "插件名称",
      "plugin_opts": "插件选项"
    }
  ],
  "bytes_used": 274877906944,
  "bytes_remaining": 824633720832
}
```

- `version` 和 `servers` 字段是必需的
- 每个服务器必须包含字段：`id`、`server`、`server_port`、`password` 和 `method`
- 可选字段包括 `remarks`、`plugin` 和 `plugin_opts`
- 流量使用字段 `bytes_used` 和 `bytes_remaining` 是可选的

## 许可

MIT 许可证。详情请查看 [License 文件](LICENSE)。

## 参考

- [Shadowsocks 配置文档](https://shadowsocks.org/doc/configs.html)
- [SIP002 URI 方案](https://shadowsocks.org/doc/sip002.html)
- [SIP008 在线配置传递](https://shadowsocks.org/doc/sip008.html)
