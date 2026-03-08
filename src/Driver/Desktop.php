<?php

namespace BocExchangeRate\Driver;

use Exception;
use BocExchangeRate\Driver;

class Desktop extends Driver
{
    /**
     * 源数据url
     */
    protected string $sourceUrl = 'https://www.boc.cn/sourcedb/whpj/';

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
     * @return array
     * @throws Exception
     */
    public function matchData(): array
    {
        $body = $this->getResponse();
        $pattern = '/';
        $pattern .= '<tr data-currency=\'(.*)\'>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<td class="pjrq">(.*)\s*<\/td>\s+';
        $pattern .= '<td>(.*)\s*<\/td>\s+';
        $pattern .= '<\/tr>\s+';
        $pattern .= '/U';
        if (preg_match_all($pattern, $body, $matches) === false) {
            throw new Exception('匹配汇率失败');
        }

        $data = [];
        foreach ($matches[0] as $key => $value) {
            $data[] = [
                'currency_name' => $matches[2][$key],
                'exchange_buy' => $matches[3][$key],
                'cash_buy' => $matches[4][$key],
                'exchange_sell' => $matches[5][$key],
                'cash_sell' => $matches[6][$key],
                'bank_conversion' => $matches[7][$key],
                'date' => substr($matches[8][$key], 0, 10),
                'time' => $matches[9][$key]
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
            $cells = $row->getElementsByTagName('td');
            if (!($cells->length >= 5)) {
                continue;
            }
            $currency = trim($cells->item(0)->nodeValue);
            // 跳过非数据行（例如分页信息“共10页”等）
            if (empty($currency) || is_numeric($currency)) {
                continue;
            }
            $data[] = [
                'currency_name' => $currency,
                'exchange_buy' => $cells->item(1)->nodeValue,
                'cash_buy' => $cells->item(2)->nodeValue,
                'exchange_sell' => $cells->item(3)->nodeValue,
                'cash_sell' => $cells->item(4)->nodeValue,
                'bank_conversion' => $cells->item(5)->nodeValue,
                'date' => substr(trim($cells->item(6)->nodeValue), 0, 10), // 根据您提供的数据，发布日期在第7列
                'time' => trim($cells->item(7)->nodeValue)  // 发布时间在第8列
            ];
        }
        return $data;
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
            $code = $this->findCurrencyCode($quot['currency_name']);
            if (empty($code)) {
                continue;
            }
            $rates[$code] = [
                'currency_name' => $quot['currency_name'],
                'currency_code' => $code,
                'exchange_buy' => empty($quot['exchange_buy']) ? null : (float)bcdiv(($quot['exchange_buy']), 100, 6),
                'exchange_sell' => empty($quot['exchange_sell']) ? null : (float)bcdiv(($quot['exchange_sell']), 100, 6),
                'cash_buy' => empty($quot['cash_buy']) ? null : (float)bcdiv(($quot['cash_buy']), 100, 6),
                'cash_sell' => empty($quot['cash_sell']) ? null : (float)bcdiv(($quot['cash_sell']), 100, 6),
                'bank_conversion' => empty($quot['bank_conversion']) ? null : (float)bcdiv((float)($quot['bank_conversion']), 100, 6),
                'publish_time' => str_replace('.', '-', $quot['date']) . ' ' . $quot['time']
            ];
        }
        return $rates;
    }
}