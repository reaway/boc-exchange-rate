<?php

namespace BocExchangeRate;

use Exception;
use BocExchangeRate\Contract\RateInterface;
use BocExchangeRate\Http\CurlClient;

abstract class Driver implements RateInterface
{
    /** HTTP客户端 */
    protected CurlClient $httpClient;

    /** 响应内容 */
    protected string $response = '';

    /** 数据来源URL */
    protected string $sourceUrl = '';

    /** 货币代码映射 */
    protected array $currencies = [
        'AED' => '阿联酋迪拉姆',
        'AUD' => '澳大利亚元',
        'BND' => '文莱元',
        'BRL' => '巴西雷亚尔',
        'CAD' => '加拿大元',
        'CHF' => '瑞士法郎',
        'CZK' => '捷克克朗',
        'DKK' => '丹麦克朗',
        'EUR' => '欧元',
        'GBP' => '英镑',
        'HKD' => '港币',
        'HUF' => '匈牙利福林',
        'IDR' => '印尼卢比',
        'ILS' => '以色列谢克尔',
        'INR' => '印度卢比',
        'JPY' => '日元',
        'KHR' => '柬埔寨瑞尔',
        'KRW' => '韩国元',
        'KWD' => '科威特第纳尔',
        'MNT' => '蒙古图格里克',
        'MOP' => '澳门元',
        'MXN' => '墨西哥比索',
        'MYR' => '林吉特',
        'NOK' => '挪威克朗',
        'NPR' => '尼泊尔卢比',
        'NZD' => '新西兰元',
        'PHP' => '菲律宾比索',
        'PKR' => '巴基斯坦卢比',
        'QAR' => '卡塔尔里亚尔',
        'RSD' => '塞尔维亚第纳尔',
        'RUB' => '卢布',
        'SAR' => '沙特里亚尔',
        'SEK' => '瑞典克朗',
        'SGD' => '新加坡元',
        'THB' => '泰国铢',
        'TRY' => '土耳其里拉',
        'TWD' => '新台币',
        'USD' => '美元',
        'VND' => '越南盾',
        'ZAR' => '南非兰特',
    ];

    /** 汇率数据缓存，键为货币代码，值为汇率 */
    protected ?array $rates = null;

    /** 配置参数 */
    protected array $config = [];

    /**
     * 构造函数
     *
     * @param CurlClient $httpClient HTTP客户端
     * @param ?array $config 配置参数
     */
    public function __construct(CurlClient $httpClient, ?array $config = null)
    {
        $this->httpClient = $httpClient;
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * 获取响应内容
     *
     * @return string 响应内容
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * 获取汇率数据来源URL
     *
     * @return string 汇率数据来源URL
     */
    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }

    /**
     * 根据货币名称查找货币代码
     *
     * @param string $name 货币名称
     *
     * @return string|null 货币代码
     */
    protected function findCurrencyCode(string $name): ?string
    {
        $key = array_search($name, $this->currencies);
        return $key !== false ? $key : null;
    }

    /**
     * 根据货币代码查找货币名称
     *
     * @param string $code 货币代码
     *
     * @return string|null 货币名称
     */
    protected function findCurrencyName(string $code): ?string
    {
        return $this->currencies[$code] ?? null;
    }

    /**
     * 获取内容
     *
     * @return string 内容
     * @throws Exception
     */
    abstract public function fetchContent(): string;

    /**
     * 匹配数据
     *
     * @return array 匹配到的数据
     * @throws Exception
     */
    abstract public function parseData(): array;

    /**
     * 格式化数据
     *
     * @param array $data 匹配到的数据
     *
     * @return array 格式化后的数据数组
     * @throws Exception
     */
    abstract public function formatData(array $data): array;

    /**
     * 获取汇率数据
     *
     * @return array 汇率数据
     * @throws Exception
     */
    public function fetchData(): array
    {
        $this->response = $this->fetchContent();
        $data = $this->parseData();
        return $this->formatData($data);
    }

    /**
     * 获取所有汇率
     *
     * 如果尚未获取数据，会自动调用fetchData()获取。
     *
     * @return array<string, float> 货币代码 => 汇率的关联数组
     * @throws Exception 当获取汇率数据时发生错误时抛出
     */
    public function getRates(): array
    {
        if ($this->rates === null) {
            $this->rates = $this->fetchData();
        }

        return $this->rates ?? [];
    }

    /**
     * 获取指定货币的汇率
     *
     * @param string $currency 货币代码（如 EUR、CNY、JPY），不区分大小写
     *
     * @return float|null 汇率值（相对于 USD），如果货币不存在则返回 null
     * @throws Exception 当获取汇率数据时发生错误时抛出
     */
    public function getRate(string $currency): ?float
    {
        $currency = strtoupper($currency);
        $rates = $this->getRates();

        return $rates[$currency] ?? null;
    }
}