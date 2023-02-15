<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Shipment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class GetIconForShippingMethod
{
    const ICON_MAPPER_CONFIG = 'payment/collectorbank_checkout/deliverycheckout/icons';
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        JsonSerializer $jsonSerializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->jsonSerializer = $jsonSerializer;
    }

    public function execute(string $code):string
    {
        $icons = $this->getIconDataField();
        if (!isset($icons[$code])) {
            return 'default';
        }

        return $icons[$code];
    }

    private function getIconDataField()
    {
        $configValue = $this->scopeConfig->getValue(self::ICON_MAPPER_CONFIG);
        if($configValue == '' || $configValue == null) {
            return [];
        }

        $methods = $this->jsonSerializer->unserialize($configValue);
        $result = [];
        foreach ($methods as $method) {
            $result[$method['method']] = $method['icon'];
        }

        return $result;
    }
}
