<?php

namespace Webbhuset\CollectorCheckout\Controller\Index;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $pageFactory;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Webbhuset\CollectorCheckout\Adapter
     */
    protected $collectorAdapter;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteConverter
     */
    protected $quoteConverter;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config
     */
    protected $config;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteValidator
     */
    protected $quoteValidator;
    /**
     * @var \Webbhuset\CollectorCheckout\QuoteComparerFactory
     */
    protected $quoteComparer;

    /**
     * @var \Webbhuset\CollectorCheckout\QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * Index constructor.
     *
     * @param \Magento\Framework\App\Action\Context                 $context
     * @param \Magento\Checkout\Model\Session                       $checkoutSession
     * @param \Webbhuset\CollectorCheckout\Adapter              $collectorAdapter
     * @param \Webbhuset\CollectorCheckout\Data\QuoteHandler    $quoteDataHandler
     * @param \Webbhuset\CollectorCheckout\QuoteConverter       $quoteConverter
     * @param \Magento\Framework\View\Result\PageFactory            $pageFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface            $quoteRepository
     * @param \Webbhuset\CollectorCheckout\Config\Config        $config
     * @param \Webbhuset\CollectorCheckout\QuoteValidator       $quoteValidator
     * @param \Webbhuset\CollectorCheckout\QuoteComparerFactory $quoteComparer
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Webbhuset\CollectorCheckout\Adapter $collectorAdapter,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler,
        \Webbhuset\CollectorCheckout\QuoteConverter $quoteConverter,
        \Webbhuset\CollectorCheckout\QuoteUpdater $quoteUpdater,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Webbhuset\CollectorCheckout\QuoteValidator $quoteValidator,
        \Webbhuset\CollectorCheckout\QuoteComparerFactory $quoteComparer
    ) {
        $this->pageFactory      = $pageFactory;
        $this->checkoutSession  = $checkoutSession;
        $this->collectorAdapter = $collectorAdapter;
        $this->quoteDataHandler = $quoteDataHandler;
        $this->quoteConverter   = $quoteConverter;
        $this->quoteRepository  = $quoteRepository;
        $this->config           = $config;
        $this->quoteValidator   = $quoteValidator;
        $this->quoteComparer    = $quoteComparer;
        $this->quoteUpdater     = $quoteUpdater;

        return parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $page = $this->pageFactory->create();
        $quote = $this->checkoutSession->getQuote();

        if (!$this->quoteComparer->create()->isCurrencyMatching()) {
            $this->messageManager->addErrorMessage(__('Currencies are not matching with what is allowed in Walley checkout'));
        }

        $quoteCheckoutErrors = $this->quoteValidator->getErrors($quote);
        if (!empty($quoteCheckoutErrors)) {
            return $this->resultRedirectFactory->create()->setPath('checkout/index');
        }

        $customerType = $this->getRequest()->getParam('customerType');
        $customerType = $customerType ? (int) $customerType : null;

        if ($this->customerTypeChanged($quote, $customerType)) {
            $this->quoteUpdater->setCustomerTypeData($quote, (int) $customerType);
        }

        $publicToken = $this->collectorAdapter->initOrSync($quote);

        $iframeConfig = new \Webbhuset\CollectorCheckoutSDK\Config\IframeConfig(
            $publicToken,
            $this->config->getStyleDataLang(),
            $this->config->getStyleDataPadding(),
            $this->config->getStyleDataContainerId(),
            $this->config->getStyleDataActionColor(),
            $this->config->getStyleDataActionTextColor()
        );
        $iframe = \Webbhuset\CollectorCheckoutSDK\Iframe::getScript($iframeConfig, $this->config->getMode());

        $iframeSrc = $iframeConfig->getSrc($this->config->getMode());
        $iframeToken = $iframeConfig->getDataToken();

        if ($this->config->getIsDeliveryCheckoutActive()) {
            $page->getConfig()->addBodyClass('delivery-checkout');
        }

        if ($this->config->getDisplayCheckoutVersion() != 'v1') {
            $dataVersion = 'v2';
        } else {
            $dataVersion = 'v1';
        }

        $block = $page
            ->getLayout()
            ->getBlock('collectorbank_checkout_iframe')
            ->setIframe($iframe)
            ->setDataToken($iframeToken)
            ->setDataVersion($dataVersion)
            ->setIframeSrc($iframeSrc);

        return $page;
    }

    /**
     * Check if customer typ is changed
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param integer $customerType
     * @return bool
     */
    public function customerTypeChanged(\Magento\Quote\Model\Quote $quote, int $customerType = null)
    {
        $canChangeCustomerType = \Webbhuset\CollectorCheckout\Config\Source\Customer\Type::BOTH_CUSTOMERS == $this->config->getCustomerTypeAllowed();

        if (!$canChangeCustomerType) {
            return false;
        }

        $availableCustomerTypes = [
            \Webbhuset\CollectorCheckout\Config\Source\Customer\Type::PRIVATE_CUSTOMERS,
            \Webbhuset\CollectorCheckout\Config\Source\Customer\Type::BUSINESS_CUSTOMERS,
        ];

        if (!$customerType || !in_array($customerType, $availableCustomerTypes)) {
            return false;
        }

        $currentCustomerType = (int) $this->quoteDataHandler->getCustomerType($quote);

        if ($currentCustomerType === $customerType) {
            return false;
        }

        return true;
    }
}
