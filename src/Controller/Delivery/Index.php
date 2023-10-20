<?php

namespace Webbhuset\CollectorCheckout\Controller\Delivery;

use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\ShippingMethodManagement;
use Webbhuset\CollectorCheckout\Checkout\Quote\Manager;
use Webbhuset\CollectorCheckout\Logger\Logger;
use Webbhuset\CollectorCheckout\Shipment\ConvertToShipment;
use Webbhuset\CollectorCheckout\Shipment\GetIconForShippingMethod;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Update
 */
class Index extends Action
{
    const CACHE_TTL = 60*5;
    const CACHE_NAME = 'WALLEY_DELIVERY_RESPONSE';
    const CACHE_TAGS = 'WALLEY_DELIVERY';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var Manager
     */
    private $quoteManager;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ShippingMethodManagement
     */
    private $shippingMethodManagement;
    /**
     * @var ConvertToShipment
     */
    private $convertToShipment;
    /**
     * @var SimpleDataObjectConverter
     */
    private $simpleDataObjectConverter;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var GetIconForShippingMethod
     */
    private $getIconForShippingMethod;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var Context
     */
    private $context;
    /**
     * @var Logger
     */
    private Logger $logger;
    private CacheInterface $cache;

    public function __construct(
        Context $context,
        Manager $quoteManager,
        ShippingMethodManagement $shippingMethodManagement,
        ConvertToShipment $convertToShipment,
        Logger $logger,
        CacheInterface $cache,
        RequestInterface $request,
        Json $json,
        CartRepositoryInterface $cartRepository,
        SimpleDataObjectConverter $simpleDataObjectConverter,
        GetIconForShippingMethod $getIconForShippingMethod,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteManager = $quoteManager;
        $this->request = $request;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->convertToShipment = $convertToShipment;
        $this->simpleDataObjectConverter = $simpleDataObjectConverter;
        $this->json = $json;
        $this->getIconForShippingMethod = $getIconForShippingMethod;
        $this->cartRepository = $cartRepository;
        $this->context = $context;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $params = $this->request->getParams();
        $key = sha1(\json_encode($params));
        $cachedResponse = $this->cache->load($key);
        if ($cachedResponse) {
            $shipment = \json_decode($cachedResponse, true);
            $result->setData($shipment);

            return $result;
        }

        $this->getIconForShippingMethod->execute('test');


        $privateId = $this->request->getParam('privateId');


        $this->logger->addCritical("Delivery adapter request: ", $this->request->getParams());
        try {
            $quote = $this->quoteManager->getQuoteByPrivateId($privateId);
            $shippingAddress = $quote->getShippingAddress();
            $postalCode = $this->request->getParam('postalCode');
            if ($postalCode) {
                $shippingAddress->setPostcode($postalCode);
            }
            $countryCode = $this->request->getParam('countryCode');
            if ($countryCode) {
                $shippingAddress->setCountryId($countryCode);
            }
            $shippingMethods = $this->shippingMethodManagement->estimateByExtendedAddress(
                $quote->getId(),
                $quote->getShippingAddress()
            );
            if ($countryCode || $postalCode) {
                $this->cartRepository->save($quote);
            }

            $shipment = $this->convertToShipment->execute($shippingMethods);
        } catch (NoSuchEntityException $e) {
            $shipment = [];
        }
        $this->logger->addCritical("Delivery adapter response:", $shipment);
        $result->setData($shipment);

        $shipmentDecoded = \json_encode($shipment);
        $this->cache->save($shipmentDecoded, $key, [self::CACHE_TAGS],self::CACHE_TTL);

        return $result;
    }
}
