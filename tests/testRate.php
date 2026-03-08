<?php

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
dump($rate->getRates());
dump($rate->setDriver('desktop')->getRates());
// dump($rate->setDriver('mobile')->getRates());
// dump($rate->setDriver('we_chat_mini_program')->getRates());
dump($rate);