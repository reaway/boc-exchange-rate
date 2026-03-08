<?php

namespace BocExchangeRate\Driver;

use Exception;
use BocExchangeRate\Driver;

class Juhe extends Driver
{
    /**
     * 数据来源URL
     */
    protected string $sourceUrl = 'http://web.juhe.cn/finance/exchange/rmbquot';

    /**
     * 配置项
     */
    protected array $config = [
        'key' => '',
    ];

    /**
     * 获取汇率数据
     *
     * @return string 汇率数据
     * @throws Exception
     */
    public function fetchContent(): string
    {
        $params = [
            'key' => $this->config['key'], // API访问密钥
            'type' => '0' // 两种格式(0或者1,默认为0)
        ];
        $result = $this->httpClient->post($this->sourceUrl, $params);
        return $result['body'];
    }

    /**
     * 解析汇率数据
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
        if ($data['error_code'] != 0) {
            throw new Exception($data['reason']);
        }
        return $data['result'][0] ?? [];
    }

    /**
     * 格式化数据
     *
     * @param array $data 解析后的数据
     *
     * @return array 格式化后的数据
     * @throws Exception
     */
    public function formatData(array $data): array
    {
        $rates = [];
        foreach ($data as $quot) {
            $code = $this->findCurrencyCode($quot['name']);
            if (empty($code)) {
                continue;
            }
            $rates[$code] = [
                'name' => $quot['name'],
                'code' => $code,
                'exchange_buy' => empty($quot['fBuyPri']) ? null : (float)bcdiv($quot['fBuyPri'], 100, 6),
                'exchange_sell' => empty($quot['fSellPri']) ? null : (float)bcdiv($quot['fSellPri'], 100, 6),
                'cash_buy' => empty($quot['mBuyPri']) ? null : (float)bcdiv($quot['mBuyPri'], 100, 6),
                'cash_sell' => empty($quot['mSellPri']) ? null : (float)bcdiv($quot['mSellPri'], 100, 6),
                'bank_conversion' => empty($quot['bankConversionPri']) ? null : (float)bcdiv($quot['bankConversionPri'], 100, 6),
                'publish_time' => $quot['date'] . ' ' . $quot['time']
            ];
        }
        return $rates;
    }
}