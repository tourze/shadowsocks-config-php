<?php

namespace Shadowsocks\Config;

use InvalidArgumentException;

/**
 * SIP008在线配置传递处理类
 *
 * 实现SIP008规范 (https://shadowsocks.org/doc/sip008.html)
 * 定义了在线配置共享和交付的标准JSON文档格式
 */
class SIP008
{
    /**
     * 文档版本
     */
    private int $version = 1;

    /**
     * 服务器配置列表
     *
     * @var ServerConfig[]
     */
    private array $servers = [];

    /**
     * 已使用流量(字节)
     */
    private ?int $bytesUsed = null;

    /**
     * 剩余流量(字节)
     */
    private ?int $bytesRemaining = null;

    /**
     * 从URL加载SIP008配置
     *
     * @param string $url HTTPS URL
     * @return self
     * @throws InvalidArgumentException 加载配置失败
     */
    public static function fromUrl(string $url): self
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('无效的URL');
        }

        if (!str_starts_with($url, 'https://')) {
            throw new InvalidArgumentException('SIP008配置必须通过HTTPS传输');
        }

        $context = stream_context_create([
            'http' => [
                'header' => 'Accept: application/json',
                'timeout' => 10.0,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $content = file_get_contents($url, false, $context);
        if ($content === false) {
            throw new InvalidArgumentException('无法加载SIP008配置: ' . error_get_last()['message'] ?? '未知错误');
        }

        return self::fromJson($content);
    }

    /**
     * 解析SIP008 JSON文档
     *
     * @param string $jsonContent JSON内容
     * @return self
     * @throws InvalidArgumentException JSON格式错误
     */
    public static function fromJson(string $jsonContent): self
    {
        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('SIP008 JSON格式错误: ' . json_last_error_msg());
        }

        if (!isset($data['version']) || !isset($data['servers']) || !is_array($data['servers'])) {
            throw new InvalidArgumentException('无效的SIP008格式: 缺少必要字段或servers不是数组');
        }

        $sip008 = new self();
        $sip008->version = (int)$data['version'];

        foreach ($data['servers'] as $server) {
            if (!isset($server['id']) || !isset($server['server']) || !isset($server['server_port']) ||
                !isset($server['password']) || !isset($server['method'])) {
                throw new InvalidArgumentException('无效的服务器配置: 缺少必要字段');
            }

            $serverConfig = new ServerConfig(
                $server['id'],
                $server['server'],
                (int)$server['server_port'],
                $server['password'],
                $server['method']
            );

            if (isset($server['remarks'])) {
                $serverConfig->setRemarks($server['remarks']);
            }

            if (isset($server['plugin']) && !empty($server['plugin'])) {
                $serverConfig->setPlugin($server['plugin']);

                if (isset($server['plugin_opts']) && !empty($server['plugin_opts'])) {
                    $serverConfig->setPluginOpts($server['plugin_opts']);
                }
            }

            $sip008->addServer($serverConfig);
        }

        // 添加流量使用信息(可选)
        if (isset($data['bytes_used'])) {
            $sip008->setBytesUsed((int)$data['bytes_used']);

            if (isset($data['bytes_remaining'])) {
                $sip008->setBytesRemaining((int)$data['bytes_remaining']);
            }
        }

        return $sip008;
    }

    /**
     * 添加服务器配置
     *
     * @param ServerConfig $server 服务器配置
     * @return $this
     */
    public function addServer(ServerConfig $server): self
    {
        // 检查是否已存在相同ID的服务器
        foreach ($this->servers as $existingServer) {
            if ($existingServer->getId() === $server->getId()) {
                // 如果ID已存在，不再添加
                return $this;
            }
        }

        $this->servers[] = $server;
        return $this;
    }

    /**
     * 从Config创建SIP008配置
     *
     * @param ClientConfig $config 基础配置
     * @param string|null $plugin 插件名称
     * @param string|null $pluginOpts 插件选项
     * @return self
     */
    public static function fromConfig(ClientConfig $config, ?string $plugin = null, ?string $pluginOpts = null): self
    {
        $tag = $config->getTag();

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
            $config->getServer(),
            $config->getServerPort(),
            $config->getPassword(),
            $config->getMethod()
        );

        if ($tag !== null) {
            $serverConfig->setRemarks($tag);
        }

        if ($plugin !== null) {
            $serverConfig->setPlugin($plugin);
        }

        if ($pluginOpts !== null) {
            $serverConfig->setPluginOpts($pluginOpts);
        }

        $sip008 = new self();
        $sip008->addServer($serverConfig);
        return $sip008;
    }

    /**
     * 从SIP002创建SIP008配置
     *
     * @param SIP002 $sip002 SIP002配置
     * @return self
     */
    public static function fromSIP002(SIP002 $sip002): self
    {
        $serverConfig = $sip002->toServerConfig();

        $sip008 = new self();
        $sip008->addServer($serverConfig);
        return $sip008;
    }

    /**
     * 从SIP002列表创建SIP008配置
     *
     * @param array $sip002List SIP002配置列表
     * @return self
     */
    public static function fromSIP002List(array $sip002List): self
    {
        $sip008 = new self();

        foreach ($sip002List as $sip002) {
            if (!($sip002 instanceof SIP002)) {
                continue;
            }

            $serverConfig = $sip002->toServerConfig();
            $sip008->addServer($serverConfig);
        }

        return $sip008;
    }

    /**
     * 获取服务器配置列表
     *
     * @return ServerConfig[]
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * 获取已使用流量
     *
     * @return int|null
     */
    public function getBytesUsed(): ?int
    {
        return $this->bytesUsed;
    }

    /**
     * 设置已使用流量
     *
     * @param int $bytes 字节数
     * @return $this
     */
    public function setBytesUsed(int $bytes): self
    {
        $this->bytesUsed = $bytes;
        return $this;
    }

    /**
     * 获取剩余流量
     *
     * @return int|null
     */
    public function getBytesRemaining(): ?int
    {
        return $this->bytesRemaining;
    }

    /**
     * 设置剩余流量
     *
     * @param int $bytes 字节数
     * @return $this
     */
    public function setBytesRemaining(int $bytes): self
    {
        $this->bytesRemaining = $bytes;
        return $this;
    }

    /**
     * 获取文档版本
     *
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * 转换为SIP002列表
     *
     * @return array 包含SIP002实例的数组
     */
    public function toSIP002List(): array
    {
        $result = [];

        foreach ($this->servers as $server) {
            $config = $server->toConfig();
            $sip002 = new SIP002($config);

            $plugin = $server->getPlugin();
            $pluginOpts = $server->getPluginOpts();

            if ($plugin !== null) {
                $pluginString = $pluginOpts !== null ? $plugin . ';' . $pluginOpts : $plugin;
                $sip002->setPlugin($pluginString);
            }

            $result[] = $sip002;
        }

        return $result;
    }

    /**
     * 转换为标准JSON格式
     *
     * @return string
     */
    public function toJson(): string
    {
        $data = [
            'version' => $this->version,
            'servers' => [],
        ];

        foreach ($this->servers as $server) {
            $serverData = [
                'id' => $server->getId(),
                'server' => $server->getServer(),
                'server_port' => $server->getServerPort(),
                'password' => $server->getPassword(),
                'method' => $server->getMethod(),
            ];

            if ($server->getRemarks() !== null) {
                $serverData['remarks'] = $server->getRemarks();
            }

            if ($server->getPlugin() !== null) {
                $serverData['plugin'] = $server->getPlugin();

                if ($server->getPluginOpts() !== null) {
                    $serverData['plugin_opts'] = $server->getPluginOpts();
                }
            }

            $data['servers'][] = $serverData;
        }

        // 添加可选的流量使用信息
        if ($this->bytesUsed !== null) {
            $data['bytes_used'] = $this->bytesUsed;

            if ($this->bytesRemaining !== null) {
                $data['bytes_remaining'] = $this->bytesRemaining;
            }
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
