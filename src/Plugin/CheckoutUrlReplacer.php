<?php

namespace Webbhuset\CollectorCheckout\Plugin;

use Webbhuset\CollectorCheckout\Gateway\Config;

/**
 * Class CheckoutUrlReplacer
 *
 * @package Webbhuset\CollectorCheckout\Plugin
 */
class CheckoutUrlReplacer
{
    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config
     */
    protected $config;

    /**
     * CheckoutUrlReplacer constructor.
     *
     * @param \Webbhuset\CollectorCheckout\Config\Config $config
     */
    public function __construct(\Webbhuset\CollectorCheckout\Config\Config $config)
    {
        $this->config = $config;
    }

    /**
     * Plugin the changes the checkout url if collector bank checkout is active
     *
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param null                            $param1
     * @param null                            $params2
     * @return array
     */
    public function beforeGetUrl(
        \Magento\Framework\UrlInterface $urlInterface,
        $param1 = null,
        $params2 = null
    ) {
        if ($this->config->getIsActive()) {
            $param1 = ('checkout' == $param1) ? Config::CHECKOUT_URL_KEY : $param1;
        }

        return [$param1, $params2];
    }
}
