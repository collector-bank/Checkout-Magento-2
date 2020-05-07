<?php


namespace Webbhuset\CollectorCheckout\Carrier;


/**
 * Class CarrierDataRepository
 *
 * @package Webbhuset\CollectorCheckout\Carrier
 */
class CarrierDataRepository implements \Webbhuset\CollectorCheckout\Api\CarrierDataRepositoryInterface
{
    /**
     * @var \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterfaceFactory
     */
    protected $carrierDataInterfaceFactory;
    /**
     * @var \Webbhuset\CollectorCheckout\Data\OrderHandler
     */
    protected $orderHandler;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * CarrierDataRepository constructor.
     *
     * @param \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterfaceFactory $carrierDataInterfaceFactory
     * @param \Webbhuset\CollectorCheckout\Data\OrderHandler                    $orderHandler
     * @param \Magento\Sales\Api\OrderRepositoryInterface                       $orderRepository
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterfaceFactory $carrierDataInterfaceFactory,
        \Webbhuset\CollectorCheckout\Data\OrderHandler $orderHandler,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->carrierDataInterfaceFactory = $carrierDataInterfaceFactory;
        $this->orderHandler = $orderHandler;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     */
    public function get(int $orderId)
    {
        $order = $this->orderRepository->get($orderId);

        $shippingData = $this->orderHandler->getDeliveryCheckoutShipmentData($order);

        /** @var \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface $carrierData */
        $carrierData = $this->carrierDataInterfaceFactory->create();
        $carrierData->setData($shippingData);

        return $carrierData;
    }

    /**
     * @inheritDoc
     */
    public function save(
        \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface $carrierData,
        int $orderId
    ) {
        $order = $this->orderRepository->get($orderId);
        $shippingData = $carrierData->getData();
        $this->orderHandler->setDeliveryCheckoutShipmentData($order, $shippingData);

        return $this->orderRepository->save($order);
    }

}