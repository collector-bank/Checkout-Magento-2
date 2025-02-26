<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Carrier;


use Webbhuset\CollectorCheckout\Api\CarrierDataRepositoryInterface;

class GetBookedShipmentIdByOrder
{
    private CarrierDataRepositoryInterface $carrierDataRepository;

    public function __construct(
        CarrierDataRepositoryInterface $carrierDataRepository
    ) {
        $this->carrierDataRepository = $carrierDataRepository;
    }

    public function execute(int $orderId):?string
    {
        $carrierData = $this->carrierDataRepository->get($orderId);
        $data = $carrierData->getData();
        if (isset($data['shipments'][0]) && $data['shipments'][0]->bookedShipmentId) {
            return $data['shipments'][0]->bookedShipmentId;
        }
        return null;
    }
}
