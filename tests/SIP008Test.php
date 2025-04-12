<?php

namespace Shadowsocks\Config\Tests;

use PHPUnit\Framework\TestCase;
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\ServerConfig;
use Shadowsocks\Config\SIP002;
use Shadowsocks\Config\SIP008;

class SIP008Test extends TestCase
{
    /**
     * @var string SIP008 JSON 示例
     */
    private string $sampleJson = <<<JSON
{
    "version": 1,
    "servers": [
        {
            "id": "27b8a625-4f4b-4428-9f0f-8a2317db7c79",
            "remarks": "Server 1",
            "server": "example1.com",
            "server_port": 8388,
            "password": "password1",
            "method": "aes-256-gcm",
            "plugin": "v2ray-plugin",
            "plugin_opts": "server"
        },
        {
            "id": "7842c068-c667-41f2-8f7d-04feece3cb67",
            "remarks": "Server 2",
            "server": "example2.com",
            "server_port": 8389,
            "password": "password2",
            "method": "chacha20-ietf-poly1305"
        }
    ],
    "bytes_used": 274877906944,
    "bytes_remaining": 824633720832
}
JSON;

    public function testFromJson(): void
    {
        $sip008 = SIP008::fromJson($this->sampleJson);

        $this->assertEquals(1, $sip008->getVersion());
        $this->assertEquals(274877906944, $sip008->getBytesUsed());
        $this->assertEquals(824633720832, $sip008->getBytesRemaining());

        $servers = $sip008->getServers();
        $this->assertCount(2, $servers);

        $server1 = $servers[0];
        $this->assertEquals('27b8a625-4f4b-4428-9f0f-8a2317db7c79', $server1->getId());
        $this->assertEquals('Server 1', $server1->getRemarks());
        $this->assertEquals('example1.com', $server1->getServer());
        $this->assertEquals(8388, $server1->getServerPort());
        $this->assertEquals('password1', $server1->getPassword());
        $this->assertEquals('aes-256-gcm', $server1->getMethod());
        $this->assertEquals('v2ray-plugin', $server1->getPlugin());
        $this->assertEquals('server', $server1->getPluginOpts());

        $server2 = $servers[1];
        $this->assertEquals('7842c068-c667-41f2-8f7d-04feece3cb67', $server2->getId());
        $this->assertEquals('Server 2', $server2->getRemarks());
        $this->assertEquals('example2.com', $server2->getServer());
        $this->assertEquals(8389, $server2->getServerPort());
        $this->assertEquals('password2', $server2->getPassword());
        $this->assertEquals('chacha20-ietf-poly1305', $server2->getMethod());
        $this->assertNull($server2->getPlugin());
        $this->assertNull($server2->getPluginOpts());
    }

    public function testFromJsonInvalidFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SIP008::fromJson('{invalid json}');
    }

    public function testFromJsonMissingVersion(): void
    {
        $json = <<<JSON
{
    "servers": []
}
JSON;
        $this->expectException(\InvalidArgumentException::class);
        SIP008::fromJson($json);
    }

    public function testFromJsonMissingServers(): void
    {
        $json = <<<JSON
{
    "version": 1
}
JSON;
        $this->expectException(\InvalidArgumentException::class);
        SIP008::fromJson($json);
    }

    public function testToJson(): void
    {
        $sip008 = SIP008::fromJson($this->sampleJson);
        $json = $sip008->toJson();

        $decodedJson = json_decode($json, true);
        $this->assertEquals(1, $decodedJson['version']);
        $this->assertCount(2, $decodedJson['servers']);
        $this->assertEquals(274877906944, $decodedJson['bytes_used']);
        $this->assertEquals(824633720832, $decodedJson['bytes_remaining']);
    }

    public function testFromConfig(): void
    {
        $config = new ClientConfig('example.com', 8388, 1080, 'password', 'aes-256-gcm');
        $config->setTag('Test Server');
        $sip008 = SIP008::fromConfig($config, 'v2ray-plugin', 'server');

        $this->assertEquals(1, $sip008->getVersion());
        $this->assertCount(1, $sip008->getServers());

        $server = $sip008->getServers()[0];
        $this->assertNotEmpty($server->getId()); // ID应该被自动生成
        $this->assertEquals('Test Server', $server->getRemarks());
        $this->assertEquals('example.com', $server->getServer());
        $this->assertEquals(8388, $server->getServerPort());
        $this->assertEquals('password', $server->getPassword());
        $this->assertEquals('aes-256-gcm', $server->getMethod());
        $this->assertEquals('v2ray-plugin', $server->getPlugin());
        $this->assertEquals('server', $server->getPluginOpts());
    }

    public function testFromSIP002(): void
    {
        $config = new ClientConfig('example.com', 8388, 1080, 'password', 'aes-256-gcm');
        $config->setTag('Test Server');

        $sip002 = new SIP002($config);
        $sip002->setPlugin('v2ray-plugin;server');

        $sip008 = SIP008::fromSIP002($sip002);

        $this->assertEquals(1, $sip008->getVersion());
        $this->assertCount(1, $sip008->getServers());

        $server = $sip008->getServers()[0];
        $this->assertNotEmpty($server->getId());
        $this->assertEquals('Test Server', $server->getRemarks());
        $this->assertEquals('example.com', $server->getServer());
        $this->assertEquals(8388, $server->getServerPort());
        $this->assertEquals('password', $server->getPassword());
        $this->assertEquals('aes-256-gcm', $server->getMethod());
        $this->assertEquals('v2ray-plugin', $server->getPlugin());
        $this->assertEquals('server', $server->getPluginOpts());
    }

    public function testFromSIP002List(): void
    {
        $config1 = new ClientConfig('example1.com', 8388, 1080, 'password1', 'aes-256-gcm');
        $config1->setTag('Server 1');
        $sip002_1 = new SIP002($config1);
        $sip002_1->setPlugin('v2ray-plugin;server');

        $config2 = new ClientConfig('example2.com', 8389, 1080, 'password2', 'chacha20-ietf-poly1305');
        $config2->setTag('Server 2');
        $sip002_2 = new SIP002($config2);

        $sip008 = SIP008::fromSIP002List([$sip002_1, $sip002_2]);

        $this->assertEquals(1, $sip008->getVersion());
        $this->assertCount(2, $sip008->getServers());

        $servers = $sip008->getServers();

        $this->assertEquals('Server 1', $servers[0]->getRemarks());
        $this->assertEquals('example1.com', $servers[0]->getServer());
        $this->assertEquals('v2ray-plugin', $servers[0]->getPlugin());

        $this->assertEquals('Server 2', $servers[1]->getRemarks());
        $this->assertEquals('example2.com', $servers[1]->getServer());
        $this->assertNull($servers[1]->getPlugin());
    }

    public function testToSIP002List(): void
    {
        $sip008 = SIP008::fromJson($this->sampleJson);
        $sip002List = $sip008->toSIP002List();

        $this->assertCount(2, $sip002List);

        $sip002_1 = $sip002List[0];
        $config1 = $sip002_1->getConfig();
        $this->assertEquals('example1.com', $config1->getServer());
        $this->assertEquals(8388, $config1->getServerPort());
        $this->assertEquals('password1', $config1->getPassword());
        $this->assertEquals('aes-256-gcm', $config1->getMethod());
        $this->assertEquals('Server 1', $config1->getTag());
        $this->assertEquals('v2ray-plugin;server', $sip002_1->getPlugin());

        $sip002_2 = $sip002List[1];
        $config2 = $sip002_2->getConfig();
        $this->assertEquals('example2.com', $config2->getServer());
        $this->assertEquals(8389, $config2->getServerPort());
        $this->assertEquals('password2', $config2->getPassword());
        $this->assertEquals('chacha20-ietf-poly1305', $config2->getMethod());
        $this->assertEquals('Server 2', $config2->getTag());
        $this->assertNull($sip002_2->getPlugin());
    }

    public function testToSIP002ListFromEmptySIP008(): void
    {
        $sip008 = new SIP008();
        $sip002List = $sip008->toSIP002List();

        $this->assertCount(0, $sip002List);
    }

    public function testAddServer(): void
    {
        $sip008 = new SIP008();
        $this->assertCount(0, $sip008->getServers());

        $server = new ServerConfig(
            '27b8a625-4f4b-4428-9f0f-8a2317db7c79',
            'example.com',
            8388,
            'password',
            'aes-256-gcm'
        );

        $sip008->addServer($server);
        $this->assertCount(1, $sip008->getServers());

        // 测试相同ID不会重复添加
        $sip008->addServer($server);
        $this->assertCount(1, $sip008->getServers());

        // 测试不同ID可以添加
        $server2 = new ServerConfig(
            '7842c068-c667-41f2-8f7d-04feece3cb67',
            'example2.com',
            8389,
            'password2',
            'chacha20-ietf-poly1305'
        );

        $sip008->addServer($server2);
        $this->assertCount(2, $sip008->getServers());

        // 添加第三个服务器，使用不同的ID但相同的服务器信息
        $server3 = new ServerConfig(
            'a3b5c7d9-e1f2-3456-7890-123456789abc',
            'example.com',
            8388,
            'password',
            'aes-256-gcm'
        );
        $sip008->addServer($server3);
        $this->assertCount(3, $sip008->getServers());

        // 测试相同服务器不同ID会重复添加
        $server4 = clone $server3;
        $reflectionProperty = new \ReflectionProperty(ServerConfig::class, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($server4, 'different-id-for-test');

        $sip008->addServer($server4);
        $this->assertCount(4, $sip008->getServers());
    }

    public function testMultipleAddServerWithSameId(): void
    {
        $sip008 = new SIP008();

        // 创建5个具有相同ID的服务器配置
        $id = '27b8a625-4f4b-4428-9f0f-8a2317db7c79';

        for ($i = 0; $i < 5; $i++) {
            $server = new ServerConfig(
                $id,
                "example{$i}.com",
                8388 + $i,
                "password{$i}",
                'aes-256-gcm'
            );

            $sip008->addServer($server);
        }

        // 应该只添加第一个服务器
        $this->assertCount(1, $sip008->getServers());
        $servers = $sip008->getServers();
        $this->assertEquals('example0.com', $servers[0]->getServer());
        $this->assertEquals(8388, $servers[0]->getServerPort());
    }

    public function testFromInvalidJson(): void
    {
        $invalidJson = '{invalid json}';
        $this->expectException(\InvalidArgumentException::class);
        SIP008::fromJson($invalidJson);
    }

    public function testFromUrl(): void
    {
        // 这个测试需要模拟HTTP请求，在这里我们只测试URL验证
        $this->expectException(\InvalidArgumentException::class);
        SIP008::fromUrl('invalid-url');

        $this->expectException(\InvalidArgumentException::class);
        SIP008::fromUrl('http://example.com/config.json'); // 不是HTTPS
    }

    public function testGetters(): void
    {
        $sip008 = new SIP008();

        // 测试初始状态
        $this->assertEquals(1, $sip008->getVersion());
        $this->assertEmpty($sip008->getServers());
        $this->assertNull($sip008->getBytesUsed());
        $this->assertNull($sip008->getBytesRemaining());

        // 测试添加服务器后的状态
        $server = new ServerConfig(
            '27b8a625-4f4b-4428-9f0f-8a2317db7c79',
            'example.com',
            8388,
            'password',
            'aes-256-gcm'
        );

        $sip008->addServer($server);
        $this->assertCount(1, $sip008->getServers());

        // 测试设置流量信息
        $sip008->setBytesUsed(1000);
        $sip008->setBytesRemaining(2000);

        $this->assertEquals(1000, $sip008->getBytesUsed());
        $this->assertEquals(2000, $sip008->getBytesRemaining());
    }

    public function testToJsonWithAllFields(): void
    {
        $sip008 = new SIP008();

        $server1 = new ServerConfig(
            '27b8a625-4f4b-4428-9f0f-8a2317db7c79',
            'example1.com',
            8388,
            'password1',
            'aes-256-gcm'
        );
        $server1->setRemarks('Server 1');
        $server1->setPlugin('v2ray-plugin');
        $server1->setPluginOpts('server');

        $server2 = new ServerConfig(
            '7842c068-c667-41f2-8f7d-04feece3cb67',
            'example2.com',
            8389,
            'password2',
            'chacha20-ietf-poly1305'
        );
        $server2->setRemarks('Server 2');

        $sip008->addServer($server1);
        $sip008->addServer($server2);
        $sip008->setBytesUsed(1000);
        $sip008->setBytesRemaining(2000);

        $json = $sip008->toJson();
        $data = json_decode($json, true);

        $this->assertEquals(1, $data['version']);
        $this->assertCount(2, $data['servers']);
        $this->assertEquals(1000, $data['bytes_used']);
        $this->assertEquals(2000, $data['bytes_remaining']);

        // 验证第一个服务器
        $serverData1 = $data['servers'][0];
        $this->assertEquals('27b8a625-4f4b-4428-9f0f-8a2317db7c79', $serverData1['id']);
        $this->assertEquals('Server 1', $serverData1['remarks']);
        $this->assertEquals('example1.com', $serverData1['server']);
        $this->assertEquals(8388, $serverData1['server_port']);
        $this->assertEquals('password1', $serverData1['password']);
        $this->assertEquals('aes-256-gcm', $serverData1['method']);
        $this->assertEquals('v2ray-plugin', $serverData1['plugin']);
        $this->assertEquals('server', $serverData1['plugin_opts']);

        // 验证第二个服务器
        $serverData2 = $data['servers'][1];
        $this->assertEquals('7842c068-c667-41f2-8f7d-04feece3cb67', $serverData2['id']);
        $this->assertEquals('Server 2', $serverData2['remarks']);
        $this->assertEquals('example2.com', $serverData2['server']);
        $this->assertEquals(8389, $serverData2['server_port']);
        $this->assertEquals('password2', $serverData2['password']);
        $this->assertEquals('chacha20-ietf-poly1305', $serverData2['method']);
        $this->assertArrayNotHasKey('plugin', $serverData2);
        $this->assertArrayNotHasKey('plugin_opts', $serverData2);
    }

    public function testSetAndGetBytesInformation(): void
    {
        $sip008 = new SIP008();

        $this->assertNull($sip008->getBytesUsed());
        $this->assertNull($sip008->getBytesRemaining());

        $sip008->setBytesUsed(1000);
        $sip008->setBytesRemaining(2000);

        $this->assertEquals(1000, $sip008->getBytesUsed());
        $this->assertEquals(2000, $sip008->getBytesRemaining());
    }
}
