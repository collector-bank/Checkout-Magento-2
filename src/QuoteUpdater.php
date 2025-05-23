<?php

namespace Webbhuset\CollectorCheckout;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Quote\Model\Quote as Quote;
use Webbhuset\CollectorCheckout\Data\ExtractShippingOptionFee;
use Webbhuset\CollectorCheckout\Shipment\DeliveryCheckoutData;
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
    protected $shippingAssignmentProcessor;
    protected $cartExtensionFactory;
    private DeliveryCheckoutData $deliveryCheckoutData;
    private ExtractShippingOptionFee $extractShippingOptionFee;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Webbhuset\CollectorCheckout\Config\QuoteConfigFactory $config,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Customer\Model\Session $session,
        ExtractShippingOptionFee $extractShippingOptionFee,
        DeliveryCheckoutData $deliveryCheckoutData,
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
        $this->extractShippingOptionFee    = $extractShippingOptionFee;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->quoteHandler                = $quoteHandler;
        $this->shippingAssignmentProcessor = $shippingAssignmentProcessor;
        $this->cartExtensionFactory        = $cartExtensionFactory;
        $this->deliveryCheckoutData = $deliveryCheckoutData;
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
        if ($shippingMethod = $this->getCustomDeliveryShippingMethod($checkoutData)) {
            $quote->getExtensionAttributes()->setShippingAssignments([]);
            $shippingAddress->setShippingMethod($shippingMethod);
        }

        $quote->setNeedsCollectorUpdate(true);

        $this->setCustomerData($quote, $checkoutData);
        $this->setPaymentMethod($quote);

        $customerLoggedIn = $this->session->isLoggedIn();
        if (!$customerLoggedIn) {
            if (!$quote->getCustomerId()) {
                $quote->setCustomerIsGuest(true);

                if ($customer->getEmail()) {
                    $email = $customer->getEmail();
                    $quote->setEmail($email);
                }
            }
        } else {
            $customerId = $this->session->getCustomer()->getId();
            $customer = $this->customerRepositoryInterface->getById($customerId);

            $quote->setCustomer($customer);
        }

        if ($this->config->create()->getIsDeliveryCheckoutActive()
            && !$this->isCustomDeliveryAdapter($checkoutData)
        ) {
            $this->setDeliveryCheckoutData($quote, $checkoutData);
        }

        return $quote;
    }

    public function isCustomDeliveryAdapter(
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ): bool {
        $shipment = $checkoutData->getShipping();
        if (!$shipment) {
            return false;
        }
        $shipmentData = $shipment->getData();
        if (
            isset($shipmentData["shipments"][0]["id"])
            && $shipmentData["shipments"][0]["id"] === 'magento-delivery-methods'
            && isset($shipmentData["shipments"][0]['shippingChoice']['id'])
        ) {
            return true;
        }
        return false;
    }

    public function getCustomDeliveryShippingMethod(
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ): ?string {
        if (!$this->isCustomDeliveryAdapter($checkoutData)) {

            return null;
        }
        $shipment = $checkoutData->getShipping()->getData();
        $code = $shipment["shipments"][0]['shippingChoice']['id'];

        return $code;
    }

    public function setDeliveryCheckoutData(
        Quote $quote,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        $shipment = $checkoutData->getShipping()->getData();
        if (!isset($shipment["shipments"][0]['shippingChoice']['id'])) {
            return;
        }
        $shippingOptions = $shipment["shipments"][0]['shippingChoice'];
        $data = [
            'unitPrice' => $shippingOptions['fee'] + $this->extractShippingOptionFee->execute($shippingOptions),
            'id' => $shippingOptions['name'],
            'description' => $shippingOptions['name'],
        ];
        $this->deliveryCheckoutData->setData($data);
        $this->quoteHandler->setDeliveryCheckoutData($quote, $data);
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
        if ($this->config->create()->getIsDeliveryCheckoutActive()
            && !$this->config->create()->getIsCustomDeliveryAdapter()) {
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
        $firstname = is_string($customerAddress->getFirstName()) && strlen($customerAddress->getFirstName()) > 0
            ? $customerAddress->getFirstName()
            : "";
        $lastname = is_string($customerAddress->getLastName()) && strlen($customerAddress->getLastName()) > 0
            ? $customerAddress->getLastName()
            : "";
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
