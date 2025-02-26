<?php


namespace Webbhuset\CollectorCheckout\Block\Admin;


use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class Carrier
 *
 * @package Webbhuset\CollectorCheckout\Block\Admin
 */
class Carrier extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config
     */
    protected $config;

    /**
     * @var \Webbhuset\CollectorCheckout\Carrier\CarrierDataRepository
     */
    protected $carrierDataRepository;
    private \Webbhuset\CollectorCheckout\Carrier\GetBookedShipmentIdByOrder $getBookedShipmentIdByOrder;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Webbhuset\CollectorCheckout\Carrier\CarrierDataRepository $carrierDataRepository
     * @param \Webbhuset\CollectorCheckout\Config\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Webbhuset\CollectorCheckout\Carrier\GetBookedShipmentIdByOrder $getBookedShipmentIdByOrder,
        \Webbhuset\CollectorCheckout\Carrier\CarrierDataRepository $carrierDataRepository,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        array $data = []
    ) {
        parent::__construct($context, $registry, $adminHelper, $data);

        $this->getBookedShipmentIdByOrder = $getBookedShipmentIdByOrder;
        $this->carrierDataRepository = $carrierDataRepository;
        $this->config = $config;
    }

    /**
     * Get carrier data for an order
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCarrierInformation()
    {
        $orderId = $this->getOrder()->getId();
        $carrierData = $this->carrierDataRepository->get($orderId);

        return $carrierData->getData();
    }

    /**
     * Returns true of collector delivery checkout is active otherwise false
     *
     * @return bool
     */
    public function isDeliveryCheckoutActive()
    {
        return $this->config->getIsDeliveryCheckoutActive();
    }

    public function getBookedShipmentId(int $orderId):?string
    {
        return $this->getBookedShipmentIdByOrder->execute($orderId);
    }

    /**
     * @return bool
     */
    public function isCustomDeliveryAdapterActive()
    {
        return (bool) $this->config->getIsCustomDeliveryAdapter();
    }
}
