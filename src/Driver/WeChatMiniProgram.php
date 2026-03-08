<?php

namespace BocExchangeRate\Driver;

use Exception;
use BocExchangeRate\Driver;

class WeChatMiniProgram extends Driver
{
    /**
     * 源数据url
     */
    protected string $sourceUrl = 'https://ccsa.ebsnew.boc.cn/BMPS/_bfwajax.do?_locale=zh_CN';

    /**
     * 获取微信小程序汇率数据
     *
     * @return string 汇率数据
     * @throws Exception
     */
    public function fetchContent(): string
    {
        $params = [
            'header' =>
                [
                    'agent' => 'WEIXIN',
                    'version' => '1.0',
                    'device' => 'wxd',
                    'platform' => 'wxp',
                    'plugins' => '',
                    'page' => '01',
                    'local' => 'zh_CN',
                    'uuid' => 'wertyuiuytr',
                    'ext' => '',
                    'cipherType' => '0',
                ],
            'method' => 'PsnGetExchangeOutlay',
            'params' => [],
        ];
        $result = $this->httpClient->post($this->sourceUrl, ['json' => json_encode($params, JSON_FORCE_OBJECT)]);
        return $result['body'];
    }

    /**
     * 解析微信小程序汇率数据
     * 
     * @return array 解析后的数据
     * @throws Exception
     */
    public function parseData(): array
    {
        $body = $this->getResponse();
        if (empty($body)) {
            throw new Exception('返回数据空');
        }
        $data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('返回数据格式错误');
        }
        if ($data['_isException_']) {
            throw new Exception('返回数据异常');
        }
        return $data;
    }

    /**
     * 格式化数据
     *
     * @param array $data 解析后的数据
     *
     * @return array 格式化后的数据数组
     * @throws Exception
     */
    public function formatData(array $data): array
    {
        $rates = [];
        foreach ($data['result'] as $quot) {
            $code = $quot['curCode'];
            $name = $this->findCurrencyName($code);
            if (empty($name)) {
                continue;
            }
            $rates[$code] = [
                'currency_name' => $name,
                'currency_code' => $code,
                'exchange_buy' => empty($quot['buyRate']) ? null : (float)bcdiv($quot['buyRate'], 100, 6),
                'exchange_sell' => empty($quot['sellRate']) ? null : (float)bcdiv($quot['sellRate'], 100, 6),
                'cash_buy' => empty($quot['buyNoteRate']) ? null : (float)bcdiv($quot['buyNoteRate'], 100, 6),
                'cash_sell' => empty($quot['sellNoteRate']) ? null : (float)bcdiv($quot['sellNoteRate'], 100, 6),
                'bank_conversion' => 0,
                'publish_time' => $quot['updateDate']
            ];
        }
        return $rates;
    }
}