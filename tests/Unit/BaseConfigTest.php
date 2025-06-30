<?php

namespace Shadowsocks\Config\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shadowsocks\Config\BaseConfig;

class BaseConfigTest extends TestCase
{
    private BaseConfig $config;

    protected function setUp(): void
    {
        // 创建一个具体的匿名类来测试抽象类
        $this->config = new class('example.com', 8388, 'password', 'aes-256-gcm') extends BaseConfig {
            public function toJson(): string
            {
                return json_encode($this->getBaseJsonArray());
            }
        };
    }

    public function testConstructorWithAllParameters(): void
    {
        $server = 'test.example.com';
        $serverPort = 8080;
        $password = 'test-password';
        $method = 'chacha20-poly1305';

        $config = new class($server, $serverPort, $password, $method) extends BaseConfig {
            public function toJson(): string
            {
                return json_encode($this->getBaseJsonArray());
            }
        };

        $this->assertEquals($server, $config->getServer());
        $this->assertEquals($serverPort, $config->getServerPort());
        $this->assertEquals($password, $config->getPassword());
        $this->assertEquals($method, $config->getMethod());
        $this->assertNull($config->getRemarks());
    }

    public function testConstructorWithDefaultParameters(): void
    {
        $server = 'default.example.com';
        $serverPort = 443;

        $config = new class($server, $serverPort) extends BaseConfig {
            public function toJson(): string
            {
                return json_encode($this->getBaseJsonArray());
            }
        };

        $this->assertEquals($server, $config->getServer());
        $this->assertEquals($serverPort, $config->getServerPort());
        $this->assertEquals('', $config->getPassword());
        $this->assertEquals('chacha20-ietf-poly1305', $config->getMethod());
    }

    public function testGetters(): void
    {
        $this->assertEquals('example.com', $this->config->getServer());
        $this->assertEquals(8388, $this->config->getServerPort());
        $this->assertEquals('password', $this->config->getPassword());
        $this->assertEquals('aes-256-gcm', $this->config->getMethod());
        $this->assertNull($this->config->getRemarks());
    }

    public function testSetAndGetRemarks(): void
    {
        $this->assertNull($this->config->getRemarks());

        $remarks = 'Test Server';
        $result = $this->config->setRemarks($remarks);
        
        // 测试链式调用
        $this->assertSame($this->config, $result);
        $this->assertEquals($remarks, $this->config->getRemarks());

        // 测试设置为 null
        $this->config->setRemarks(null);
        $this->assertNull($this->config->getRemarks());
    }

    public function testGetBaseJsonArray(): void
    {
        $json = json_decode($this->config->toJson(), true);

        $this->assertIsArray($json);
        $this->assertEquals('example.com', $json['server']);
        $this->assertEquals(8388, $json['server_port']);
        $this->assertEquals('password', $json['password']);
        $this->assertEquals('aes-256-gcm', $json['method']);
        $this->assertArrayNotHasKey('remarks', $json);
    }

    public function testGetBaseJsonArrayWithRemarks(): void
    {
        $remarks = 'Production Server';
        $this->config->setRemarks($remarks);

        $json = json_decode($this->config->toJson(), true);

        $this->assertIsArray($json);
        $this->assertEquals('example.com', $json['server']);
        $this->assertEquals(8388, $json['server_port']);
        $this->assertEquals('password', $json['password']);
        $this->assertEquals('aes-256-gcm', $json['method']);
        $this->assertArrayHasKey('remarks', $json);
        $this->assertEquals($remarks, $json['remarks']);
    }

    public function testEmptyPasswordHandling(): void
    {
        $config = new class('empty-pass.com', 8388) extends BaseConfig {
            public function toJson(): string
            {
                return json_encode($this->getBaseJsonArray());
            }
        };

        $this->assertEquals('', $config->getPassword());
        
        $json = json_decode($config->toJson(), true);
        $this->assertEquals('', $json['password']);
    }

    public function testDifferentPortNumbers(): void
    {
        $testCases = [
            80,
            443,
            8080,
            8888,
            65535, // 最大端口号
            1,     // 最小有效端口号
        ];

        foreach ($testCases as $port) {
            $config = new class('port-test.com', $port) extends BaseConfig {
                public function toJson(): string
                {
                    return json_encode($this->getBaseJsonArray());
                }
            };

            $this->assertEquals($port, $config->getServerPort());
        }
    }
}