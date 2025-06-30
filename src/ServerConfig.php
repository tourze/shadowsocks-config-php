<?php

namespace Shadowsocks\Config;

use Shadowsocks\Config\Exception\InvalidConfigException;

/**
 * Shadowsocks服务器配置类
 *
 * 主要用于SIP008格式配置
 */
class ServerConfig extends BaseConfig
{
    /**
     * 服务器ID(UUID)
     */
    private string $id;

    /**
     * 插件名称
     */
    private ?string $plugin = null;

    /**
     * 插件选项
     */
    private ?string $pluginOpts = null;

    /**
     * 创建服务器配置
     *
     * @param string $id 服务器ID(UUID)
     * @param string $server 服务器地址
     * @param int $serverPort 服务器端口
     * @param string $password 密码
     * @param string $method 加密方法
     */
    public function __construct(
        string $id,
        string $server,
        int    $serverPort,
        string $password,
        string $method
    )
    {
        parent::__construct($server, $serverPort, $password, $method);
        $this->id = $id;
    }

    /**
     * 从JSON字符串创建ServerConfig
     *
     * @param string $json JSON字符串
     * @return self
     * @throws InvalidConfigException 如果JSON格式错误或缺少必要字段
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidConfigException('JSON格式错误: ' . json_last_error_msg());
        }

        // 检查必要字段
        $requiredFields = ['id', 'server', 'server_port', 'password', 'method'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new InvalidConfigException("缺少必要字段: {$field}");
            }
        }

        $serverConfig = new self(
            $data['id'],
            $data['server'],
            (int)$data['server_port'],
            $data['password'],
            $data['method']
        );

        if (isset($data['remarks'])) {
            $serverConfig->setRemarks($data['remarks']);
        }

        if (isset($data['plugin'])) {
            $serverConfig->setPlugin($data['plugin']);
        }

        if (isset($data['plugin_opts'])) {
            $serverConfig->setPluginOpts($data['plugin_opts']);
        }

        return $serverConfig;
    }

    /**
     * 获取服务器ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * 获取插件名称
     *
     * @return string|null
     */
    public function getPlugin(): ?string
    {
        return $this->plugin;
    }

    /**
     * 设置插件名称
     *
     * @param string|null $plugin 插件名称
     * @return $this
     */
    public function setPlugin(?string $plugin): self
    {
        $this->plugin = $plugin;
        return $this;
    }

    /**
     * 获取插件选项
     *
     * @return string|null
     */
    public function getPluginOpts(): ?string
    {
        return $this->pluginOpts;
    }

    /**
     * 设置插件选项
     *
     * @param string|null $pluginOpts 插件选项
     * @return $this
     */
    public function setPluginOpts(?string $pluginOpts): self
    {
        $this->pluginOpts = $pluginOpts;
        return $this;
    }

    /**
     * 设置插件字符串（SIP002格式）
     *
     * @param string|null $pluginString 格式为 "plugin;options"
     * @return $this
     */
    public function setPluginString(?string $pluginString): self
    {
        if (empty($pluginString)) {
            $this->plugin = null;
            $this->pluginOpts = null;
            return $this;
        }

        $parts = explode(';', $pluginString, 2);
        $this->plugin = $parts[0] !== '' ? $parts[0] : null;
        $this->pluginOpts = $parts[1] ?? null;

        return $this;
    }

    /**
     * 获取插件字符串（SIP002格式）
     *
     * @return string|null
     */
    public function getPluginString(): ?string
    {
        if ($this->plugin === null) {
            return null;
        }

        if ($this->pluginOpts === null) {
            return $this->plugin;
        }

        return $this->plugin . ';' . $this->pluginOpts;
    }

    /**
     * 转换为Config对象
     *
     * @param int $localPort 本地端口
     * @return ClientConfig
     */
    public function toConfig(int $localPort = 1080): ClientConfig
    {
        $config = new ClientConfig(
            $this->server,
            $this->serverPort,
            $localPort,
            $this->password,
            $this->method
        );

        if ($this->remarks !== null) {
            $config->setTag($this->remarks);
        }

        return $config;
    }

    public function toJson(): string
    {
        $data = $this->getBaseJsonArray();
        $data['id'] = $this->id;

        if ($this->plugin !== null) {
            $data['plugin'] = $this->plugin;
        }

        if ($this->pluginOpts !== null) {
            $data['plugin_opts'] = $this->pluginOpts;
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
