<?php

namespace Webbhuset\CollectorCheckout\Helper;

/**
 * Class GetSkuSuffix
 *
 * Helper class to generate unique SKU suffixes to avoid duplicates
 *
 * @package Webbhuset\CollectorCheckout\Helper
 */
class GetSkuSuffix
{
    /**
     * @var array
     */
    private array $skus = [];

    /**
     * Get a unique suffix for the given SKU
     *
     * @param string $sku
     * @return string
     */
    public function execute(string $sku): string
    {
        $suffix = '';
        $index = 0;
        while (isset($this->skus[$sku . $suffix])) {
            $index++;
            $suffix = '-' . $index;
        }
        $this->skus[$sku . $suffix] = 1;
        return $suffix;
    }

    /**
     * Reset the SKU tracking array
     *
     * @return void
     */
    public function reset(): void
    {
        $this->skus = [];
    }
}

