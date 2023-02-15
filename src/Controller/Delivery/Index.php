<?php

namespace Webbhuset\CollectorCheckout\Controller\Delivery;

use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\ShippingMethodManagement;
use Webbhuset\CollectorCheckout\Checkout\Quote\Manager;
use Webbhuset\CollectorCheckout\Shipment\ConvertToShipment;

/**
 * Class Index
 *
 * @package Webbhuset\CollectorCheckout\Controller\Update
 */
class Index extends Action
{
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
     * @var \Webbhuset\CollectorCheckout\Shipment\GetIconForShippingMethod
     */
    private $getIconForShippingMethod;

    public function __construct(
        Context $context,
        Manager $quoteManager,
        ShippingMethodManagement $shippingMethodManagement,
        ConvertToShipment $convertToShipment,
        RequestInterface $request,
        Json $json,
        SimpleDataObjectConverter $simpleDataObjectConverter,
        \Webbhuset\CollectorCheckout\Shipment\GetIconForShippingMethod $getIconForShippingMethod,
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
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $this->getIconForShippingMethod->execute('test');

        $result = $this->resultJsonFactory->create();
        $privateId = $this->request->getParam('privateId');
        try {
            $quote = $this->quoteManager->getQuoteByPrivateId($privateId);
            $shippingMethods = $this->shippingMethodManagement->estimateByExtendedAddress(
                $quote->getId(),
                $quote->getShippingAddress()
            );
            $shipment = $this->convertToShipment->execute($shippingMethods);
        } catch (NoSuchEntityException $e) {
            $shipment = [];
        }
        $result->setData($shipment);

        return $result;
    }
}
