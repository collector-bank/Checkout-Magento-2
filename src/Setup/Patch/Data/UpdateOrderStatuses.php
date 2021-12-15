<?php

declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Setup\Patch\Data;

class UpdateOrderStatuses implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    private $setup;
    private $statusFactory;

    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup,
        \Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory $statusFactory
    ) {
        $this->setup = $setup;
        $this->statusFactory = $statusFactory;
    }

    public function apply()
    {
        $this->setup->startSetup();

        $statusCollection = $this->statusFactory->create();
        $models = $statusCollection->addFieldToFilter('status', ['like' => 'collectorbank_%']);
        foreach($models as $model){
            $newLabel = str_replace(['collector', 'Collector'], 'Walley', $model->getLabel());
            $model->setLabel($newLabel);
        }
        $models->save();

        $this->setup->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

}
