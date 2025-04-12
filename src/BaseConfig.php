<?php

namespace Shadowsocks\Config;

/**
 * Shadowsocks基础配置类
 *
 * 提取共用字段和方法的基类
 */
abstract class BaseConfig
{
    /**
     * 服务器主机名或IP地址(IPv4/IPv6)
     */
    protected readonly string $server;

    /**
     * 服务器端口号
     */
    protected readonly int $serverPort;

    /**
     * 用于加密传输的密码
     */
    protected readonly string $password;

    /**
     * 加密方法
     *
     * 推荐使用AEAD加密方式，例如：chacha20-ietf-poly1305或aes-256-gcm
     */
    protected readonly string $method;

    /**
     * 备注/标签
     */
    protected ?string $remarks = null;

    /**
     * 创建基础配置实例
     *
     * @param string $server 服务器主机名或IP地址
     * @param int $serverPort 服务器端口号
     * @param string $password 用于加密传输的密码
     * @param string $method 加密方法
     */
    public function __construct(
        string $server,
        int $serverPort,
        string $password = '',
        string $method = 'chacha20-ietf-poly1305'
    )
    {
        $this->server = $server;
        $this->serverPort = $serverPort;
        $this->password = $password;
        $this->method = $method;
    }

    /**
     * 获取服务器主机名或IP地址
     *
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * 获取服务器端口号
     *
     * @return int
     */
    public function getServerPort(): int
    {
        return $this->serverPort;
    }

    /**
     * 获取密码
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * 获取加密方法
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * 获取备注
     *
     * @return string|null
     */
    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    /**
     * 设置备注
     *
     * @param string|null $remarks
     * @return self
     */
    public function setRemarks(?string $remarks): self
    {
        $this->remarks = $remarks;
        return $this;
    }

    /**
     * 获取基础JSON数据数组
     */
    protected function getBaseJsonArray(): array
    {
        $data = [
            'server' => $this->server,
            'server_port' => $this->serverPort,
            'password' => $this->password,
            'method' => $this->method,
        ];

        if ($this->remarks !== null) {
            $data['remarks'] = $this->remarks;
        }

        return $data;
    }

    /**
     * 转换为JSON字符串
     *
     * @return string
     */
    abstract public function toJson(): string;
}
