<?php

namespace Shadowsocks\Config\Exception;

use InvalidArgumentException;

/**
 * 配置无效异常
 *
 * 当配置参数不符合要求时抛出此异常
 */
class InvalidConfigException extends InvalidArgumentException
{
}