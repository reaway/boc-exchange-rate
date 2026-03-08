<?php

use BocExchangeRate\Facade\Rate;
use Think\Component\Config\Facade\Config;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = [
    'default' => 'juhe', // 默认驱动
    'drivers' => [
        'juhe' => [
            'key' => '888888', // API访问密钥
        ],
    ],
];
Config::set($config, 'boc_exchange_rate');

dump(Rate::getRates());
dump(Rate::setDriver('desktop')->getRates());
// dump(Rate::setDriver('mobile')->getRates());
// dump(Rate::setDriver('we_chat_mini_program')->getRates());
dump(container());