<?php

namespace Shadowsocks\Config;

/**
 * Shadowsocks配置类
 *
 * 客户端配置对象
 */
class ClientConfig extends BaseConfig
{

    /**
     * 创建配置实例
     *
     * @param string $server 服务器主机名或IP地址
     * @param int $serverPort 服务器端口号
     * @param int $localPort 本地端口号
     * @param string $password 用于加密传输的密码
     * @param string $method 加密方法
     */
    public function __construct(
        string               $server,
        int                  $serverPort,
        private readonly int $localPort = 1080,
        string               $password = '',
        string               $method = 'chacha20-ietf-poly1305'
    )
    {
        parent::__construct($server, $serverPort, $password, $method);
    }

    /**
     * 获取本地端口号
     *
     * @return int
     */
    public function getLocalPort(): int
    {
        return $this->localPort;
    }

    /**
     * 获取标签（兼容旧方法）
     *
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->getRemarks();
    }

    /**
     * 设置标签（兼容旧方法）
     *
     * @param string|null $tag
     * @return self
     */
    public function setTag(?string $tag): self
    {
        return $this->setRemarks($tag);
    }

    public function toJson(): string
    {
        $data = $this->getBaseJsonArray();

        // 调整标准Config字段
        if (isset($data['remarks'])) {
            unset($data['remarks']);
        }

        $data['local_port'] = $this->localPort;

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
