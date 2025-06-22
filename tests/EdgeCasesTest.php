<?php

namespace Shadowsocks\Config\Tests;

use PHPUnit\Framework\TestCase;
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\ServerConfig;
use Shadowsocks\Config\SIP002;
use Shadowsocks\Config\SIP008;

class EdgeCasesTest extends TestCase
{
    /**
     * 测试特殊字符在 URL 编码和解码中的处理
     */
    public function testSpecialCharacters(): void
    {
        // 测试包含特殊字符的标签处理
        $specialChars = "Special & 特殊 <> 字符 + 测试 % ! ?";

        $config = new ClientConfig('example.com', 8388, 1080, 'password', 'aes-256-gcm');
        $config->setTag($specialChars);

        $sip002 = new SIP002($config);
        $uri = $sip002->toUri();

        // 确保可以正确编码和解码
        $decodedSip002 = SIP002::fromSIP002Uri($uri);
        $this->assertEquals($specialChars, $decodedSip002->getConfig()->getTag());

        // 测试包含特殊字符的密码处理 (使用较简单的特殊字符)
        $specialPassword = "p@ssw0rd!";

        $config2 = new ClientConfig('example.com', 8388, 1080, $specialPassword, 'aes-256-gcm');
        $sip002_2 = new SIP002($config2);
        $uri2 = $sip002_2->toUriWithBase64UserInfo(); // 明确使用Base64编码用户信息

        $decodedSip002_2 = SIP002::fromSIP002Uri($uri2);
        $this->assertEquals($specialPassword, $decodedSip002_2->getConfig()->getPassword());
    }

    /**
     * 测试空值处理
     */
    public function testEmptyValues(): void
    {
        // 测试空密码
        $config = new ClientConfig('example.com', 8388, 1080, '', 'aes-256-gcm');
        $sip002 = new SIP002($config);
        $uri = $sip002->toUri();

        $decodedSip002 = SIP002::fromSIP002Uri($uri);
        $this->assertEquals('', $decodedSip002->getConfig()->getPassword());

        // 测试空标签
        $config->setTag('');
        $uri = $sip002->toUri();

        $decodedSip002 = SIP002::fromSIP002Uri($uri);
        $this->assertEquals('', $decodedSip002->getConfig()->getTag());

        // 空服务器列表的 SIP008
        $sip008 = new SIP008();
        $json = $sip008->toJson();

        $decodedSip008 = SIP008::fromJson($json);
        $this->assertEmpty($decodedSip008->getServers());
    }

    /**
     * 测试 IPv6 地址处理
     */
    public function testIPv6Addresses(): void
    {
        // 测试 IPv6 地址
        $ipv6 = "2001:db8:85a3::8a2e:370:7334";
        $config = new ClientConfig($ipv6, 8388);

        $sip002 = new SIP002($config);
        $uri = $sip002->toUri();

        $decodedSip002 = SIP002::fromSIP002Uri($uri);
        $this->assertEquals($ipv6, $decodedSip002->getConfig()->getServer());

        // 测试 IPv6 地址在 SIP008 中的处理
        $server = new ServerConfig(
            '27b8a625-4f4b-4428-9f0f-8a2317db7c79',
            $ipv6,
            8388,
            'password',
            'aes-256-gcm'
        );

        $sip008 = new SIP008();
        $sip008->addServer($server);
        $json = $sip008->toJson();

        $decodedSip008 = SIP008::fromJson($json);
        $servers = $decodedSip008->getServers();
        $this->assertEquals($ipv6, $servers[0]->getServer());
    }

    /**
     * 测试大型数据处理
     */
    public function testLargeData(): void
    {
        $sip008 = new SIP008();

        // 添加大量服务器
        for ($i = 0; $i < 100; $i++) {
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

            $server = new ServerConfig(
                $uuid,
                "server{$i}.example.com",
                8388 + $i,
                "password{$i}",
                'aes-256-gcm'
            );

            $sip008->addServer($server);
        }

        // 设置大流量值
        $sip008->setBytesUsed(PHP_INT_MAX);
        $sip008->setBytesRemaining(PHP_INT_MAX);

        $json = $sip008->toJson();

        $decodedSip008 = SIP008::fromJson($json);
        $this->assertCount(100, $decodedSip008->getServers());
        $this->assertEquals(PHP_INT_MAX, $decodedSip008->getBytesUsed());
        $this->assertEquals(PHP_INT_MAX, $decodedSip008->getBytesRemaining());
    }

    /**
     * 测试 SIP008 类的方法调用链
     */
    public function testMethodChaining(): void
    {
        $sip008 = new SIP008();

        $server1 = new ServerConfig(
            '27b8a625-4f4b-4428-9f0f-8a2317db7c79',
            'server1.example.com',
            8388,
            'password1',
            'aes-256-gcm'
        );

        $server2 = new ServerConfig(
            '7842c068-c667-41f2-8f7d-04feece3cb67',
            'server2.example.com',
            8389,
            'password2',
            'chacha20-ietf-poly1305'
        );

        // 测试方法链
        $sip008->addServer($server1)
            ->addServer($server2)
            ->setBytesUsed(1000)
            ->setBytesRemaining(2000);

        $this->assertCount(2, $sip008->getServers());
        $this->assertEquals(1000, $sip008->getBytesUsed());
        $this->assertEquals(2000, $sip008->getBytesRemaining());
    }
}
