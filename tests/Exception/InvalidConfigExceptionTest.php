<?php

namespace Shadowsocks\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Shadowsocks\Config\Exception\InvalidConfigException;

/**
 * InvalidConfigException 测试类
 */
class InvalidConfigExceptionTest extends TestCase
{
    /**
     * 测试异常能正常抛出
     */
    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('测试异常消息');
        
        throw new InvalidConfigException('测试异常消息');
    }
    
    /**
     * 测试异常继承关系
     */
    public function testExceptionExtendsInvalidArgumentException(): void
    {
        $exception = new InvalidConfigException('测试');
        
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
    
    /**
     * 测试异常代码
     */
    public function testExceptionWithCode(): void
    {
        $exception = new InvalidConfigException('测试', 123);
        
        $this->assertEquals(123, $exception->getCode());
        $this->assertEquals('测试', $exception->getMessage());
    }
}