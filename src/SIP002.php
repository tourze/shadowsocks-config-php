<?php

namespace Shadowsocks\Config;

use InvalidArgumentException;

/**
 * SIP002 URI格式处理类
 *
 * 实现SIP002规范 (https://shadowsocks.org/doc/sip002.html)
 * SS-URI = "ss://" userinfo "@" hostname ":" port [ "/" ] [ "?" plugin ] [ "#" tag ]
 * userinfo = websafe-base64-encode-utf8(method ":" password)
 *           method ":" password
 */
class SIP002
{
    /**
     * 配置对象
     */
    private readonly ClientConfig $config;

    /**
     * 插件参数
     */
    private ?string $plugin = null;

    /**
     * 创建SIP002实例
     *
     * @param ClientConfig $config 配置对象
     */
    public function __construct(ClientConfig $config)
    {
        $this->config = $config;
    }

    /**
     * 从JSON配置文件创建配置
     *
     * @param string $jsonFilePath 配置文件路径
     * @return self
     * @throws InvalidArgumentException 配置文件不存在或格式错误
     */
    public static function fromJsonFile(string $jsonFilePath): self
    {
        if (!file_exists($jsonFilePath)) {
            throw new InvalidArgumentException("配置文件不存在: {$jsonFilePath}");
        }

        $content = file_get_contents($jsonFilePath);
        $config = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('配置文件JSON格式错误: ' . json_last_error_msg());
        }

        $serverConfig = new ClientConfig(
            $config['server'] ?? '',
            $config['server_port'] ?? 0,
            $config['local_port'] ?? 1080,
            $config['password'] ?? '',
            $config['method'] ?? 'chacha20-ietf-poly1305'
        );

        $sip002 = new self($serverConfig);

        // 检查是否有plugin参数
        if (isset($config['plugin'])) {
            $sip002->setPlugin($config['plugin']);
        }

        return $sip002;
    }

    /**
     * 从标准URI格式创建配置
     *
     * URI格式: ss://method:password@hostname:port#tag
     *
     * @param string $uri Shadowsocks URI
     * @return self
     * @throws InvalidArgumentException URI格式错误
     */
    public static function fromUri(string $uri): self
    {
        if (!str_starts_with($uri, 'ss://')) {
            throw new InvalidArgumentException('无效的Shadowsocks URI，必须以ss://开头');
        }

        // 检查是否是SIP002格式还是标准URI格式
        $parts = parse_url($uri);
        if (isset($parts['user']) && isset($parts['host']) && isset($parts['port'])) {
            return self::fromSIP002Uri($uri);
        }

        // 处理标准URI格式: ss://method:password@hostname:port#tag
        $parts = parse_url(substr($uri, 5));

        if (!isset($parts['user']) || !isset($parts['host']) || !isset($parts['port'])) {
            throw new InvalidArgumentException('无效的Shadowsocks URI格式');
        }

        $method = $parts['user'];
        $password = $parts['pass'] ?? '';
        $server = $parts['host'];
        $serverPort = (int)$parts['port'];
        $tag = isset($parts['fragment']) ? urldecode($parts['fragment']) : null;

        $config = new ClientConfig($server, $serverPort, 1080, $password, $method);
        if ($tag !== null) {
            $config->setTag($tag);
        }

        return new self($config);
    }

    /**
     * 从SIP002 URI解析配置
     *
     * @param string $uri SIP002 URI
     * @return self
     * @throws InvalidArgumentException URI格式错误
     */
    public static function fromSIP002Uri(string $uri): self
    {
        if (!str_starts_with($uri, 'ss://')) {
            throw new InvalidArgumentException('无效的Shadowsocks URI，必须以ss://开头');
        }

        $parts = parse_url($uri);
        if (!isset($parts['host']) || !isset($parts['port'])) {
            throw new InvalidArgumentException('无效的SIP002 URI格式: 缺少主机或端口');
        }

        $server = $parts['host'];
        $serverPort = (int)$parts['port'];
        $tag = isset($parts['fragment']) ? urldecode($parts['fragment']) : null;
        $plugin = null;

        // 解析插件参数
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            if (isset($query['plugin'])) {
                $plugin = $query['plugin'];
            }
        }

        // 解析用户信息
        if (!isset($parts['user'])) {
            throw new InvalidArgumentException('无效的SIP002 URI格式: 缺少用户信息');
        }

        $userInfo = $parts['user'];
        $password = $parts['pass'] ?? '';

        // 尝试Base64解码用户信息
        $decodedUserInfo = base64_decode($userInfo . (str_contains($userInfo, '=') ? '' : '=='), true);

        // 检查是否为Base64编码的用户信息
        if ($decodedUserInfo !== false && strpos($decodedUserInfo, ':') !== false) {
            list($method, $decodedPassword) = explode(':', $decodedUserInfo, 2);
            // 使用解码后的密码
            $password = $decodedPassword;
        } else {
            // 非Base64编码，直接使用
            $method = $userInfo;
            // 密码已经从parts['pass']获取
        }

        // 如果是百分比编码，则解码
        $method = urldecode($method);
        $password = urldecode($password);

        $config = new ClientConfig($server, $serverPort, 1080, $password, $method);
        if ($tag !== null) {
            $config->setTag($tag);
        }

        $sip002 = new self($config);
        if ($plugin !== null) {
            $sip002->setPlugin($plugin);
        }

        return $sip002;
    }

    /**
     * 从Base64编码的URI格式创建配置
     *
     * Base64 URI格式: ss://BASE64-ENCODED-STRING-WITHOUT-PADDING#TAG
     *
     * @param string $base64Uri Base64编码的Shadowsocks URI
     * @return self
     * @throws InvalidArgumentException URI格式错误
     */
    public static function fromBase64Uri(string $base64Uri): self
    {
        if (!str_starts_with($base64Uri, 'ss://')) {
            throw new InvalidArgumentException('无效的Shadowsocks Base64 URI，必须以ss://开头');
        }

        $uri = substr($base64Uri, 5);
        $tag = null;

        // 分离TAG部分
        if (($hashPos = strpos($uri, '#')) !== false) {
            $tag = substr($uri, $hashPos + 1);
            $uri = substr($uri, 0, $hashPos);
        }

        // Base64解码
        $decodedUri = base64_decode($uri);
        if ($decodedUri === false) {
            throw new InvalidArgumentException('无效的Base64编码');
        }

        // 解析标准URI格式
        $parts = explode('@', $decodedUri, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('无效的URI格式');
        }

        $methodPass = explode(':', $parts[0], 2);
        if (count($methodPass) !== 2) {
            throw new InvalidArgumentException('无效的加密方法和密码格式');
        }

        $hostPort = explode(':', $parts[1], 2);
        if (count($hostPort) !== 2) {
            throw new InvalidArgumentException('无效的主机和端口格式');
        }

        $method = $methodPass[0];
        $password = $methodPass[1];
        $server = $hostPort[0];
        $serverPort = (int)$hostPort[1];

        $config = new ClientConfig($server, $serverPort, 1080, $password, $method);
        if ($tag !== null) {
            $config->setTag($tag);
        }

        return new self($config);
    }

    /**
     * 获取配置对象
     *
     * @return ClientConfig
     */
    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    /**
     * 获取插件
     *
     * @return string|null
     */
    public function getPlugin(): ?string
    {
        return $this->plugin;
    }

    /**
     * 设置插件
     *
     * @param string|null $plugin 插件字符串，格式为 "插件名称;插件选项"
     * @return self
     */
    public function setPlugin(?string $plugin): self
    {
        $this->plugin = $plugin;
        return $this;
    }

    /**
     * 转换为ServerConfig对象
     *
     * @return ServerConfig
     */
    public function toServerConfig(): ServerConfig
    {
        // 生成一个随机UUID
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );

        $serverConfig = new ServerConfig(
            $uuid,
            $this->config->getServer(),
            $this->config->getServerPort(),
            $this->config->getPassword(),
            $this->config->getMethod()
        );

        $tag = $this->config->getTag();
        if ($tag !== null) {
            $serverConfig->setRemarks($tag);
        }

        if ($this->plugin !== null) {
            $serverConfig->setPluginString($this->plugin);
        }

        return $serverConfig;
    }

    /**
     * 根据加密方法选择合适的URI格式
     *
     * @return string
     */
    public function toUri(): string
    {
        $method = $this->config->getMethod();

        // AEAD-2022加密方法必须使用明文用户信息
        if (str_starts_with($method, '2022-')) {
            return $this->toUriWithPlainUserInfo();
        } else {
            // 其他加密方法推荐使用Base64编码的用户信息
            return $this->toUriWithBase64UserInfo();
        }
    }

    /**
     * 转换为带有明文用户信息的SIP002 URI
     *
     * @return string
     */
    public function toUriWithPlainUserInfo(): string
    {
        $method = urlencode($this->config->getMethod());
        $password = urlencode($this->config->getPassword());
        $server = $this->config->getServer();
        $serverPort = $this->config->getServerPort();
        $tag = $this->config->getTag();

        $uri = "ss://{$method}:{$password}@{$server}:{$serverPort}";

        // 添加插件参数
        if ($this->plugin !== null) {
            $uri .= '/?plugin=' . urlencode($this->plugin);
        }

        // 添加标签
        if ($tag !== null) {
            $uri .= '#' . urlencode($tag);
        }

        return $uri;
    }

    /**
     * 转换为带有Base64编码用户信息的SIP002 URI
     *
     * @return string
     */
    public function toUriWithBase64UserInfo(): string
    {
        $method = $this->config->getMethod();
        $password = $this->config->getPassword();
        $server = $this->config->getServer();
        $serverPort = $this->config->getServerPort();
        $tag = $this->config->getTag();

        // Base64编码用户信息
        $userInfo = base64_encode("{$method}:{$password}");
        $userInfo = rtrim($userInfo, '='); // 移除填充

        $uri = "ss://{$userInfo}@{$server}:{$serverPort}";

        // 添加插件参数
        if ($this->plugin !== null) {
            $uri .= '/?plugin=' . urlencode($this->plugin);
        }

        // 添加标签
        if ($tag !== null) {
            $uri .= '#' . urlencode($tag);
        }

        return $uri;
    }

    /**
     * 转换为标准URI格式
     *
     * @return string
     */
    public function toStandardUri(): string
    {
        $method = $this->config->getMethod();
        $password = $this->config->getPassword();
        $server = $this->config->getServer();
        $serverPort = $this->config->getServerPort();
        $tag = $this->config->getTag();

        $uri = "ss://{$method}:{$password}@{$server}:{$serverPort}";

        if ($tag !== null) {
            $uri .= '#' . urlencode($tag);
        }

        return $uri;
    }

    /**
     * 转换为Base64编码的标准URI格式
     *
     * @return string
     */
    public function toBase64Uri(): string
    {
        $method = $this->config->getMethod();
        $password = $this->config->getPassword();
        $server = $this->config->getServer();
        $serverPort = $this->config->getServerPort();
        $tag = $this->config->getTag();

        $plainUri = "{$method}:{$password}@{$server}:{$serverPort}";
        $base64Uri = 'ss://' . rtrim(base64_encode($plainUri), '=');

        if ($tag !== null) {
            $base64Uri .= '#' . $tag;
        }

        return $base64Uri;
    }
}
