<?php

namespace Webbhuset\CollectorCheckout\Logger;

class Logger extends \Magento\Framework\Logger\Monolog
{
    public function addCritical($message, $context)
    {
        parent::critical($message, $context);
    }
    public function addInfo($message, $context)
    {
        parent::info($message, $context);
    }
}
