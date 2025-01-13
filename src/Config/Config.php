<?php

namespace Webbhuset\CollectorCheckout\Config;

use Webbhuset\CollectorCheckout\Config\Source\Checkout\Version;

/**
 * Class Config
 *
 * @package Webbhuset\CollectorCheckout\Config
 */
class Config implements
    \Webbhuset\CollectorCheckoutSDK\Config\ConfigInterface,
    \Webbhuset\CollectorPaymentSDK\Config\ConfigInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderDataHandler;
    /**
     * @var int $storeId
     */
    protected $storeId;
    /**
     * @var Source\Country\Country
     */
    protected $countryData;
    /**
     * @var int
     */
    protected $magentoStoreId = null;
    private \Webbhuset\CollectorCheckout\Oath\AccessKeyManager $accessKeyManager;

    /**
     * Config constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Encryption\EncryptorInterface   $encryptor
     * @param \Magento\Checkout\Model\Session                    $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler
     * @param \Webbhuset\CollectorCheckout\Data\OrderHandler $orderDataHandler
     * @param Source\Country\Country                             $countryData
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\CollectorCheckout\Config\Source\Country\Country $countryData,
        \Webbhuset\CollectorCheckout\Oath\AccessKeyManager $accessKeyManager
    ) {
        $this->scopeConfig      = $scopeConfig;
        $this->encryptor        = $encryptor;
        $this->storeManager     = $storeManager;
        $this->countryData      = $countryData;
        $this->accessKeyManager = $accessKeyManager;
    }

    /**
     * Returns true if collector payment method is active
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return 1 == $this->getConfigValue('active');
    }

    public function getStoreScopeId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }

    public function getAccessKey(): string
    {
        $storeId = $this->getStoreScopeId();

        return $this->accessKeyManager->getAccessKeyByStore($storeId);
    }

    /**
     * Returns true if delete pending orders
     *
     * @return bool
     */
    public function getDeletePendingOrders(): bool
    {
        return 1 == $this->getConfigValue('delete_pending_orders');
    }

    /**
     * Returns true if customers accounts should be created for new orders
     *
     * @return bool
     */
    public function getCreateCustomerAccount(): bool
    {
        return 1 == $this->getConfigValue('create_customer_account');
    }

    /**
     * Get the username
     *
     * @return string
     */
    public function getUsername() : string
    {
        return $this->getIsTestMode() ? $this->getTestModeUsername() : $this->getProductionModeUsername();
    }

    /**
     * Get shared access key
     *
     * @return string
     */
    public function getSharedAccessKey() : string
    {
        return $this->getPassword();
    }

    /**
     * Get shared access key / password
     *
     * @return string
     */
    public function getPassword() : string
    {
        return $this->getIsTestMode() ? $this->getTestModePassword() : $this->getProductionModePassword();
    }

    /**
     * Get country code
     *
     * @return string
     */
    public function getCountryCode() : string
    {
        return $this->getConfigValue('country_code');
    }

    /**
     * Gets current store id
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId() : string
    {
        $customerType = $this->getDefaultCustomerType();

        if (\Webbhuset\CollectorCheckout\Config\Source\Customer\DefaultType::PRIVATE_CUSTOMERS == $customerType) {
            return $this->getB2CStoreId();
        }

        return $this->getB2BStoreId();
    }

    /**
     * Gets B2C store id
     *
     * @return string
     */
    public function getB2CStoreId() : string
    {
        return $this->getIsTestMode() ? $this->getTestModeB2C() : $this->getProductionModeB2C();
    }

    /**
     * Get B2B store id
     *
     * @return string
     */
    public function getB2BStoreId() : string
    {
        return $this->getIsTestMode() ? $this->getTestModeB2B() : $this->getProductionModeB2B();
    }

    public function getDisplayCheckoutVersion(): string
    {
        return Version::V2;
    }

    /**
     * Get customer types allowed to checkout
     *
     * @return int
     */
    public function getCustomerTypeAllowed(): int
    {
        return $this->getConfigValue('customer_type') ? $this->getConfigValue('customer_type') : 0;
    }

    /**
     * Get default customer type
     *
     * @return int
     */
    public function getDefaultCustomerType(): int
    {
        return $this->getConfigValue('default_customer_type') ? $this->getConfigValue('default_customer_type') : 0;
    }

    /**
     * Returns true if in mock mode
     *
     * @return bool
     */
    public function getIsMockMode(): bool
    {
        return false;
    }

    /**
     * Returns true if in test mode
     *
     * @return bool
     */
    public function getIsTestMode(): bool
    {
        return $this->getConfigValue('test_mode') ? $this->getConfigValue('test_mode') : false;
    }

    public function getIsOath(): bool
    {
        if ($this->getIsTestModeOath()) {
            return true;
        }

        return (bool) $this->getConfigValue('activeoath');
    }

    public function getClientId(): string
    {
        if ($this->getIsTestModeOath()) {
            return $this->getTestModeClientId();
        }

        return (string) $this->getConfigValue('client_id');
    }

    public function getClientSecret(): string
    {
        if ($this->getIsTestModeOath()) {
            return $this->getTestModeClientSecret();
        }

        return (string) $this->getConfigValue('client_secret');
    }

    public function getIsTestModeOath(): bool
    {
        $isTestMode = $this->getIsTestMode();
        if (!$isTestMode) {
            return false;
        }

        return (bool) $this->getConfigValue('test_mode_activeoath');
    }

    public function getTestModeClientSecret(): string
    {
        return (string) $this->getConfigValue('client_secret');
    }

    public function getTestModeClientId(): string
    {
        return (string) $this->getConfigValue('client_id');
    }

    /**
     * Get the url for customer / merchant terms
     *
     * @return string
     */
    public function getMerchantTermsUri(): string
    {
        return $this->getConfigValue('terms_url') ? $this->getConfigValue('terms_url') : "";
    }

    /**
     * Get the redirect page url = Success page / thank you page url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRedirectPageUri(): string
    {
        $checkoutUrl = \Webbhuset\CollectorCheckout\Gateway\Config::CHECKOUT_URL_KEY;
        $urlKey = $checkoutUrl . "/success/index/reference/{checkout.publictoken}";

        $url = $this->storeManager->getStore()->getUrl($urlKey);

        return $url;
    }

    public function getCustomFields():array
    {
        if (empty($this->getFields())) {
            return [];
        }
        return [
            [
                "id" => "myGroup",
                "metadata"=> [
                    "groupMeta" => "content"
                ],
                'fields' => $this->getFields()
            ]
        ];
    }

    public function getFields()
    {
        $fields = [];
        $newsletter = $this->getNewsletterField();
        if (!empty($newsletter)) {
            $fields[] = $newsletter;
        }
        $comments = $this->getCommentField();
        if (!empty($comments)) {
            $fields[] = $comments;
        }

        return  $fields;
    }

    public function getNewsletterField():array
    {
        if (!$this->isNewsletter() || !$this->getNewsletterText()) {
            return [];
        }
        return [
            "id" => "newsConsent",
            "name" => $this->getNewsletterText(),
            "type" => "Checkbox",
            "value" => true,
            "metadata" => [
                "field1Meta" => "field-newsletter-consent"
            ],
        ];
    }

    public function getCommentField()
    {
        if (!$this->isComment() || !$this->getCommentText()) {
            return [];
        }
        return [
            "id" => "comments",
            "name" => $this->getCommentText(),
            "type" => "Text",
        ];
    }
    /**
     * Get the notification url - Used by collector to update order state after order has been placed
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getNotificationUri() : string
    {
        $urlKey = "collectorbank/notification/index/reference/{checkout.publictoken}";

        if ($this->getCustomBaseUrl()) {
            return $this->getCustomBaseUrl() . $urlKey;
        }

        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    /**
     * Get the validation url - Used by collector when placing orders
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getValidationUri(): string
    {
        $urlKey = "collectorbank/validation/index/reference/{checkout.publictoken}";

        if ($this->getCustomBaseUrl()) {
            return $this->getCustomBaseUrl() . $urlKey;
        }

        return $this->storeManager->getStore()->getUrl($urlKey);
    }

    /**
     * Get the order status for new orders
     *
     * @return string
     */
    public function getOrderStatusNew(): string
    {
        return $this->getWithoutConfigurationConfigValue('order_status');
    }

    /**
     * Get the order status for acknowledged
     *
     * @return string
     */
    public function getOrderStatusAcknowledged(): string
    {
        return $this->getConfigValue('order_accepted_status');
    }

    /**
     * Get the order status for holded
     *
     * @return string
     */
    public function getOrderStatusHolded(): string
    {
        return $this->getConfigValue('order_holded_status');
    }

    /**
     * Get the order status for denied
     *
     * @return string
     */
    public function getOrderStatusDenied(): string
    {
        return $this->getConfigValue('order_denied_status');
    }

    /**
     * Gets B2C store id
     *
     * @return string
     */
    public function getB2CProfileName() : string
    {
        $profileName = $this->getConfigValue('profile_name');

        return $profileName ? $profileName : "";
    }

    /**
     * Get B2B store id
     *
     * @return string
     */
    public function getB2BProfileName() : string
    {
        $profileName = $this->getConfigValue('profile_name_b2b');

        return $profileName ? $profileName : "";
    }

    /**
     * Get profile name
     *
     * @return string
     */
    public function getProfileName(): string
    {
        $customerType = $this->getDefaultCustomerType();

        if (\Webbhuset\CollectorCheckout\Config\Source\Customer\DefaultType::PRIVATE_CUSTOMERS == $customerType) {
            return $this->getB2CProfileName();
        }

        return $this->getB2BProfileName();
    }

    /**
     * Get production mode username
     *
     * @return string
     */
    public function getProductionModeUsername(): string
    {
        return $this->getConfigValue('username') ? $this->getConfigValue('username') : "";
    }

    /**
     * Get production mode password / shared secret
     *
     * @return string
     */
    public function getProductionModePassword(): string
    {
        $value = $this->getConfigValue('password');
        if (!$value) {
            return "";
        }

        $value = $this->encryptor->decrypt($value);

        return $value;
    }

    /**
     * Get production mode store id for B2C
     *
     * @return string
     */
    public function getProductionModeB2C() : string
    {
        return $this->getConfigValue('b2c') ? $this->getConfigValue('b2c') : "";
    }

    /**
     * Get production mode store id for B2B
     *
     * @return string
     */
    public function getProductionModeB2B() : string
    {
        return $this->getConfigValue('b2b') ? $this->getConfigValue('b2b') : "";
    }

    /**
     * Get username for testmode
     *
     * @return string
     */
    public function getTestModeUsername(): string
    {
        return $this->getConfigValue('test_mode_username') ? $this->getConfigValue('test_mode_username') : "";
    }

    /**
     * Get password for testmode
     *
     * @return string
     */
    public function getTestModePassword(): string
    {
        $value = $this->getConfigValue('test_mode_password');
        if (!$value) {
            return "";
        }
        $value = $this->encryptor->decrypt($value);

        return $value;
    }

    /**
     * Get storeid for b2b for testmode
     *
     * @return string
     */
    public function getTestModeB2C(): string
    {
        return $this->getConfigValue('test_mode_b2c') ? $this->getConfigValue('test_mode_b2c') : "";
    }

    /**
     * Get storeid for b2b for testmode
     *
     * @return string
     */
    public function getTestModeB2B(): string
    {
        return $this->getConfigValue('test_mode_b2b') ? $this->getConfigValue('test_mode_b2b') : "";
    }

    protected function getWithoutConfigurationConfigValue($name)
    {
        $storeId = $this->storeManager->getStore()->getId();

        $value = $this->scopeConfig->getValue(
            'payment/collectorbank_checkout/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value;
    }

    protected function getConfigValue($name)
    {
        $storeId = $this->storeManager->getStore()->getId();

        $value = $this->scopeConfig->getValue(
            'payment/collectorbank_checkout/configuration/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value;
    }

    /**
     * Returns true if collector delivery checkout is active
     *
     * @return bool
     */
    public function getIsDeliveryCheckoutActive(): bool
    {
        return 1 == $this->getDeliveryCheckoutConfigValue('active');
    }

    /**
     * Returns true if collector delivery checkout is active
     *
     * @return bool
     */
    public function getIsCustomDeliveryAdapter(): bool
    {
        return 1 == $this->getDeliveryCheckoutConfigValue('custom_delivery_adapter');
    }

    /**
     * Get fallback title
     *
     * @return string
     */
    public function getDeliveryCheckoutFallbackTitle()
    {
        return $this->getDeliveryCheckoutConfigValue('fallback_title');
    }

    /**
     * Get fallback description
     *
     * @return string
     */
    public function getDeliveryCheckoutFallbackDescription()
    {
        return $this->getDeliveryCheckoutConfigValue('fallback_description');
    }

    /**
     * Get fallback price
     *
     * @return float
     */
    public function getDeliveryCheckoutFallbackPrice()
    {
        return (float)$this->getDeliveryCheckoutConfigValue('fallback_price');
    }

    protected function getDeliveryCheckoutConfigValue($name)
    {
        $storeId = $this->storeManager->getStore()->getId();

        $value = $this->scopeConfig->getValue(
            'payment/collectorbank_checkout/deliverycheckout/' . $name,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value;
    }

    /**
     * Get the current mode the collector bank payment method is running in
     *
     * @return string
     */
    public function getMode()
    {
        $mode = $this->getIsTestMode() ? "test mode" : "production mode";

        return $this->getIsMockMode() ? "mock mode" : $mode;
    }

    /**
     * Returns true if collector bank is in testmode
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return $this->getIsTestMode();
    }

    /**
     * Returns true if collector bank is in production mode
     *
     * @return bool
     */
    public function isProductionMode(): bool
    {
        return !$this->getIsTestMode();
    }

    /**
     * Get custom base url - used one behind a proxy / firewall
     *
     * @return mixed
     */
    public function getCustomBaseUrl()
    {
        return $this->getConfigValue('custom_base_url');
    }

    public function isNewsletter(): bool
    {
        return (bool) $this->getConfigValue('newsletter');
    }

    public function getNewsletterText():string
    {
        return $this->getConfigValue('newsletter_text');
    }

    public function isComment(): bool
    {
        return (bool) $this->getConfigValue('comment');
    }

    public function getCommentText():string
    {
        return $this->getConfigValue('comment_text');
    }

    /**
     * Get checkout url
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCheckoutUrl()
    {
        $urlKey = \Webbhuset\CollectorCheckout\Gateway\Config::CHECKOUT_URL_KEY;
        $url = $this->storeManager->getStore()->getUrl($urlKey);

        return $url;
    }

    /**
     * Get style data-lang an attribute used for styling iframe
     *
     * @return mixed
     */
    public function getStyleDataLang()
    {
        $data = $this->getConfigValue('style_data_lang');

        return ($data) ? $data : $this->getDefaultLanguage();
    }

    /**
     * Get default language code for the selected country
     *
     *
     * @return mixed
     */
    public function getDefaultLanguage()
    {
        $language = $this->countryData->getDefaultLanguagePerCounty();
        $countryCode = $this->getCountryCode();

        return $language[$countryCode];
    }

    /**
     * Get style data-padding, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataPadding()
    {
        $data = $this->getConfigValue('style_data_padding');

        return ($data) ? $data : null;
    }

    /**
     * Get style container-id, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataContainerId()
    {
        $data = $this->getConfigValue('style_data_container_id');

        return ($data) ? $data : null;
    }

    /**
     * Get style data-action-color, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataActionColor()
    {
        $data = $this->getConfigValue('style_data_action_color');

        return ($data) ? $data : null;
    }

    /**
     * Get style data-action-text-color, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataActionTextColor()
    {
        $data = $this->getConfigValue('style_data_action_text_color');

        return ($data) ? $data : null;
    }

    /**
     * Get default currency code for the selected country
     *
     * @return mixed
     */
    public function getCurrency()
    {
        $currencies = $this->countryData->getCurrencyPerCountry();
        $countryCode = $this->getCountryCode();

        return $currencies[$countryCode];
    }
}
