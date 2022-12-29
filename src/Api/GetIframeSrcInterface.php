<?php


namespace Webbhuset\CollectorCheckout\Api;


interface GetIframeSrcInterface
{
    /**
     * @param string $cartId
     * @return \Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface
     */
    public function execute(string $cartId):\Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;
}
