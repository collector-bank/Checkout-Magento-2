<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Checkout;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Webbhuset\CollectorCheckout\Adapter as CheckoutInitiator;
use Webbhuset\CollectorCheckout\Api\Data\IframeDataInterface;
use Webbhuset\CollectorCheckout\Api\Data\IframeDataInterfaceFactory;
use Webbhuset\CollectorCheckout\Api\GetIframeSrcInterface;
use Webbhuset\CollectorCheckout\Config\QuoteConfig;
use Webbhuset\CollectorCheckout\Config\QuoteConfigFactory;

class GetIframeSrc implements GetIframeSrcInterface
{
    private QuoteIdMaskFactory $quoteIdMaskFactory;
    private CartRepositoryInterface $cartRepository;
    /**
     * @var IframeDataInterfaceFactory
     */
    private IframeDataInterfaceFactory $iframeDataInterfaceFactory;
    private CheckoutInitiator $checkoutInitiator;
    private QuoteConfigFactory $configFactory;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        CartRepositoryInterface $cartRepository,
        IframeDataInterfaceFactory $iframeDataInterfaceFactory,
        CheckoutInitiator $checkoutInitiator,
        QuoteConfigFactory $configFactory
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->cartRepository = $cartRepository;
        $this->iframeDataInterfaceFactory = $iframeDataInterfaceFactory;
        $this->checkoutInitiator = $checkoutInitiator;
        $this->configFactory = $configFactory;
    }

    public function execute(string $cartId): IframeDataInterface
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        /** @var Quote $quote */
        $quote = $this->cartRepository->getActive($quoteIdMask->getQuoteId());

        $publicToken = $this->checkoutInitiator->initOrSync($quote);

        /** @var QuoteConfig $config */
        $config = $this->configFactory->create(['quote' => $quote]);

        $iframeConfig = new \Webbhuset\CollectorCheckoutSDK\Config\IframeConfig(
            $publicToken,
            $config->getStyleDataLang(),
            $config->getStyleDataPadding(),
            $config->getStyleDataContainerId(),
            $config->getStyleDataActionColor(),
            $config->getStyleDataActionTextColor()
        );

        if ($config->getDisplayCheckoutVersion() != 'v1') {
            $dataVersion = 'v2';
        } else {
            $dataVersion = 'v1';
        }

        /** @var IframeDataInterface $iframeData */
        $iframeData = $this->iframeDataInterfaceFactory->create();
        $iframeData->setSrc($iframeConfig->getSrc($config->getMode()) ?? '')
            ->setDataToken($iframeConfig->getDataToken() ?? '')
            ->setDataVersion($dataVersion)
            ->setDataLang($iframeConfig->getDataLang() ?? '')
            ->setDataActionColor($config->getStyleDataActionColor() ?? '')
            ->setDataActionTextColor($config->getStyleDataActionTextColor() ?? '')
        ;

        return $iframeData;
    }
}
