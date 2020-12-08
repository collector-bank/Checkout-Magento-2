<?php

namespace Webbhuset\CollectorCheckout\Setup;

/**
 * Class InstallData
 *
 * @package Webbhuset\CollectorCheckout\Setup
 */
class InstallData implements \Magento\Framework\Setup\InstallDataInterface
{
    /**
     * @var \Magento\Sales\Model\Order\StatusFactory
     */
    protected $statusFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\StatusFactory
     */
    protected $statusResourceFactory;

    /**
     * InstallData constructor.
     *
     * @param \Magento\Sales\Model\Order\StatusFactory               $statusFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\StatusFactory $statusResourceFactory
     */
    public function __construct(
        \Magento\Sales\Model\Order\StatusFactory $statusFactory,
        \Magento\Sales\Model\ResourceModel\Order\StatusFactory $statusResourceFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    /**
     * Install data
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface   $context
     * @throws \Exception
     */
    public function install(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Framework\Setup\ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $status = $this->statusFactory->create();
        $statusResourceFactory = $this->statusResourceFactory->create();

        $orderStatuses = [];
        $orderStatuses[\Magento\Sales\Model\Order::STATE_NEW] = [
            'status' => 'collectorbank_new',
            'label' => 'Collector Bank - Payment Review'
        ];
        $orderStatuses[\Magento\Sales\Model\Order::STATE_PROCESSING] = [
            'status' => 'collectorbank_acknowledged',
            'label' => 'Collector Bank - Acknowledged'
        ];
        $orderStatuses[\Magento\Sales\Model\Order::STATE_HOLDED] = [
            'status' => 'collectorbank_onhold',
            'label' => 'Collector Bank - On Hold'
        ];
        $orderStatuses[\Magento\Sales\Model\Order::STATE_CANCELED] = [
            'status' => 'collectorbank_canceled',
            'label' => 'Collector Bank - Cancelled'
        ];

        foreach ($orderStatuses as $state => $orderStatus) {
            $status->setData($orderStatus);
            try {
                $statusResourceFactory->save($status);
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                continue;
            }
            $status->assignState($state, false, true);
        }
        $setup->endSetup();
    }
}
