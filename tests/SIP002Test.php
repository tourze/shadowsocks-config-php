<?php

namespace Shadowsocks\Config\Tests;

use PHPUnit\Framework\TestCase;
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\ServerConfig;
use Shadowsocks\Config\SIP002;

class SIP002Test extends TestCase
{
    public function testConstructor(): void
    {
        $config = new ClientConfig('example.com', 8388, 1080, 'password', 'aes-256-gcm');
        $config->setTag('Test Server');

        $sip002 = new SIP002($config);

        $this->assertEquals($config, $sip002->getConfig());
        $this->assertNull($sip002->getPlugin());
    }

    public function testSetGetPlugin(): void
    {
        $config = new ClientConfig('example.com', 8388);
        $sip002 = new SIP002($config);

        $this->assertNull($sip002->getPlugin());

        $plugin = 'v2ray-plugin;server';
        $sip002->setPlugin($plugin);
        $this->assertEquals($plugin, $sip002->getPlugin());

        // 测试设置为null
        $sip002->setPlugin(null);
        $this->assertNull($sip002->getPlugin());
    }

    public function testToServerConfig(): void
    {
        $server = 'example.com';
        $serverPort = 8388;
        $password = 'password';
        $method = 'aes-256-gcm';
        $tag = 'Test Server';
        $plugin = 'v2ray-plugin;server';

        $config = new ClientConfig($server, $serverPort, 1080, $password, $method);
        $config->setTag($tag);

        $sip002 = new SIP002($config);
        $sip002->setPlugin($plugin);

        $serverConfig = $sip002->toServerConfig();

        $this->assertInstanceOf(ServerConfig::class, $serverConfig);
        $this->assertNotEmpty($serverConfig->getId()); // 确认生成了UUID
        $this->assertEquals($server, $serverConfig->getServer());
        $this->assertEquals($serverPort, $serverConfig->getServerPort());
        $this->assertEquals($password, $serverConfig->getPassword());
        $this->assertEquals($method, $serverConfig->getMethod());
        $this->assertEquals($tag, $serverConfig->getRemarks());
        $this->assertEquals('v2ray-plugin', $serverConfig->getPlugin());
        $this->assertEquals('server', $serverConfig->getPluginOpts());
    }

    public function testFromBase64Uri(): void
    {
        // 有标签的Base64 URI
        $base64Uri = 'ss://' . base64_encode('aes-256-gcm:password@example.com:8388') . '#Test%20Server';

        $sip002 = SIP002::fromBase64Uri($base64Uri);
        $config = $sip002->getConfig();

        $this->assertEquals('example.com', $config->getServer());
        $this->assertEquals(8388, $config->getServerPort());
        $this->assertEquals('password', $config->getPassword());
        $this->assertEquals('aes-256-gcm', $config->getMethod());
        $this->assertEquals('Test%20Server', $config->getTag());

        // 无标签的Base64 URI
        $base64Uri = 'ss://' . base64_encode('chacha20-ietf-poly1305:test@example.org:8389');

        $sip002 = SIP002::fromBase64Uri($base64Uri);
        $config = $sip002->getConfig();

        $this->assertEquals('example.org', $config->getServer());
        $this->assertEquals(8389, $config->getServerPort());
        $this->assertEquals('test', $config->getPassword());
        $this->assertEquals('chacha20-ietf-poly1305', $config->getMethod());
        $this->assertNull($config->getTag());
    }

    public function testFromSIP002Uri(): void
    {
        // 带插件的SIP002 URI
        $uri = 'ss://' . base64_encode('aes-256-gcm:password') . '@example.com:8388/?plugin=v2ray-plugin%3Bserver#Test%20Server';

        $sip002 = SIP002::fromSIP002Uri($uri);
        $config = $sip002->getConfig();

        $this->assertEquals('example.com', $config->getServer());
        $this->assertEquals(8388, $config->getServerPort());
        $this->assertEquals('password', $config->getPassword());
        $this->assertEquals('aes-256-gcm', $config->getMethod());
        $this->assertEquals('Test Server', $config->getTag());
        $this->assertEquals('v2ray-plugin;server', $sip002->getPlugin());

        // 不带插件的SIP002 URI
        $uri = 'ss://' . base64_encode('chacha20-ietf-poly1305:test') . '@example.org:8389';

        $sip002 = SIP002::fromSIP002Uri($uri);
        $config = $sip002->getConfig();

        $this->assertEquals('example.org', $config->getServer());
        $this->assertEquals(8389, $config->getServerPort());
        $this->assertEquals('chacha20-ietf-poly1305', $config->getMethod());
        $this->assertEquals('test', $config->getPassword());
        $this->assertNull($config->getTag());
        $this->assertNull($sip002->getPlugin());
    }

    public function testToUri(): void
    {
        $server = 'example.com';
        $serverPort = 8388;
        $password = 'password';
        $method = 'aes-256-gcm';
        $tag = 'Test Server';
        $plugin = 'v2ray-plugin;server';

        $config = new ClientConfig($server, $serverPort, 1080, $password, $method);
        $config->setTag($tag);

        $sip002 = new SIP002($config);
        $sip002->setPlugin($plugin);

        $uri = $sip002->toUri();

        // 解析并验证生成的URI
        $this->assertStringStartsWith('ss://', $uri);
        $this->assertStringContainsString('@example.com:8388', $uri);
        $this->assertStringContainsString('plugin=v2ray-plugin%3Bserver', $uri);
        $this->assertStringContainsString('#Test+Server', $uri);

        // 测试AEAD-2022加密方法
        $config2 = new ClientConfig($server, $serverPort, 1080, $password, '2022-blake3-aes-256-gcm');
        $sip002_2 = new SIP002($config2);

        $uri2 = $sip002_2->toUri();
        $this->assertStringContainsString('ss://2022-blake3-aes-256-gcm:', $uri2);
    }

    public function testToBase64Uri(): void
    {
        $config = new ClientConfig('example.com', 8388, 1080, 'password', 'aes-256-gcm');
        $config->setTag('Test Server');

        $sip002 = new SIP002($config);

        $uri = $sip002->toBase64Uri();

        // 验证生成的Base64 URI
        $this->assertStringStartsWith('ss://', $uri);
        $this->assertStringContainsString('#Test Server', $uri);

        // 解码并验证内容
        $base64Part = substr($uri, 5, strpos($uri, '#') - 5);
        $decodedPart = base64_decode($base64Part . '==');

        $this->assertStringContainsString('aes-256-gcm:password@example.com:8388', $decodedPart);
    }
}
