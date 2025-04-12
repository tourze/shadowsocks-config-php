<?php

namespace Shadowsocks\Config\Tests;

use PHPUnit\Framework\TestCase;
use Shadowsocks\Config\ClientConfig;
use Shadowsocks\Config\ServerConfig;

class ServerConfigTest extends TestCase
{
    public function testConstructor(): void
    {
        $id = '27b8a625-4f4b-4428-9f0f-8a2317db7c79';
        $server = 'example.com';
        $serverPort = 8388;
        $password = 'password';
        $method = 'aes-256-gcm';

        $serverConfig = new ServerConfig($id, $server, $serverPort, $password, $method);

        $this->assertEquals($id, $serverConfig->getId());
        $this->assertEquals($server, $serverConfig->getServer());
        $this->assertEquals($serverPort, $serverConfig->getServerPort());
        $this->assertEquals($password, $serverConfig->getPassword());
        $this->assertEquals($method, $serverConfig->getMethod());
        $this->assertNull($serverConfig->getRemarks());
        $this->assertNull($serverConfig->getPlugin());
        $this->assertNull($serverConfig->getPluginOpts());
    }

    public function testOptionalParameters(): void
    {
        $id = '27b8a625-4f4b-4428-9f0f-8a2317db7c79';
        $serverConfig = new ServerConfig($id, 'example.com', 8388, 'password', 'aes-256-gcm');
        
        $remarks = 'Test Server';
        $plugin = 'v2ray-plugin';
        $pluginOpts = 'server';
        
        $serverConfig->setRemarks($remarks);
        $serverConfig->setPlugin($plugin);
        $serverConfig->setPluginOpts($pluginOpts);
        
        $this->assertEquals($remarks, $serverConfig->getRemarks());
        $this->assertEquals($plugin, $serverConfig->getPlugin());
        $this->assertEquals($pluginOpts, $serverConfig->getPluginOpts());
    }
    
    public function testToConfig(): void
    {
        $id = '27b8a625-4f4b-4428-9f0f-8a2317db7c79';
        $server = 'example.com';
        $serverPort = 8388;
        $password = 'password';
        $method = 'aes-256-gcm';
        $remarks = 'Test Server';
        
        $serverConfig = new ServerConfig($id, $server, $serverPort, $password, $method);
        $serverConfig->setRemarks($remarks);
        
        $config = $serverConfig->toConfig();
        
        $this->assertInstanceOf(ClientConfig::class, $config);
        $this->assertEquals($server, $config->getServer());
        $this->assertEquals($serverPort, $config->getServerPort());
        $this->assertEquals(1080, $config->getLocalPort()); // 默认本地端口
        $this->assertEquals($password, $config->getPassword());
        $this->assertEquals($method, $config->getMethod());
        $this->assertEquals($remarks, $config->getTag());
    }
    
    public function testToJson(): void
    {
        $id = '27b8a625-4f4b-4428-9f0f-8a2317db7c79';
        $server = 'example.com';
        $serverPort = 8388;
        $password = 'password';
        $method = 'aes-256-gcm';
        $remarks = 'Test Server';
        $plugin = 'v2ray-plugin';
        $pluginOpts = 'server';
        
        $serverConfig = new ServerConfig($id, $server, $serverPort, $password, $method);
        $serverConfig->setRemarks($remarks);
        $serverConfig->setPlugin($plugin);
        $serverConfig->setPluginOpts($pluginOpts);
        
        $json = $serverConfig->toJson();
        $data = json_decode($json, true);
        
        $this->assertEquals($id, $data['id']);
        $this->assertEquals($server, $data['server']);
        $this->assertEquals($serverPort, $data['server_port']);
        $this->assertEquals($password, $data['password']);
        $this->assertEquals($method, $data['method']);
        $this->assertEquals($remarks, $data['remarks']);
        $this->assertEquals($plugin, $data['plugin']);
        $this->assertEquals($pluginOpts, $data['plugin_opts']);
    }
    
    public function testJsonWithoutOptionalFields(): void
    {
        $id = '27b8a625-4f4b-4428-9f0f-8a2317db7c79';
        $serverConfig = new ServerConfig($id, 'example.com', 8388, 'password', 'aes-256-gcm');
        
        $json = $serverConfig->toJson();
        $data = json_decode($json, true);
        
        $this->assertEquals($id, $data['id']);
        $this->assertArrayNotHasKey('remarks', $data);
        $this->assertArrayNotHasKey('plugin', $data);
        $this->assertArrayNotHasKey('plugin_opts', $data);
    }
    
    public function testFromJson(): void
    {
        $jsonStr = <<<JSON
{
    "id": "27b8a625-4f4b-4428-9f0f-8a2317db7c79",
    "remarks": "Test Server",
    "server": "example.com",
    "server_port": 8388,
    "password": "password",
    "method": "aes-256-gcm",
    "plugin": "v2ray-plugin",
    "plugin_opts": "server"
}
JSON;
        
        $serverConfig = ServerConfig::fromJson($jsonStr);
        
        $this->assertEquals('27b8a625-4f4b-4428-9f0f-8a2317db7c79', $serverConfig->getId());
        $this->assertEquals('Test Server', $serverConfig->getRemarks());
        $this->assertEquals('example.com', $serverConfig->getServer());
        $this->assertEquals(8388, $serverConfig->getServerPort());
        $this->assertEquals('password', $serverConfig->getPassword());
        $this->assertEquals('aes-256-gcm', $serverConfig->getMethod());
        $this->assertEquals('v2ray-plugin', $serverConfig->getPlugin());
        $this->assertEquals('server', $serverConfig->getPluginOpts());
    }
    
    public function testFromJsonMissingRequiredFields(): void
    {
        $jsonStr = <<<JSON
{
    "id": "27b8a625-4f4b-4428-9f0f-8a2317db7c79",
    "remarks": "Test Server"
}
JSON;
        
        $this->expectException(\InvalidArgumentException::class);
        ServerConfig::fromJson($jsonStr);
    }
}
