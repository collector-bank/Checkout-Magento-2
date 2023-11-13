<?php

namespace Webbhuset\CollectorCheckout;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Webbhuset\CollectorCheckoutSDK\Checkout\Cart;
use Webbhuset\CollectorCheckoutSDK\Checkout\Cart\Item;
use Webbhuset\CollectorCheckoutSDK\Checkout\Customer\InitializeCustomer;
use Webbhuset\CollectorCheckoutSDK\Checkout\Fees;
use Webbhuset\CollectorCheckoutSDK\Checkout\Fees\Fee;

class QuoteConverter
{
    protected $taxConfig;
    protected $taxCalculator;
    protected $scopeConfig;
    protected $configurationHelper;
    protected $config;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;
    /**
     * @var AddressRepositoryInterface
     */
    private AddressRepositoryInterface $addressRepository;
    /**
     * @var Data\QuoteHandler
     */
    private Data\QuoteHandler $quoteHandler;

    public function __construct(
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $taxCalculator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteHandler,
        CustomerRepositoryInterface $customerRepository,
        AddressRepositoryInterface $addressRepository,
        \Magento\Catalog\Helper\Product\Configuration $configurationHelper,
        \Webbhuset\CollectorCheckout\Config\QuoteConfigFactory $config
    ) {
        $this->taxConfig            = $taxConfig;
        $this->taxCalculator        = $taxCalculator;
        $this->scopeConfig          = $scopeConfig;
        $this->configurationHelper  = $configurationHelper;
        $this->config               = $config;
        $this->customerRepository   = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->quoteHandler = $quoteHandler;
    }

    public function getCart(\Magento\Quote\Model\Quote $quote) : Cart
    {
        $quoteItems = $quote->getAllVisibleItems();
        $items = [];

        foreach ($quoteItems as $quoteItem) {
            if (\Magento\Bundle\Model\Product\Type::TYPE_CODE === $quoteItem->getProductType()) {
                $items = array_merge($items, $this->extractBundleQuoteItem($quoteItem));
            } else {
                $items = array_merge($items, $this->extractQuoteItem($quoteItem));
            }
        }

        $roundingError = $this->addRoundingError($quote, $items);
        if ($roundingError) {
            $items = array_merge($items, [$roundingError]);
        }
        $items = $this->renameDuplicates($items);
        $cart = new Cart($items);

        return $cart;
    }

    protected function renameDuplicates($items)
    {
        $duplicates = [];

        foreach ($items as $item) {
            $sku = $item->getId();

            if (!isset($duplicates[$sku])) {
                $duplicates[$sku] = 0;
            } else {
                $duplicates[$sku] = $duplicates[$sku]+1;
                $num = $duplicates[$sku];

                $newSku = $sku . "-" . $num;
                $item->setId($newSku);
            }
        }

        return $items;
    }

    protected function extractQuoteItem($quoteItem)
    {
        $items[] = $this->getCartItem($quoteItem);

        if ((float)$quoteItem->getDiscountAmount()) {
            $items[] = $this->getDiscountItem($quoteItem);
        }

        return $items;
    }

    protected function extractBundleQuoteItem($quoteItem)
    {
        $items = [];

        $childrenTotal = 0;
        foreach ($quoteItem->getChildren() as $child) {
            $childrenItem = $this->getCartItem(
                $child,
                "- ",
                false,
                $quoteItem->getQty()
            );

            $items[] = $childrenItem;
            $childrenTotal += $childrenItem->getUnitPrice();
            if ((float)$child->getDiscountAmount()) {
                $items[] = $this->getDiscountItem(
                    $child,
                    '- ' . __('Discount: '),
                    $quoteItem->getQty()
                );
            }
        }
        $bundleParent = [];
        $bundleParent[] = ($childrenTotal > 0) ?
            $this->getCartItem($quoteItem, "", true) :
            $this->getCartItem($quoteItem);

        $items = array_merge($bundleParent, $items);
        if ((float)$quoteItem->getDiscountAmount()) {
            $items[] = $this->getDiscountItem($quoteItem, __('Discount: '));
        }

        return $items;
    }

    protected function addRoundingError(\Magento\Quote\Model\Quote $quote, $items)
    {
        $collectorCheckoutSum = $this->sumItems($items) + $this->sumFees($this->getFees($quote));
        $quoteSum = $quote->getGrandTotal();

        $roundingError = round($quoteSum - $collectorCheckoutSum, 2);
        if (!($roundingError != 0 && abs($roundingError) < 0.1)) {
            return false;
        }

        return new Item(
            \Webbhuset\CollectorCheckout\Gateway\Config::CURRENCY_ROUNDING_SKU,
            __("Currency rounding"),
            $roundingError,
            1,
            0,
            false,
            \Webbhuset\CollectorCheckout\Gateway\Config::CURRENCY_ROUNDING_SKU
        );
    }

    public function getCartItem(
        \Magento\Quote\Model\Quote\Item $quoteItem,
        $prefix = "",
        $priceIsZero = false,
        $parentQty = 1
    )
    : Item
    {
        $optionText = $this->getSelectedOptionText($quoteItem);

        $id                     = (string) $prefix . $quoteItem->getSku();
        $description            = (string) $quoteItem->getName() . $optionText;
        $unitPrice              = ($priceIsZero) ? 0.00 : (float) $quoteItem->getPriceInclTax();
        $weight                 = (float) $quoteItem->getWeight();
        $quantity               = (int) $quoteItem->getQty() * $parentQty;
        $vat                    = (float) $quoteItem->getTaxPercent();
        $requiresElectronicId   = (bool) $this->requiresElectronicId($quoteItem);
        $sku                    = (string) $quoteItem->getItemId();

        $item = new Item(
            $id,
            $description,
            round($unitPrice, 2),
            $quantity,
            $vat,
            $requiresElectronicId,
            $sku,
            $weight
        );

        return $item;
    }

    public function getDiscountItem(
        \Magento\Quote\Model\Quote\Item $quoteItem,
        $prefix = "",
        $parentQty = 1
    ) {
        $discountAmount = $quoteItem->getDiscountAmount();
        $taxPercent = $quoteItem->getTaxPercent();
        $priceIncludesTax = $this->scopeConfig->getValue(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $discountTax = 0;
        if ($taxPercent && !$priceIncludesTax) {
            $discountTax = ($discountAmount * $taxPercent / 100);
        }

        $id                     = (string) $prefix . $quoteItem->getSku();
        $description            = (string) __('Discount');
        $unitPrice              = (float) ($discountAmount + $discountTax) * -1;
        $quantity               = (int) $quoteItem->getQty() * $parentQty;
        $vat                    = (float) $quoteItem->getTaxPercent();

        $item = new Item(
            $id,
            $description,
            round($unitPrice/$quantity, 2),
            $quantity,
            $vat,
            null,
            $quoteItem->getItemId() . ":discount"
        );

        return $item;
    }

    public function getFees(\Magento\Quote\Model\Quote $quote) : Fees
    {
        $shippingFee        = $this->getShippingFee($quote);
        $directInvoiceFee   = $this->getDirectInvoiceFee($quote);

        $fees = new Fees(
            $shippingFee,
            $directInvoiceFee
        );

        return $fees;
    }

    public function getFallbackFees(\Magento\Quote\Model\Quote $quote) : Fees
    {
        $shippingFee        = $this->getShippingFallbackFee($quote);
        $directInvoiceFee   = $this->getDirectInvoiceFee($quote);

        $fees = new Fees(
            $shippingFee,
            $directInvoiceFee
        );

        return $fees;
    }

    public function getShippingFallbackFee(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $method = $shippingAddress->getShippingMethod();
        if (!$method) {
            return null;
        }

        /** @var \Webbhuset\CollectorCheckout\Config\QuoteConfig $config */
        $config         = $this->config->create();

        $id          = (string) $config->getDeliveryCheckoutFallbackTitle();
        $description = (string) $config->getDeliveryCheckoutFallbackDescription();
        $unitPrice   = (float) $config->getDeliveryCheckoutFallbackPrice();
        $vatPercent  = (float) $this->getShippingTaxPercent($quote);

        $fee = new Fee(
            $id,
            $description,
            $unitPrice,
            $vatPercent,
            'shipping'
        );

        return $fee;
    }

    public function getShippingFee(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $method = $shippingAddress->getShippingMethod();
        if (!$method) {
            return null;
        }

        $id          = (string) $method;
        $description = ((string) $shippingAddress->getShippingDescription()) ? ((string) $shippingAddress->getShippingDescription()) : (string) $method;
        $unitPrice   = (float) $shippingAddress->getShippingInclTax();
        $vatPercent  = (float) $this->getShippingTaxPercent($quote);

        $fee = new Fee(
            $id,
            $description,
            $unitPrice,
            $vatPercent,
            'shipping'
        );

        return $fee;
    }

    public function getShippingTaxPercent(\Magento\Quote\Model\Quote $quote) : float
    {
        $request = $this->taxCalculator->getRateRequest(
            $quote->getShippingAddress(),
            $quote->getBillingAddress(),
            $quote->getCustomerTaxClassId(),
            $quote->getStoreId()
        );

        $shippingTaxClassId = $this->taxConfig->getShippingTaxClass($quote->getStoreId());
        $vatPercent = (float) $this->taxCalculator->getRate($request->setProductClassId($shippingTaxClassId));

        return $vatPercent;
    }

    public function getDirectInvoiceFee(\Magento\Quote\Model\Quote $quote)
    {
        return null;
    }

    public function getInitializeCustomer(\Magento\Quote\Model\Quote $quote)
    {
        $email                          = (string) $this->getEmail($quote);
        $mobilePhoneNumber              = (string) $this->getMobilePhoneNumber($quote);
        $nationalIdentificationNumber   = (string) $this->getNationalIdentificationNumber($quote);
        $postalCode                     = (string) $this->getPostalCode($quote);
        $deliveryAddress                = $this->getDeliveryAddress($quote);
        $customerType                   = $this->quoteHandler->getCustomerType($quote);

        // Email and mobile phone number are required. If we don't have both, we return null
        if ($email && $mobilePhoneNumber) {
            $customer = new InitializeCustomer(
                $email,
                $mobilePhoneNumber,
                $nationalIdentificationNumber,
                $postalCode,
                $deliveryAddress,
                $customerType
            );

            return $customer;
        }

        return null;
    }

    public function getEmail(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $email = $quote->getCustomerEmail() ?? $shippingAddress->getEmail();

        return $email;
    }

    public function getMobilePhoneNumber(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $quotePhoneNumber = $shippingAddress->getTelephone();
        if ($quotePhoneNumber) {
            return $quotePhoneNumber;
        }
        $defaultShippingAddressPhoneNumber = $this->getDefaultShippingAddressPhoneNumber($quote);
        if (!$defaultShippingAddressPhoneNumber) {
            return "";
        }

        return $defaultShippingAddressPhoneNumber;
    }

    private function getDefaultShippingAddressId(\Magento\Quote\Model\Quote $quote):?int
    {
        $customerId = (int) $quote->getCustomerId();
        if (!$customerId) {
            return null;
        }
        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (\Exception $e) {
            return null;
        }

        return (int) $customer->getDefaultShipping();
    }

    private function getDefaultShippingAddress(\Magento\Quote\Model\Quote $quote)
    {
        $defaultAddressId = $this->getDefaultShippingAddressId($quote);
        if (!$defaultAddressId) {
            return null;
        }
        try {
            $address = $this->addressRepository->getById($defaultAddressId);
        } catch (LocalizedException $e) {
            return null;
        }

        return $address;
    }

    private function getDefaultShippingAddressPhoneNumber(\Magento\Quote\Model\Quote $quote):?string
    {
        $address = $this->getDefaultShippingAddress($quote);
        if (!$address) {
            return null;
        }

        return (string) $address->getTelephone();
    }

    private function getDefaultShippingAddressPostCode(\Magento\Quote\Model\Quote $quote):?string
    {
        $address = $this->getDefaultShippingAddress($quote);
        if (!$address) {
            return null;
        }

        return (string) $address->getPostcode();
    }

    public function getNationalIdentificationNumber(\Magento\Quote\Model\Quote $quote)
    {
        return null;
    }

    public function getPostalCode(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $quote->getShippingAddress();
        $postCode = $shippingAddress->getPostcode();
        if ($postCode) {
            return $postCode;
        }
        $defaultAddressPostalCode = $this->getDefaultShippingAddressPostCode($quote);
        if (!$defaultAddressPostalCode) {
            return "";
        }

        return $defaultAddressPostalCode;
    }

    public function getReference(\Magento\Quote\Model\Quote $quote)
    {
        return $quote->getReservedOrderId();
    }

    public function requiresElectronicId($quoteItem)
    {
        if ($quoteItem->getIsVirtual()) {
            return true;
        }

        if ($quoteItem->getProductType() == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE) {
            return true;
        }

        return false;
    }

    private function sumFees($fees)
    {
        $sum = 0;
        $fees = ($fees->toArray());
        foreach ($fees as $fee) {
            $sum += $fee['unitPrice'];
        }

        return $sum;
    }

    private function sumItems($items)
    {
        $sum = 0;
        foreach ($items as $item) {
            $sum += $item->getUnitPrice() * $item->getQuantity();
        }

        return $sum;
    }

    private function getSelectedOptionText(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
        $optionTexts = $this->getSelectedOptionsOfQuoteItem($item);

        $result = [];
        foreach ($optionTexts as $option) {
            $result[] = $option['value'];
        }

        if (empty($result)) {
            return "";
        }

        return ":" . implode("-", $result);
    }

    private function getSelectedOptionsOfQuoteItem(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface $item)
    {
        return $this->configurationHelper->getCustomOptions($item);
    }

    public function getDeliveryAddress(\Magento\Quote\Model\Quote $quote)
    {
        $shippingAddress = $this->getDefaultShippingAddress($quote);
        if (!$shippingAddress) {
            return [];
        }
        return [
            'firstName' => $shippingAddress->getFirstname(),
            'lastName' => $shippingAddress->getLastname(),
            'companyName' => $shippingAddress->getCompany(),
            'address' => $shippingAddress->getStreet()[0] ?? '',
            'address2' => $shippingAddress->getStreet()[1] ?? '',
            'postalCode' => $shippingAddress->getPostcode(),
            'city' => $shippingAddress->getCity(),
        ];

    }
}
