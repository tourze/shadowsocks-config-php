<?php

namespace Shadowsocks\Config\Tests;

use PHPUnit\Framework\TestCase;
use Shadowsocks\Config\ClientConfig;

class ClientConfigTest extends TestCase
{
    public function testConstructor(): void
    {
        $server = 'example.com';
        $serverPort = 8388;
        $localPort = 1080;
        $password = 'password';
        $method = 'aes-256-gcm';

        $config = new ClientConfig($server, $serverPort, $localPort, $password, $method);

        $this->assertEquals($server, $config->getServer());
        $this->assertEquals($serverPort, $config->getServerPort());
        $this->assertEquals($localPort, $config->getLocalPort());
        $this->assertEquals($password, $config->getPassword());
        $this->assertEquals($method, $config->getMethod());
        $this->assertNull($config->getTag());
    }

    public function testDefaultParameters(): void
    {
        $config = new ClientConfig('example.com', 8388);

        $this->assertEquals(1080, $config->getLocalPort());
        $this->assertEquals('', $config->getPassword());
        $this->assertEquals('chacha20-ietf-poly1305', $config->getMethod());
    }

    public function testTag(): void
    {
        $config = new ClientConfig('example.com', 8388);
        $this->assertNull($config->getTag());

        $tag = 'Test Server';
        $config->setTag($tag);
        $this->assertEquals($tag, $config->getTag());

        // 测试 Remarks 和 Tag 的关系
        $this->assertEquals($tag, $config->getRemarks());

        // 测试修改 remarks 也会更新 tag
        $newTag = 'New Tag';
        $config->setRemarks($newTag);
        $this->assertEquals($newTag, $config->getTag());
    }

    public function testToJson(): void
    {
        $server = 'example.com';
        $serverPort = 8388;
        $localPort = 1080;
        $password = 'password';
        $method = 'aes-256-gcm';
        $tag = 'Test Server';

        $config = new ClientConfig($server, $serverPort, $localPort, $password, $method);
        $config->setTag($tag);

        $json = $config->toJson();
        $data = json_decode($json, true);

        $this->assertEquals($server, $data['server']);
        $this->assertEquals($serverPort, $data['server_port']);
        $this->assertEquals($localPort, $data['local_port']);
        $this->assertEquals($password, $data['password']);
        $this->assertEquals($method, $data['method']);

        // 确认 remarks 字段不存在于 JSON 中（应使用其他名称或不包含）
        $this->assertArrayNotHasKey('remarks', $data);
    }
}
