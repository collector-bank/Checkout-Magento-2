<?php

namespace Webbhuset\CollectorCheckout\Config;

use Webbhuset\CollectorCheckout\Config\Source\Checkout\Version;
use Magento\Payment\Gateway\Config\Config as GatewayConfig;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Config accessor service
 */
class Config extends GatewayConfig implements
    \Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Config\ConfigInterface,
    \Webbhuset\CollectorCheckout\Service\Sdk\Payment\Config\ConfigInterface
{
    /**
     * Payment method code also used as part of payment config path:
     * payment/{method_code}/{field}
     */
    const METHOD_CODE = 'collectorbank_checkout';

    /**
     * Group keys
     */
    const GROUP_CONFIGURATION = 'configuration';

    const GROUP_DELIVERYCHECKOUT = 'deliverycheckout';

    /**
     * Field keys
     */
    // Base group
    const BASE_FIELD_ORDER_STATUS = 'order_status';

    // Configuration group
    const CONFIG_FIELD_ACTIVE = 'active';
    const CONFIG_FIELD_DELETE_PENDING_ORDERS = 'delete_pending_orders';
    const CONFIG_FIELD_CREATE_CUSTOMER_ACCOUNT = 'create_customer_account';
    const CONFIG_FIELD_COUNTRY_CODE = 'country_code';
    const CONFIG_FIELD_CUSTOMER_TYPE = 'customer_type';
    const CONFIG_FIELD_DEFAULT_CUSTOMER_TYPE = 'default_customer_type';
    const CONFIG_FIELD_TEST_MODE = 'test_mode';
    const CONFIG_FIELD_CLIENT_ID = 'client_id';
    const CONFIG_FIELD_CLIENT_SECRET = 'client_secret';
    const CONFIG_FIELD_TEST_MODE_CLIENT_SECRET = 'test_mode_client_secret';
    const CONFIG_FIELD_TEST_MODE_CLIENT_ID = 'test_mode_client_id';
    const CONFIG_FIELD_TERMS_URL = 'terms_url';
    const CONFIG_FIELD_ORDER_ACCEPTED_STATUS = 'order_accepted_status';
    const CONFIG_FIELD_ORDER_HOLDED_STATUS = 'order_holded_status';
    const CONFIG_FIELD_ORDER_DENIED_STATUS = 'order_denied_status';
    const CONFIG_FIELD_PROFILE_NAME = 'profile_name';
    const CONFIG_FIELD_PROFILE_NAME_B2B = 'profile_name_b2b';
    const CONFIG_FIELD_B2C = 'b2c';
    const CONFIG_FIELD_B2B = 'b2b';
    const CONFIG_FIELD_TEST_MODE_B2C = 'test_mode_b2c';
    const CONFIG_FIELD_TEST_MODE_B2B = 'test_mode_b2b';
    const CONFIG_FIELD_CUSTOM_BASE_URL = 'custom_base_url';
    const CONFIG_FIELD_NEWSLETTER = 'newsletter';
    const CONFIG_FIELD_NEWSLETTER_TEXT = 'newsletter_text';
    const CONFIG_FIELD_COMMENT = 'comment';
    const CONFIG_FIELD_COMMENT_TEXT = 'comment_text';
    const CONFIG_FIELD_STYLE_DATA_LANG = 'style_data_lang';
    const CONFIG_FIELD_STYLE_DATA_PADDING = 'style_data_padding';
    const CONFIG_FIELD_STYLE_DATA_CONTAINER_ID = 'style_data_container_id';
    const CONFIG_FIELD_STYLE_DATA_ACTION_COLOR = 'style_data_action_color';
    const CONFIG_FIELD_STYLE_DATA_ACTION_TEXT_COLOR = 'style_data_action_text_color';

    // Deliverycheckout group
    const DELIVERYCHECKOUT_FIELD_ACTIVE = 'active';
    const DELIVERYCHECKOUT_FIELD_CUSTOM_DELIVERY_ADAPTER = 'custom_delivery_adapter';
    const DELIVERYCHECKOUT_FIELD_FALLBACK_TITLE = 'fallback_title';
    const DELIVERYCHECKOUT_FIELD_FALLBACK_DESCRIPTION = 'fallback_description';
    const DELIVERYCHECKOUT_FIELD_FALLBACK_PRICE = 'fallback_price';

    /**
     * Url Keys
     */
    const URL_KEY_NOTIFICATION = 'collectorbank/notification/index/reference/{checkout.publictoken}';
    const URL_KEY_VALIDATION = 'collectorbank/validation/index/reference/{checkout.publictoken}';

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var Source\Country\Country
     */
    private $countryData;

    /**
     * @var ?int $storeId
     */
    private $storeId = null;

    /**
     * Customer type of the current checkout, falls back to configured default when not set
     *
     * @var ?int $customerType
     */
    private $customerType = null;

    /**
     * @var \Webbhuset\CollectorCheckout\Oath\AccessKeyManager
     */
    private \Webbhuset\CollectorCheckout\Oath\AccessKeyManager $accessKeyManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Webbhuset\CollectorCheckout\Config\Source\Country\Country $countryData,
        \Webbhuset\CollectorCheckout\Oath\AccessKeyManager $accessKeyManager,
        ?int $storeId = null
    ) {
        $this->storeManager     = $storeManager;
        $this->countryData      = $countryData;
        $this->accessKeyManager = $accessKeyManager;
        $this->storeId = $storeId;
        parent::__construct($scopeConfig, self::METHOD_CODE, self::DEFAULT_PATH_PATTERN);
    }

    /**
     * Returns true if collector payment method is active
     *
     * @return bool
     */
    public function getIsActive(): bool
    {
        return 1 == $this->getConfigurationSectionValue(self::CONFIG_FIELD_ACTIVE);
    }

    public function getAccessKey(): string
    {
        $storeId = $this->getMagentoStoreId();
        return $this->accessKeyManager->getAccessKeyByStore($storeId);
    }

    /**
     * Returns true if delete pending orders
     *
     * @return bool
     */
    public function getDeletePendingOrders(): bool
    {
        return 1 == (int)$this->getConfigurationSectionValue(self::CONFIG_FIELD_DELETE_PENDING_ORDERS);
    }

    /**
     * Returns true if customers accounts should be created for new orders
     *
     * @return bool
     */
    public function getCreateCustomerAccount(): bool
    {
        return 1 == (int)$this->getConfigurationSectionValue(self::CONFIG_FIELD_CREATE_CUSTOMER_ACCOUNT);
    }

    /**
     * Get country code
     *
     * @return string
     */
    public function getCountryCode() : string
    {
        return $this->getConfigurationSectionValue(self::CONFIG_FIELD_COUNTRY_CODE);
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
        $customerType = $this->getCustomerType();

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
        return (int)$this->getConfigurationSectionValue(self::CONFIG_FIELD_CUSTOMER_TYPE);
    }

    /**
     * Get default customer type
     *
     * @return int
     */
    public function getDefaultCustomerType(): int
    {
        return (int)$this->getConfigurationSectionValue(self::CONFIG_FIELD_DEFAULT_CUSTOMER_TYPE);
    }

    /**
     * Get customer type of the current checkout, or the default customer type if not set
     *
     * @return int
     */
    public function getCustomerType(): int
    {
        return $this->customerType ?: $this->getDefaultCustomerType();
    }

    /**
     * Set customer type of the current checkout, used to pick B2C or B2B store id and profile name
     *
     * @param int|null $customerType
     * @return void
     */
    public function setCustomerType(?int $customerType): void
    {
        $this->customerType = $customerType;
    }

    /**
     * Returns true if in test mode
     *
     * @return bool
     */
    public function getIsTestMode(): bool
    {
        return !!$this->getConfigurationSectionValue(self::CONFIG_FIELD_TEST_MODE);
    }

    /**
     * OAuth is now always enabled when using the Walley Checkout API
     *
     * @return bool
     */
    public function getIsOath(): bool
    {
        return true;
    }

    public function getClientId(): string
    {
        if ($this->getIsTestMode()) {
            return $this->getTestModeClientId();
        }

        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_CLIENT_ID);
    }

    public function getClientSecret(): string
    {
        if ($this->getIsTestMode()) {
            return $this->getTestModeClientSecret();
        }

        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_CLIENT_SECRET);
    }

    /**
     * In test mode, OAuth is always enabled
     *
     * @return bool
     */
    public function getIsTestModeOath(): bool
    {
        return $this->getIsTestMode();
    }

    public function getTestModeClientSecret(): string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_TEST_MODE_CLIENT_SECRET);
    }

    public function getTestModeClientId(): string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_TEST_MODE_CLIENT_ID);
    }

    /**
     * Get the url for customer / merchant terms
     *
     * @return string
     */
    public function getMerchantTermsUri(): string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_TERMS_URL);
    }

    /**
     * Get the redirect page url = Success page / thank you page url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRedirectPageUri(): string
    {
        $path = \Webbhuset\CollectorCheckout\Gateway\Config::CHECKOUT_URL_KEY
            . '/success/index/reference/{checkout.publictoken}';

        return $this->getUrl($path);
    }

    private function getUrl($urlKey):string
    {
        $store = $this->storeManager->getStore($this->storeId);
        /** @var \Magento\Store\Model\Store $store */
        $baseUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK);

        return rtrim($baseUrl, '/') . '/' . ltrim($urlKey, '/');
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
            "value" => false,
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
        $urlKey = self::URL_KEY_NOTIFICATION;

        if ($this->getCustomBaseUrl()) {
            return $this->getCustomBaseUrl() . $urlKey;
        }

        return $this->getUrl($urlKey);
    }

    /**
     * Get the validation url - Used by collector when placing orders
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getValidationUri(): string
    {
        $urlKey = self::URL_KEY_VALIDATION;

        if ($this->getCustomBaseUrl()) {
            return $this->getCustomBaseUrl() . $urlKey;
        }

        return $this->getUrl($urlKey);
    }

    /**
     * Get the order status for new orders
     *
     * @return string
     */
    public function getOrderStatusNew(): string
    {
        return $this->getBaseSectionValue(self::BASE_FIELD_ORDER_STATUS);
    }

    /**
     * Get the order status for acknowledged
     *
     * @return string
     */
    public function getOrderStatusAcknowledged(): string
    {
        return $this->getConfigurationSectionValue(self::CONFIG_FIELD_ORDER_ACCEPTED_STATUS);
    }

    /**
     * Get the order status for holded
     *
     * @return string
     */
    public function getOrderStatusHolded(): string
    {
        return $this->getConfigurationSectionValue(self::CONFIG_FIELD_ORDER_HOLDED_STATUS);
    }

    /**
     * Get the order status for denied
     *
     * @return string
     */
    public function getOrderStatusDenied(): string
    {
        return $this->getConfigurationSectionValue(self::CONFIG_FIELD_ORDER_DENIED_STATUS);
    }

    /**
     * Gets B2C store id
     *
     * @return string
     */
    public function getB2CProfileName() : string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_PROFILE_NAME);
    }

    /**
     * Get B2B store id
     *
     * @return string
     */
    public function getB2BProfileName() : string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_PROFILE_NAME_B2B);
    }

    /**
     * Get profile name
     *
     * @return string
     */
    public function getProfileName(): string
    {
        $customerType = $this->getCustomerType();

        if (\Webbhuset\CollectorCheckout\Config\Source\Customer\DefaultType::PRIVATE_CUSTOMERS == $customerType) {
            return $this->getB2CProfileName();
        }

        return $this->getB2BProfileName();
    }

    /**
     * Get production mode store id for B2C
     *
     * @return string
     */
    public function getProductionModeB2C() : string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_B2C);
    }

    /**
     * Get production mode store id for B2B
     *
     * @return string
     */
    public function getProductionModeB2B() : string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_B2B);
    }

    /**
     * Get storeid for b2b for testmode
     *
     * @return string
     */
    public function getTestModeB2C(): string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_TEST_MODE_B2C);
    }

    /**
     * Get storeid for b2b for testmode
     *
     * @return string
     */
    public function getTestModeB2B(): string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_TEST_MODE_B2B);
    }

    /**
     * Returns true if collector delivery checkout is active
     *
     * @return bool
     */
    public function getIsDeliveryCheckoutActive(): bool
    {
        return 1 == (int)$this->getDeliverycheckoutSectionValue(self::DELIVERYCHECKOUT_FIELD_ACTIVE);
    }

    /**
     * Returns true if collector delivery checkout is active
     *
     * @return bool
     */
    public function getIsCustomDeliveryAdapter(): bool
    {
        return 1 == (int)$this->getDeliverycheckoutSectionValue(self::DELIVERYCHECKOUT_FIELD_CUSTOM_DELIVERY_ADAPTER);
    }

    /**
     * Get fallback title
     *
     * @return string
     */
    public function getDeliveryCheckoutFallbackTitle(): string
    {
        return (string)$this->getDeliverycheckoutSectionValue(self::DELIVERYCHECKOUT_FIELD_FALLBACK_TITLE);
    }

    /**
     * Get fallback description
     *
     * @return string
     */
    public function getDeliveryCheckoutFallbackDescription(): string
    {
        return (string)$this->getDeliverycheckoutSectionValue(self::DELIVERYCHECKOUT_FIELD_FALLBACK_DESCRIPTION);
    }

    /**
     * Get fallback price
     *
     * @return float
     */
    public function getDeliveryCheckoutFallbackPrice(): float
    {
        return (float)$this->getDeliverycheckoutSectionValue(self::DELIVERYCHECKOUT_FIELD_FALLBACK_PRICE);
    }

    /**
     * Get the current mode the collector bank payment method is running in
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->getIsTestMode() ? "test mode" : "production mode";
    }

    /**
     * Returns true if collector bank is in testmode
     *
     * @return bool
     */
    public function isTestMode(): bool
    {
        return !!$this->getIsTestMode();
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
     * @return string
     */
    public function getCustomBaseUrl(): string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_CUSTOM_BASE_URL);
    }

    public function isNewsletter(): bool
    {
        return !!$this->getConfigurationSectionValue(self::CONFIG_FIELD_NEWSLETTER);
    }

    public function getNewsletterText(): string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_NEWSLETTER_TEXT);
    }

    public function isComment(): bool
    {
        return !!$this->getConfigurationSectionValue(self::CONFIG_FIELD_COMMENT);
    }

    public function getCommentText(): string
    {
        return (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_COMMENT_TEXT);
    }

    /**
     * Get checkout url
     *
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCheckoutUrl(): string
    {
        $urlKey = \Webbhuset\CollectorCheckout\Gateway\Config::CHECKOUT_URL_KEY;
        $store = $this->storeManager->getStore();
        /** @var \Magento\Store\Model\Store $store */
        $url = $store->getUrl($urlKey);

        return $url;
    }

    /**
     * Get style data-lang an attribute used for styling iframe
     *
     * @return mixed
     */
    public function getStyleDataLang(): string
    {
        $data = (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_STYLE_DATA_LANG);

        return ($data) ? $data : (string)$this->getDefaultLanguage();
    }

    /**
     * Get style data-padding, an attribute used for styling iframe
     *
     * @return int|null
     */
    public function getStyleDataPadding(): ?int
    {
        $data = (int)$this->getConfigurationSectionValue(self::CONFIG_FIELD_STYLE_DATA_PADDING);

        return ($data) ? $data : null;
    }

    /**
     * Get style container-id, an attribute used for styling iframe
     *
     * @return mixed|null
     */
    public function getStyleDataContainerId(): ?string
    {
        $data = (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_STYLE_DATA_CONTAINER_ID);

        return ($data) ? $data : null;
    }

    /**
     * Get style data-action-color, an attribute used for styling iframe
     *
     * @return string|null
     */
    public function getStyleDataActionColor(): ?string
    {
        $data = (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_STYLE_DATA_ACTION_COLOR);

        return ($data) ? $data : null;
    }

    /**
     * Get style data-action-text-color, an attribute used for styling iframe
     *
     * @return string|null
     */
    public function getStyleDataActionTextColor(): ?string
    {
        $data = (string)$this->getConfigurationSectionValue(self::CONFIG_FIELD_STYLE_DATA_ACTION_TEXT_COLOR);

        return ($data) ? $data : null;
    }

    /**
     * Get default currency code for the selected country
     *
     * @return string
     */
    public function getCurrency(): string
    {
        $currencies = $this->countryData->getCurrencyPerCountry();
        $countryCode = $this->getCountryCode();

        return (string)$currencies[$countryCode];
    }

    /**
     * @param int|null $storeId
     * @return void
     */
    public function setStoreId(?int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Get default language code for the selected country
     *
     * @return string
     */
    private function getDefaultLanguage(): string
    {
        $language = $this->countryData->getDefaultLanguagePerCounty();
        $countryCode = $this->getCountryCode();

        return (string)$language[$countryCode];
    }

    /**
     * @param string $field
     * @return mixed
     */
    private function getConfigurationSectionValue(string $field)
    {
        return $this->getValue(
            sprintf('%s/%s', self::GROUP_CONFIGURATION, $field),
            $this->getMagentoStoreId()
        );
    }

    /**
     * @param string $field
     * @return mixed
     */
    private function getDeliverycheckoutSectionValue(string $field)
    {
        return $this->getValue(
            sprintf('%s/%s', self::GROUP_DELIVERYCHECKOUT, $field),
            $this->getMagentoStoreId()
        );
    }

    /**
     * @param string $field
     * @return mixed
     */
    private function getBaseSectionValue(string $field)
    {
        return $this->getValue(
            $field,
            $this->getMagentoStoreId()
        );
    }

    /**
     * @return int
     */
    private function getMagentoStoreId(): int
    {
        if (null === $this->storeId) {
            $this->storeId = $this->storeManager->getStore()->getId();
        }

        return (int)$this->storeId;
    }
}
