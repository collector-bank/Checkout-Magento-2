<?php

namespace Webbhuset\CollectorCheckout\Block\Checkout;

use Webbhuset\CollectorCheckout\Config\Source\Customer\DefaultType as CustomerType;
use Webbhuset\CollectorCheckout\Config\Source\Customer\Type as AllowedCustomerType;

/**
 * Class CustomerTypeSwitcher
 *
 * @package Webbhuset\CollectorCheckout\Block\Checkout
 */
class CustomerTypeSwitcher extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config
     */
    protected $config;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Webbhuset\CollectorCheckout\Data\QuoteHandler
     */
    protected $quoteDataHandler;

    /**
     * CustomerTypeSwitcher constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param \Webbhuset\CollectorCheckout\Config\Config        $config
     * @param \Magento\Checkout\Model\Session                   $checkoutSession
     * @param \Webbhuset\CollectorCheckout\Data\QuoteHandler    $quoteDataHandler
     * @param array                                             $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->quoteDataHandler = $quoteDataHandler;
    }

    /**
     * Returns the url to the checkout for business customers.
     *
     * @return string
     */
    public function getBusinessCheckoutUrl()
    {
        return $this->config->getCheckoutUrl() . '?customerType=' . CustomerType::BUSINESS_CUSTOMERS;
    }

    /**
     * Returns the url to the checkout for private customers.
     *
     * @return string
     */
    public function getPrivateCheckoutUrl()
    {
        return $this->config->getCheckoutUrl() . '?customerType=' . CustomerType::PRIVATE_CUSTOMERS;
    }

    /**
     * Returns the customer type that is set on the quote, falls back on default customer type set in admin if quote
     * has no customer type specified.
     *
     * @return int
     */
    public function getCustomerType()
    {
        $quote = $this->checkoutSession->getQuote();

        $quoteCustomerType = $this->quoteDataHandler->getCustomerType($quote);

        return $quoteCustomerType ?: $this->config->getDefaultCustomerType();
    }

    /**
     * Returns an array with data to render in the private / business customer switch
     *
     * @return array
     */
    public function getAllowedCustomerTypesData()
    {
        $allowedCustomerTypes = $this->config->getCustomerTypeAllowed();
        $allowed = [];

        $allowed[AllowedCustomerType::PRIVATE_CUSTOMERS]['checkoutUrl'] = $this->getPrivateCheckoutUrl();
        $allowed[AllowedCustomerType::PRIVATE_CUSTOMERS]['title'] = __('Private');

        $allowed[AllowedCustomerType::BUSINESS_CUSTOMERS]['checkoutUrl'] = $this->getBusinessCheckoutUrl();
        $allowed[AllowedCustomerType::BUSINESS_CUSTOMERS]['title'] = __('Business');

        $allowed[$this->getCustomerType()]['isActive'] = 1;

        switch ($allowedCustomerTypes) {
            case AllowedCustomerType::BUSINESS_CUSTOMERS:
                unset($allowed[AllowedCustomerType::PRIVATE_CUSTOMERS]);
                break;
            case AllowedCustomerType::PRIVATE_CUSTOMERS:
                unset($allowed[AllowedCustomerType::BUSINESS_CUSTOMERS]);
                break;
        }

        return $allowed;
    }
}
