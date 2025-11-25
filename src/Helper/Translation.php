<?php

namespace Webbhuset\CollectorCheckout\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\TranslateInterface;
use Magento\Store\Model\ScopeInterface;

class Translation
{
    /**
     * @var TranslateInterface
     */
    protected $translator;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Translation constructor.
     *
     * @param TranslateInterface $translator
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        TranslateInterface $translator,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->translator = $translator;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get translated label for a specific store
     *
     * @param string $label
     * @param int $storeId
     * @return string
     */
    public function getLabelByStoreId(string $label, int $storeId): string
    {
        $storeLocale = $this->scopeConfig->getValue(
            'general/locale/code',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $currentLocale = $this->translator->getLocale();

        $this->translator->setLocale($storeLocale);
        $this->translator->loadData(null, true);

        $translatedLabel = (string)__($label);

        $this->translator->setLocale($currentLocale);
        $this->translator->loadData(null, true);

        return $translatedLabel;
    }
}

