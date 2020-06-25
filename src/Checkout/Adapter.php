<?php

namespace Webbhuset\CollectorCheckout\Checkout;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Adapter
 *
 * @package Webbhuset\CollectorCheckout\Checkout
 */
class Adapter extends \Magento\Payment\Model\Method\Adapter
{
    protected $config;
    /**
     * Adapter constructor.
     */
    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        \Webbhuset\CollectorCheckout\Config\Config $config,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    ) {
        $this->config = $config;

        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code, $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );
    }

    public function canVoid()
    {
        $additionalInformation = $this->getInfoInstance()->getAdditionalInformation();

        if (isset($additionalInformation['payment_name'])
            && \Webbhuset\CollectorCheckout\Gateway\Config::PAYMENT_METHOD_SWISH == $additionalInformation['payment_name']) {

            return false;
        }

        return parent::canVoid();
    }

    public function isActive($storeId = 0)
    {
        return $this->config->getIsActive();
    }
}
