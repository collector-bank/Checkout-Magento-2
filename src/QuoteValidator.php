<?php

namespace Webbhuset\CollectorCheckout;

class QuoteValidator
{
    /**
     * Can we use Collector checkout for this quote?
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return boolean
     */
    public function canUseCheckout(\Magento\Quote\Model\Quote $quote)
    {
        $errors = $this->getErrors($quote);

        return empty($errors);
    }

    /**
     * Get reasons we cannot use Collector checkout for this quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    public function getErrors(\Magento\Quote\Model\Quote $quote)
    {
        $errors = [];

        if (!$quote->hasItems()) {
            $errors[] = __('Quote is empty');
        }

        return $errors;
    }
}
