<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Shipment;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;

class GetBadgesForShippingMethod
{
    const BADGES_MAPPER_CONFIG = 'payment/collectorbank_checkout/deliverycheckout/badges';
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

    public function execute(string $code, int $storeId):array
    {
        $badges = $this->getBadgesDataFieldValue($storeId);
        if (!isset($badges[$code])) {
            return [];
        }

        return $badges[$code];
    }

    private function getBadgesDataFieldValue(int $storeId)
    {
        $configValue = $this->scopeConfig->getValue(self::BADGES_MAPPER_CONFIG, 'store', $storeId);
        if($configValue == '' || $configValue == null) {
            return [];
        }

        $methods = $this->jsonSerializer->unserialize($configValue);
        $result = [];
        foreach ($methods as $method) {
            if (!isset($method['method'])
                || !isset($method['color'])
                || !isset($method['text'])
            ) {
                continue;
            }
            $result[$method['method']][] = [
                'text' => $method['text'],
                'color' => $method['color']
            ];
        }

        return $result;
    }
}
