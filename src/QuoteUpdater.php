<?php

namespace Webbhuset\CollectorCheckout;

use Webbhuset\CollectorCheckoutSDK\Checkout\Customer as SDK;
use Magento\Quote\Model\Quote as Quote;

class QuoteUpdater
{
    protected $taxConfig;
    protected $taxCalculator;
    protected $shippingMethodManagement;
    protected $config;
    protected $session;
    protected $customerRepositoryInterface;
    protected $quoteHandler;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Webbhuset\CollectorCheckout\Config\QuoteConfigFactory $config,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\Session $session,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteHandler,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManagement,
        \Magento\Quote\Model\Quote\ShippingAssignment\ShippingAssignmentProcessor $shippingAssignmentProcessor,
        \Magento\Quote\Api\Data\CartExtensionFactory $cartExtensionFactory
    ) {
        $this->taxConfig                   = $taxConfig;
        $this->config                      = $config;
        $this->taxCalculator               = $taxCalculator;
        $this->shippingMethodManagement    = $shippingMethodManagement;
        $this->session                     = $session;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->quoteHandler                = $quoteHandler;
        $this->shippingAssignmentProcessor = $shippingAssignmentProcessor;
        $this->cartExtensionFactory        = $cartExtensionFactory;
    }

    public function setQuoteData(
        Quote $quote,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) : Quote {
        $customer                   = $checkoutData->getCustomer();
        $collectorInvoiceAddress    = $customer->getInvoiceAddress();
        $billingAddress             = $quote->getBillingAddress();
        $collectorDeliveryAddress   = $customer->getDeliveryAddress();
        $shippingAddress            = $quote->getShippingAddress();
        $config                     = $this->config->create(['quote' => $quote]);

        if ($customer instanceof SDK\PrivateCustomer) {
            $billingAddress = $this->setPrivateAddressData($billingAddress, $customer, $collectorInvoiceAddress)
                ->setCountryId($checkoutData->getCountryCode());
            $shippingAddress = $this->setPrivateAddressData($shippingAddress, $customer, $collectorDeliveryAddress)
                ->setCountryId($checkoutData->getCountryCode());
        }

        if ($customer instanceof SDK\BusinessCustomer) {
            $billingAddress = $this->setBusinessAddressData($billingAddress, $customer, $collectorInvoiceAddress)
                ->setCountryId($checkoutData->getCountryCode());
            $shippingAddress = $this->setBusinessAddressData($shippingAddress, $customer, $collectorDeliveryAddress)
                ->setCountryId($checkoutData->getCountryCode());

            $this->quoteHandler->setOrgNumber($quote, $customer->getOrganizationNumber())
                ->setReference($quote, $customer->getInvoiceReference());
        }

        $quote->setDefaultShippingAddress($shippingAddress);
        $quote->setDefaultBillingAddress($billingAddress);

        $shippingAddress->setCollectShippingRates(true);
        $quote->setNeedsCollectorUpdate(true);

        $this->setCustomerData($quote, $checkoutData);
        $this->setPaymentMethod($quote);

        $customerLoggedIn = $this->session->isLoggedIn();
        if (!$customerLoggedIn) {
            $quote->setCustomerIsGuest(true);

            if($customer->getEmail()) {
                $email = $customer->getEmail();
                $quote->setEmail($email);
            }
        } else {
            $customerId = $this->session->getCustomer()->getId();
            $customer = $this->customerRepositoryInterface->getById($customerId);

            $this->customerRepositoryInterface->save($customer);

            $quote->setCustomer($customer);
        }

        if ($this->config->create()->getIsDeliveryCheckoutActive()) {

            $this->setDeliveryCheckoutData($quote, $checkoutData);
        }

        return $quote;
    }

    public function setDeliveryCheckoutData(
        Quote $quote,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        $fees = $checkoutData->getFees();
        if(!$fees) {

            return;
        }

        $fees = $fees->toArray();
        if(isset($fees['shipping'])) {

            $this->quoteHandler->setDeliveryCheckoutData($quote, $fees['shipping']);
        }
    }

    public function setDefaultShippingIfEmpty(
        Quote $quote
    ) : Quote {
        if ($quote->getShippingAddress()->getShippingMethod()) {
            return $quote;
        }
        $shippingAddress = $quote->getShippingAddress();
        $config = $this->config->create(['quote' => $quote]);
        $countryCode = $config->getCountryCode();

        $shippingAddress->setCountryId($countryCode)
            ->setCollectShippingRates(true)
            ->collectShippingRates();

        $this->setDefaultShippingMethod($quote);

        return $quote;
    }

    public function setDefaultShippingMethod($quote)
    {
        $defaultShippingMethod = $this->getDefaultShippingMethod($quote);

        if ($defaultShippingMethod) {
            $quote->getShippingAddress()
                ->setShippingMethod($defaultShippingMethod);

            $cartExtension = $quote->getExtensionAttributes();
            if ($cartExtension === null) {
                $cartExtension = $this->cartExtensionFactory->create();
            }
            $shippingAssignment = $this->shippingAssignmentProcessor->create($quote);
            $cartExtension->setShippingAssignments([$shippingAssignment]);
            $quote->setExtensionAttributes($cartExtension);
        }
    }

    protected function getDefaultShippingMethod(Quote $quote)
    {
        if ($this->config->create()->getIsDeliveryCheckoutActive()) {
            $gatewayKey = \Webbhuset\CollectorCheckout\Carrier\Collector::GATEWAY_KEY;

            return $gatewayKey . '_' . $gatewayKey;
        }

        $shippingAddress = $quote->getShippingAddress();
        $rates = $this->shippingMethodManagement->getList($quote->getId());

        if (empty($rates)) {
            return false;
        }

        $shippingMethod = reset($rates);
        foreach ($rates as $rate) {
            $method = $rate->getCarrierCode() . '_' . $rate->getMethodCode();
            if ($method === $shippingAddress->getShippingMethod()) {
                $shippingMethod = $rate;
                break;
            }
        }

        return $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode();
    }

    public function setCustomerData(
        Quote $quote,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) : Quote {
        $customer = $checkoutData->getCustomer();
        $customerAddress = $customer->getInvoiceAddress();

        $firstname = $customerAddress->getFirstName();
        $lastname  = $customerAddress->getLastName();
        $email = $customer->getEmail();

        $quote->setCustomerFirstname($firstname)
            ->setCustomerLastname($lastname)
            ->setCustomerEmail($email);

        return $quote;
    }

    public function setPaymentMethod(
        Quote $quote
    ) : Quote {
        $payment = $quote->getPayment();
        $payment->setMethod(\Webbhuset\CollectorCheckout\Gateway\Config::CHECKOUT_CODE);

        return $quote;
    }

    public function setPrivateAddressData(
        Quote\Address $address,
        SDK\PrivateCustomer $customer,
        SDK\PrivateAddress $collectorAddress
    ) {
        $address->setEmail($customer->getEmail())
            ->setTelephone($customer->getMobilePhoneNumber())
            ->setFirstname($collectorAddress->getFirstName())
            ->setLastname($collectorAddress->getLastName())
            ->setStreet([
                $collectorAddress->getCoAddress(),
                $collectorAddress->getAddress(),
                $collectorAddress->getAddress2()
            ])->setPostCode($collectorAddress->getPostalCode())
            ->setCity($collectorAddress->getCity());

        return $address;
    }

    public function setCustomerTypeData(
        Quote $quote,
        int $customerType
    ) {
        /** @var \Webbhuset\CollectorCheckout\Config\QuoteConfig $config */
        $config = $this->config->create(['quote' => $quote]);

        $this->quoteHandler->setCustomerType($quote, $customerType);
        if (\Webbhuset\CollectorCheckout\Config\Source\Customer\DefaultType::PRIVATE_CUSTOMERS == $customerType) {
            $storeId = $config->getB2CStoreId();
        } else {
            $storeId =  $config->getB2BStoreId();
        }
        $this->quoteHandler->setStoreId($quote, $storeId);

        return $quote;
    }

    public function setBusinessAddressData(
        Quote\Address $address,
        SDK\BusinessCustomer $customer,
        SDK\BusinessAddress $collectorAddress
    ) {
        $address->setEmail($customer->getEmail())
            ->setTelephone($customer->getMobilePhoneNumber())
            ->setFirstname($customer->getFirstName())
            ->setLastname($customer->getLastName())
            ->setCompany($collectorAddress->getCompanyName())
            ->setStreet([
                $collectorAddress->getCoAddress(),
                $collectorAddress->getAddress(),
                $collectorAddress->getAddress2()
            ])->setPostCode($collectorAddress->getPostalCode())
            ->setCity($collectorAddress->getCity());

        return $address;
    }
}
