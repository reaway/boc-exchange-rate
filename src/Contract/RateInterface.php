<?php

namespace BocExchangeRate\Contract;

interface RateInterface
{
    /**
     * 获取所有汇率
     *
     * @return array<string, float>
     */
    public function getRates(): array;

    /**
     * 获取指定货币的汇率
     *
     * @param string $currency
     *
     * @return float|null
     */
    public function getRate(string $currency): ?float;
}