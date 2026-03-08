<?php

namespace BocExchangeRate\Driver;

use Exception;
use BocExchangeRate\Driver;

class Mobile extends Driver
{
    /**
     * 源数据url
     */
    protected string $sourceUrl = 'https://www.boc.cn/sourcedb/whpj/sjmfx_1621.html';

    /**
     * 获取html内容
     *
     * @return string html内容
     * @throws Exception
     */
    public function fetchContent(): string
    {
        $result = $this->httpClient->get($this->sourceUrl);
        return $result['body'];
    }

    /**
     * 正则匹配html内容匹配数据
     * 
     * @return array 匹配到的汇率数据
     * @throws Exception
     */
    public function matchData(): array
    {
        $body = $this->getResponse();
        $pattern = '/<p class="sort_time clearfix">\s*(\d+\/\d+\/\d+)\s+(\d+:\d+:\d+)<\/p>/U';
        if (!preg_match($pattern, $body, $matches)) {
            throw new Exception('匹配汇率发布时间失败');
        }
        $date = $matches[1];
        $time = $matches[2];

        $pattern = '/';
        $pattern .= '<tr data-currency=\'(.*)\'>\s+';
        $pattern .= '<td>([A-Z]{3})\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<\/tr>\s+';
        $pattern .= '/U';
        if (!preg_match_all($pattern, $body, $matches)) {
            throw new Exception('匹配汇率失败');
        }

        $data = [];
        foreach ($matches[0] as $key => $value) {
            $data[] = [
                'currency_name' => $matches[1][$key],
                'currency_code' => $matches[2][$key],
                'exchange_buy' => $matches[3][$key],
                'cash_buy' => $matches[4][$key],
                'exchange_sell' => $matches[5][$key],
                'cash_sell' => $matches[6][$key],
                'date' => $date,
                'time' => $time
            ];
        }
        return $data;
    }

    /**
     * 解析html内容匹配数据
     * 
     * @return array 解析后的数据
     * @throws Exception
     */
    public function parseData(): array
    {
        $htmlContent = $this->getResponse();

        $pattern = '/<p class="sort_time clearfix">\s*(\d+\/\d+\/\d+)\s+(\d+:\d+:\d+)<\/p>/U';
        if (!preg_match($pattern, $htmlContent, $matches)) {
            throw new Exception('匹配汇率发布时间失败');
        }
        $date = $matches[1];
        $time = $matches[2];

        // 创建DOMDocument对象，并处理可能存在的HTML编码问题
        $dom = new \DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8')); // @用于抑制可能因HTML不规范产生的警告

        // 使用DOMDocument的getElementById方法（需要确保HTML有正确的DTD或已设置id属性）
        $tableNode = $dom->getElementById('priceTable');
        if (empty($tableNode)) {
            throw new Exception('使用ID:priceTable查找表格失败');
        }

        $data = [];
        // 获取表格中的所有行(tr)，跳过表头行（索引0）
        $rows = $tableNode->getElementsByTagName('tr');
        for ($i = 1; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            $name = $row->getAttribute('data-currency');
            if (empty($name)) {
                continue;
            }
            $cells = $row->getElementsByTagName('td');
            if (!($cells->length >= 5)) {
                continue;
            }
            $data[] = [
                'currency_name' => $name,
                'currency_code' => $cells->item(0)->nodeValue,
                'exchange_buy' => $cells->item(1)->nodeValue,
                'cash_buy' => $cells->item(2)->nodeValue,
                'exchange_sell' => $cells->item(3)->nodeValue,
                'cash_sell' => $cells->item(4)->nodeValue,
                'date' => $date,
                'time' => $time
            ];
        }
        return $data;
    }


    /**
     * 格式化数据
     *
     * @param array $data
     *
     * @return array
     * @throws Exception
     */
    public function formatData(array $data): array
    {
        $rates = [];
        foreach ($data as $quot) {
            $rates[$quot['currency_code']] = [
                'currency_name' => $quot['currency_name'],
                'currency_code' => $quot['currency_code'],
                'exchange_buy' => empty($quot['exchange_buy']) ? null : (float)bcdiv(($quot['exchange_buy']), 100, 6),
                'exchange_sell' => empty($quot['exchange_sell']) ? null : (float)bcdiv(($quot['exchange_sell']), 100, 6),
                'cash_buy' => empty($quot['cash_buy']) ? null : (float)bcdiv(($quot['cash_buy']), 100, 6),
                'cash_sell' => empty($quot['cash_sell']) ? null : (float)bcdiv(($quot['cash_sell']), 100, 6),
                'publish_time' => str_replace('.', '-', $quot['date']) . ' ' . $quot['time']
            ];
        }
        return $rates;
    }
}