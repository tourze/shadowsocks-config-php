<?php

namespace Shadowsocks\Config\Tests;

use PHPUnit\Framework\TestCase;
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\ServerConfig;
use Shadowsocks\Config\SIP002;
use Shadowsocks\Config\SIP008;

class FormatConversionTest extends TestCase
{
    /**
     * 测试 SIP002 和 SIP008 之间的转换
     */
    public function testSIP002ToSIP008Conversion(): void
    {
        // 创建一个包含多种配置的 SIP002 列表
        $configs = [];

        // 第一个配置 - 标准加密方法 + 插件
        $config1 = new ClientConfig('server1.example.com', 8388, 1080, 'password1', 'aes-256-gcm');
        $config1->setTag('Server 1');
        $sip002_1 = new SIP002($config1);
        $sip002_1->setPlugin('v2ray-plugin;server');
        $configs[] = $sip002_1;

        // 第二个配置 - AEAD-2022 加密方法
        $config2 = new ClientConfig('server2.example.com', 8389, 1080, 'password2', '2022-blake3-aes-256-gcm');
        $config2->setTag('Server 2');
        $configs[] = new SIP002($config2);

        // 第三个配置 - 特殊字符
        $config3 = new ClientConfig('server3.example.com', 8390, 1080, 'p@ssw0rd!', 'chacha20-ietf-poly1305');
        $config3->setTag('特殊 Server & Test');
        $sip002_3 = new SIP002($config3);
        $sip002_3->setPlugin('simple-obfs;obfs=http;obfs-host=www.example.com');
        $configs[] = $sip002_3;

        // SIP002 列表转换为 SIP008
        $sip008 = SIP008::fromSIP002List($configs);

        // 验证 SIP008 配置
        $this->assertEquals(1, $sip008->getVersion());
        $this->assertCount(3, $sip008->getServers());

        $servers = $sip008->getServers();

        // 验证第一个服务器
        $this->assertEquals('Server 1', $servers[0]->getRemarks());
        $this->assertEquals('server1.example.com', $servers[0]->getServer());
        $this->assertEquals(8388, $servers[0]->getServerPort());
        $this->assertEquals('password1', $servers[0]->getPassword());
        $this->assertEquals('aes-256-gcm', $servers[0]->getMethod());
        $this->assertEquals('v2ray-plugin', $servers[0]->getPlugin());
        $this->assertEquals('server', $servers[0]->getPluginOpts());

        // 验证第二个服务器
        $this->assertEquals('Server 2', $servers[1]->getRemarks());
        $this->assertEquals('server2.example.com', $servers[1]->getServer());
        $this->assertEquals(8389, $servers[1]->getServerPort());
        $this->assertEquals('password2', $servers[1]->getPassword());
        $this->assertEquals('2022-blake3-aes-256-gcm', $servers[1]->getMethod());
        $this->assertNull($servers[1]->getPlugin());

        // 验证第三个服务器
        $this->assertEquals('特殊 Server & Test', $servers[2]->getRemarks());
        $this->assertEquals('server3.example.com', $servers[2]->getServer());
        $this->assertEquals(8390, $servers[2]->getServerPort());
        $this->assertEquals('p@ssw0rd!', $servers[2]->getPassword());
        $this->assertEquals('chacha20-ietf-poly1305', $servers[2]->getMethod());
        $this->assertEquals('simple-obfs', $servers[2]->getPlugin());
        $this->assertEquals('obfs=http;obfs-host=www.example.com', $servers[2]->getPluginOpts());

        // SIP008 转回 SIP002 列表
        $convertedConfigs = $sip008->toSIP002List();
        $this->assertCount(3, $convertedConfigs);

        // 验证第一个转换后的配置
        $convertedConfig1 = $convertedConfigs[0]->getConfig();
        $this->assertEquals('server1.example.com', $convertedConfig1->getServer());
        $this->assertEquals(8388, $convertedConfig1->getServerPort());
        $this->assertEquals('password1', $convertedConfig1->getPassword());
        $this->assertEquals('aes-256-gcm', $convertedConfig1->getMethod());
        $this->assertEquals('Server 1', $convertedConfig1->getTag());
        $this->assertEquals('v2ray-plugin;server', $convertedConfigs[0]->getPlugin());

        // 验证第二个转换后的配置
        $convertedConfig2 = $convertedConfigs[1]->getConfig();
        $this->assertEquals('server2.example.com', $convertedConfig2->getServer());
        $this->assertEquals(8389, $convertedConfig2->getServerPort());
        $this->assertEquals('password2', $convertedConfig2->getPassword());
        $this->assertEquals('2022-blake3-aes-256-gcm', $convertedConfig2->getMethod());
        $this->assertEquals('Server 2', $convertedConfig2->getTag());
        $this->assertNull($convertedConfigs[1]->getPlugin());

        // 验证第三个转换后的配置
        $convertedConfig3 = $convertedConfigs[2]->getConfig();
        $this->assertEquals('server3.example.com', $convertedConfig3->getServer());
        $this->assertEquals(8390, $convertedConfig3->getServerPort());
        $this->assertEquals('p@ssw0rd!', $convertedConfig3->getPassword());
        $this->assertEquals('chacha20-ietf-poly1305', $convertedConfig3->getMethod());
        $this->assertEquals('特殊 Server & Test', $convertedConfig3->getTag());
        $this->assertEquals('simple-obfs;obfs=http;obfs-host=www.example.com', $convertedConfigs[2]->getPlugin());
    }

    /**
     * 测试 JSON 序列化和反序列化的完整性
     */
    public function testJSONRoundTrip(): void
    {
        // 创建 SIP008 配置
        $sip008 = new SIP008();

        // 添加多个不同的服务器配置
        $server1 = new ServerConfig(
            '27b8a625-4f4b-4428-9f0f-8a2317db7c79',
            'server1.example.com',
            8388,
            'password1',
            'aes-256-gcm'
        );
        $server1->setRemarks('Server 1');
        $server1->setPlugin('v2ray-plugin');
        $server1->setPluginOpts('server');

        $server2 = new ServerConfig(
            '7842c068-c667-41f2-8f7d-04feece3cb67',
            'server2.example.com',
            8389,
            'password2',
            '2022-blake3-aes-256-gcm'
        );
        $server2->setRemarks('Server 2');

        $server3 = new ServerConfig(
            'a3b5c7d9-e1f2-3456-7890-123456789abc',
            'server3.example.com',
            8390,
            'p@ssw0rd!',
            'chacha20-ietf-poly1305'
        );
        $server3->setRemarks('特殊 Server & Test');
        $server3->setPlugin('simple-obfs');
        $server3->setPluginOpts('obfs=http;obfs-host=www.example.com');

        $sip008->addServer($server1)
            ->addServer($server2)
            ->addServer($server3)
            ->setBytesUsed(1000000000)
            ->setBytesRemaining(2000000000);

        // 转换为 JSON
        $json = $sip008->toJson();

        // 从 JSON 重新解析
        $reloadedSip008 = SIP008::fromJson($json);

        // 验证配置完整性
        $this->assertEquals($sip008->getVersion(), $reloadedSip008->getVersion());
        $this->assertEquals($sip008->getBytesUsed(), $reloadedSip008->getBytesUsed());
        $this->assertEquals($sip008->getBytesRemaining(), $reloadedSip008->getBytesRemaining());
        $this->assertCount(count($sip008->getServers()), $reloadedSip008->getServers());

        // 验证每个服务器
        $originalServers = $sip008->getServers();
        $reloadedServers = $reloadedSip008->getServers();

        foreach ($originalServers as $i => $server) {
            $reloadedServer = $reloadedServers[$i];

            $this->assertEquals($server->getId(), $reloadedServer->getId());
            $this->assertEquals($server->getServer(), $reloadedServer->getServer());
            $this->assertEquals($server->getServerPort(), $reloadedServer->getServerPort());
            $this->assertEquals($server->getPassword(), $reloadedServer->getPassword());
            $this->assertEquals($server->getMethod(), $reloadedServer->getMethod());
            $this->assertEquals($server->getRemarks(), $reloadedServer->getRemarks());
            $this->assertEquals($server->getPlugin(), $reloadedServer->getPlugin());
            $this->assertEquals($server->getPluginOpts(), $reloadedServer->getPluginOpts());
        }
    }
}
