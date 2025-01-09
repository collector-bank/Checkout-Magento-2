<?php

namespace Webbhuset\CollectorCheckout\Carrier;

/**
 * Class Collector
 *
 * @package Webbhuset\CollectorCheckout\Carrier
 */
class Collector extends \Magento\Shipping\Model\Carrier\AbstractCarrierOnline implements \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'collectorshipping';

    /**
     *
     */
    const GATEWAY_KEY = 'collectorshipping';

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $rateResultFactory;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\QuoteHandler
     */
    protected $quoteDataHandler;
    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $rateResultMethodFactory;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Webbhuset\CollectorCheckout\QuoteConverter
     */
    protected $quoteConverter;
    private \Webbhuset\CollectorCheckout\Shipment\DeliveryCheckoutData $deliveryCheckoutData;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Xml\Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateResultMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Webbhuset\CollectorCheckout\Shipment\DeliveryCheckoutData $deliveryCheckoutData,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Webbhuset\CollectorCheckout\Data\QuoteHandler $quoteDataHandler,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Webbhuset\CollectorCheckout\QuoteConverter $quoteConverter,
        array $data = []
    ) {
        $this->rateResultFactory        = $rateResultFactory;
        $this->rateResultMethodFactory  = $rateResultMethodFactory;
        $this->quoteDataHandler         = $quoteDataHandler;
        $this->quoteRepository          = $quoteRepository;
        $this->quoteConverter           = $quoteConverter;

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateResultFactory,
            $rateResultMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
        $this->deliveryCheckoutData = $deliveryCheckoutData;
    }

    /**
     * @inheritDoc
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        return $request;
    }

    /**
     * @inheritDoc
     */
    public function processAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAllowedMethods()
    {
        return [];
    }

    /**
     * Get shipping method based on a quote and the information in collector checkout data
     *
     * @param int $quoteId
     * @return array|\Magento\Quote\Model\Quote\Address\RateResult\Method
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMethodForQuote(int $quoteId)
    {
        if ($this->deliveryCheckoutData->getData()) {
            $shippingData = $this->deliveryCheckoutData->getData();
        } else {
            $quote = $this->quoteRepository->get($quoteId);
            $shippingData = $this->quoteDataHandler->getDeliveryCheckoutData($quote);
        }

        if(empty($shippingData)) {
            return [];
        }

        $price = $shippingData['unitPrice'];
        $title = $shippingData['id'];
        $description = $shippingData['description'];

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateResultMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($description);
        $method->setMethodDescription($description);
        $method->setMethod($this->_code);
        $method->setMethodTitle($description);
        $method->setPrice($price);

        return $method;
    }

    /**
     * @inheritDoc
     */
    public function collectRates(\Magento\Quote\Model\Quote\Address\RateRequest $request)
    {
        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        $quoteItems = $request->getAllItems();
        if (empty($quoteItems) || !isset($quoteItems[0])){

            return $result;
        }

        /** @var \Magento\Quote\Model\Quote\Item\Interceptor $quote */
        $quote = $quoteItems[0];
        $quoteId = $quote->getQuoteId();
        if (!$quoteId) {
            return $result;
        }

        $method = $this->getMethodForQuote($quoteId);
        if (!empty($method)) {
            $result->append($method);
        }

        return $result;
    }

}
