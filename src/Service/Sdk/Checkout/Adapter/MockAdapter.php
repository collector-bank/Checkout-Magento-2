<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Service\Sdk\Checkout\Adapter;

/**
 * Keeps checkout data in memory for SDK examples without sending requests.
 */
class MockAdapter extends CurlWithAccessKey
{
    /** @var array */
    private $data = [];

    /**
     * Initialize an example checkout.
     *
     * @param array $data
     * @return array
     */
    public function initializeCheckout(array $data): array
    {
        $this->data = array_merge($data, [
            'privateId' => 'mock-private-id',
            'publicToken' => 'mock-public-token',
            'paymentUri' => 'https://example.com/payment',
            'expiresAt' => '2099-01-01T00:00:00Z',
            'status' => 'Initialized',
        ]);

        return $this->acquireInformation('mock-private-id');
    }

    /**
     * Update the example cart.
     *
     * @param array $data
     * @param string $privateId
     * @return array
     */
    public function updateCart(array $data, string $privateId): array
    {
        $this->data['cart'] = $data;

        return $this->acquireInformation($privateId);
    }

    /**
     * Update the example fees.
     *
     * @param array $data
     * @param string $privateId
     * @return array
     */
    public function updateFees(array $data, string $privateId): array
    {
        $this->data['fees'] = $data;

        return $this->acquireInformation($privateId);
    }

    /**
     * Update the example order reference.
     *
     * @param string $reference
     * @param string $privateId
     * @return array
     */
    public function setOrderReference(string $reference, string $privateId): array
    {
        $this->data['reference'] = $reference;

        return $this->acquireInformation($privateId);
    }

    /**
     * Read the example checkout.
     *
     * @param string $privateId
     * @return array
     */
    public function acquireInformation(string $privateId): array
    {
        return ['id' => $privateId, 'data' => $this->data, 'error' => null];
    }
}
