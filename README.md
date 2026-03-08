# BocExchangeRate

## 安装
```bash
composer require reaway/boc-exchange-rate
```

## 使用门面（Facade）
```php
use BocExchangeRate\Facade\Rate;
use Think\Component\Config\Facade\Config;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = [
    'default' => 'desktop', // 默认驱动
    'drivers' => [
        'juhe' => [
            'key' => '8888', // API访问密钥
        ],
    ],
];
Config::set($config, 'boc_exchange_rate');

$rates = Rate::getRates();
var_dump($rates);
```

## 使用
```php
use BocExchangeRate\Rate;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = [
    'default' => 'desktop', // 默认驱动
    'drivers' => [
        'juhe' => [
            'key' => '666666', // API访问密钥
        ],
    ],
];

$rate = new Rate($config);
$rates = $rate->getRates();
var_dump($rates);
```