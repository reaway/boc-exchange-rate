<?php
declare(strict_types=1);

namespace BocExchangeRate\Facade;

use Think\Component\Container\Facade;
use BocExchangeRate\Driver;

/**
 * Rate 面板
 * @package BocExchangeRate\Facade
 * @mixin \BocExchangeRate\Rate
 * @method static Driver setDriver(string $driver) 设置汇率驱动程序
 * @method static string getResponse() 获取响应内容
 * @method static string getSourceUrl() 获取数据来源URL
 * @method static array getRates() 获取所有汇率数据
 * @method static float getRate(string $currency) 获取指定货币的汇率
 */
class Rate extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass(): string
    {
        return 'BocExchangeRate\Rate';
    }
}