<?php

namespace Webbhuset\CollectorCheckout;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Quote\Model\Quote as Quote;
use Webbhuset\CollectorCheckoutSDK\Checkout\Customer as SDK;

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
            $this->quoteHandler->setNationalIdentificationNumber($quote, $customer->getNationalIdentificationNumber());
            if ($customer->getDeliveryMobilePhoneNumber()) {
                $shippingAddress->setTelephone($customer->getDeliveryMobilePhoneNumber());
            }
        }

        if ($customer instanceof SDK\BusinessCustomer) {
            $billingAddress = $this->setBusinessAddressData($billingAddress, $customer, $collectorInvoiceAddress)
                ->setCountryId($checkoutData->getCountryCode());
            $shippingAddress = $this->setBusinessAddressData($shippingAddress, $customer, $collectorDeliveryAddress)
                ->setCountryId($checkoutData->getCountryCode());

            $this->quoteHandler->setOrgNumber($quote, $customer->getOrganizationNumber())
                ->setReference($quote, $customer->getInvoiceReference())
                ->setInvoiceTag($quote, $customer->getInvoiceTag());
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

            if ($customer->getEmail()) {
                $email = $customer->getEmail();
                $quote->setEmail($email);
            }
        } else {
            $customerId = $this->session->getCustomer()->getId();
            $customer = $this->customerRepositoryInterface->getById($customerId);

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
        if (!$fees) {
            return;
        }

        $fees = $fees->toArray();
        if (isset($fees['shipping'])) {
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
        $rate = reset($rates);
        if (!$rate) {
            return '';
        }

        return $rate->getCarrierCode() . '_' . $rate->getMethodCode();
    }

    public function setCustomerData(
        Quote $quote,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) : Quote {
        $customer = $checkoutData->getCustomer();
        $customerAddress = $customer->getInvoiceAddress();
        $firstname = $customerAddress->getFirstName() ?? $customer->getFirstName();
        $lastname  = $customerAddress->getLastName() ?? $customer->getLastName();
        $email = $customer->getEmail();
        $countryCode = $checkoutData->getCountryCode();
        $basicAddress = $quote->getShippingAddress()->setCountryId($countryCode);
        $quote->setCustomerFirstname($firstname)
            ->setCustomerLastname($lastname)
            ->setCustomerEmail($email)
            ->setShippingAddress($basicAddress);

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
        $ssn  = $customer->getNationalIdentificationNumber();
        $address->setEmail($customer->getEmail())
            ->setTelephone($customer->getMobilePhoneNumber())
            ->setFirstname($collectorAddress->getFirstName())
            ->setNationalIdentificationNumber($ssn)
            ->setLastname($collectorAddress->getLastName())
            ->setStreet(trim(
                    implode("\n", [
                        $collectorAddress->getCoAddress(),
                        $collectorAddress->getAddress(),
                        $collectorAddress->getAddress2()
                    ])
                )
            )->setPostCode($collectorAddress->getPostalCode())
            ->setCity($collectorAddress->getCity());

        return $address;
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
            ->setStreet(trim(
                implode("\n", [
                $collectorAddress->getCoAddress(),
                $collectorAddress->getAddress(),
                $collectorAddress->getAddress2()
            ])))->setPostCode($collectorAddress->getPostalCode())
            ->setCity($collectorAddress->getCity());

        return $address;
    }
}
