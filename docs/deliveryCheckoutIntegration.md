## Delivery checkout integration

#### V1.0.4

When an order is placed using the delivery checkout, shipping / unifaun information is saved on the order and displayed in the order view. 

To fetch this information for use in other systems, e.g. business systems, there is a Api Repository that can be used located in the module's Api folder.

This repository provides two methods:

```
interface CarrierDataRepositoryInterface
{
    /**
     * Get carrier data from order id
     *
     * @param int $orderId
     *
     * @return \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface
     */
    public function get(int $orderId);

    /**
     * Save carrier data on order
     *
     * @param \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface $carrierData
     * @param int $orderId
     *
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function save(\Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface $carrierData, int $orderId);
}
```

To retrieve the shipping information, use dependency injection in your constructor like:

```
__construct(
...
\Webbhuset\CollectorCheckout\Api\CarrierDataRepositoryInterface $carrierRepository
...
) {
...
$this->carrierRepository = $carrierRepository;
...
}
...

/** @var \Webbhuset\CollectorCheckout\Api\Data\CarrierDataInterface $carrierDataInterface */
$carrierDataInterface = $this->carrierRepository->get(ORDER_ID_HERE);
$carrierDataArray = $carrierDataInterface->getData();

...
```

You will then end up with an array with the shipping information that has been saved which you can use together with the unifaun API keys to perform actions for this shipment against the unifaun API.