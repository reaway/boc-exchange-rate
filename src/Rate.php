<?php

namespace BocExchangeRate;

use think\helper\Arr;
use Think\Component\Manager\Manager;
use Think\Component\Config\Config;

class Rate extends Manager
{
    // 驱动的命名空间
    protected $namespace = '\\BocExchangeRate\\Driver\\';

    // 配置
    protected $config = [
        'default' => 'desktop', // 默认驱动
        'drivers' => [
            'juhe' => [
                'key' => '', // API访问密钥
            ],
        ],
    ];

    public static function __make(Config $config)
    {
        return new static($config->get('boc_exchange_rate'));
    }

    /**
     * 获取默认驱动名称
     *
     * @return string|null 默认驱动名称
     */
    public function getDefaultDriver(): ?string
    {
        return $this->config['default'];
    }

    /**
     * 获取驱动配置
     *
     * @param string $store 驱动名称
     *
     * @return array|null 驱动配置
     */
    public function getDriverConfig(string $store, ?string $name = null, $default = null): ?array
    {
        if (!key_exists($store, $this->config['drivers'])) {
            return null;
        }

        return Arr::get($this->config['drivers'][$store], $name, $default);
    }

    /**
     * 设置默认驱动
     *
     * @param string|null $name 驱动名称
     *
     * @return Driver 驱动实例
     */
    public function setDriver(?string $name = null): Driver
    {
        return $this->driver($name);
    }

    /**
     * 解析驱动配置
     *
     * @param string $name 驱动名称
     *
     * @return array|null 驱动配置
     */
    protected function resolveConfig(string $name): ?array
    {
        return $this->getDriverConfig($name);
    }
}