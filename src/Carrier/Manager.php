<?php


namespace Webbhuset\CollectorCheckout\Carrier;


/**
 * Class Manager
 *
 * @package Webbhuset\CollectorCheckout\Carrier
 */
class Manager
{
    /**
     * @var \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterfaceFactory
     */
    protected $carrierDataInterfaceFactory;
    /**
     * @var \Webbhuset\CollectorCheckout\Api\CarrierDataRepositoryInterface
     */
    protected $carrierDataRepository;

    /**
     * Manager constructor.
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Api\CarrierDataRepositoryInterface $carrierDataRepository,
        \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterfaceFactory $carrierDataInterfaceFactory
    ) {
        $this->carrierDataRepository = $carrierDataRepository;
        $this->carrierDataInterfaceFactory = $carrierDataInterfaceFactory;
    }

    /**
     * Saves shipment data on an order
     *
     * @param int                                          $orderId
     * @param \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function saveShipmentDataOnOrder(
        int $orderId,
        \Webbhuset\CollectorCheckoutSDK\CheckoutData $checkoutData
    ) {
        /** @var \Webbhuset\CollectorCheckoutSDK\Checkout\Shipping $shipping */
        $shipping = $checkoutData->getShipping();
        $shippingData = $shipping->getData();

        /** @var \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface $carrierData */
        $carrierData = $this->carrierDataInterfaceFactory->create();
        $carrierData->setData($shippingData);

        return $this->carrierDataRepository->save($carrierData, $orderId);
    }
}