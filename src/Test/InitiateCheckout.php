<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Test;

use Magento\Store\Model\App\Emulation;
use Webbhuset\CollectorCheckout\Adapter;
use Webbhuset\CollectorCheckout\Config\Config;
use Webbhuset\CollectorCheckout\Test\Data\GetCart;
use Webbhuset\CollectorCheckout\Test\Data\GetFees;
use Webbhuset\CollectorCheckoutSDK\Session;

class InitiateCheckout
{
    /**
     * @var Emulation
     */
    private $emulation;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Adapter
     */
    private $adapter;
    /**
     * @var GetCart
     */
    private $getCart;
    /**
     * @var GetFees
     */
    private $getFees;

    public function __construct(
        Emulation $emulation,
        Adapter $adapter,
        Config $config,
        GetCart $getCart,
        GetFees $getFees
    ) {
        $this->emulation = $emulation;
        $this->config = $config;
        $this->adapter = $adapter;
        $this->getCart = $getCart;
        $this->getFees = $getFees;
    }

    public function execute(int $storeId):string
    {
        $this->emulation->startEnvironmentEmulation($storeId);

        $config = $this->config;
        $adapter = $this->adapter->getAdapter($config);
        $session = new Session($adapter);
        $result = $session->initialize(
            $config,
            $this->getFees->execute(),
            $this->getCart->execute(),
            $config->getCountryCode()
        );

        $this->emulation->stopEnvironmentEmulation();

        return "privateId: " . $result->getPrivateId();
    }
}
