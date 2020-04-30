<?php

namespace Webbhuset\CollectorCheckout\Gateway;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class Config
 *
 * @package Webbhuset\CollectorCheckout\Gateway
 */
class Config implements ConfigProviderInterface
{
    /**
     * The method code of collector bank payment method
     */
    const CHECKOUT_CODE = "collectorbank_checkout";
    /**
     *
     */
    const PAYMENT_METHOD_NAME = "Collector Bank Checkout";
    /**
     * The method code of collector bank payment method
     */
    const CHECKOUT_URL_KEY = "collectorcheckout";
    /**
     * Remove order older than this number
     */
    const REMOVE_PENDING_ORDERS_HOURS = 5;

    /**
     * @var \Webbhuset\CollectorCheckout\Config\Config
     */
    protected $config;
    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepo;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Config constructor.
     *
     * @param \Webbhuset\CollectorCheckout\Config\Config $config
     * @param \Magento\Framework\View\Asset\Repository       $assetRepo
     * @param \Magento\Framework\UrlInterface                $urlBuilder
     * @param \Magento\Framework\App\RequestInterface        $request
     */
    public function __construct(
        \Webbhuset\CollectorCheckout\Config\Config $config,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->config = $config;
        $this->assetRepo = $assetRepo;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    /**
     * Returns an array with javascript config used in the frontend
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->config->getIsActive()) {
            return [];
        }

        return [
            'payment' => [
                'collector_checkout' => [
                    'image_remove_item' => $this->getViewFileUrl('Webbhuset_CollectorCheckout::images/times-solid.svg'),
                    'image_plus_qty' => $this->getViewFileUrl('Webbhuset_CollectorCheckout::images/plus-solid.svg'),
                    'image_minus_qty' => $this->getViewFileUrl('Webbhuset_CollectorCheckout::images/minus-solid.svg'),
                    'newsletter_url' => $this->getNewsletterUrl(),
                    'reinit_url' => $this->getReinitUrl(),
                    'update_url' => $this->getUpdateUrl(),
                ],
            ],
        ];
    }

    /**
     * Get the endpoint url for subscribing to newsletter on place order
     *
     * @return string
     */
    public function getNewsletterUrl()
    {
        return $this->urlBuilder->getUrl('collectorcheckout/newsletter');
    }

    public function getReinitUrl()
    {
        return $this->urlBuilder->getUrl('collectorcheckout/reinit');
    }

    public function getUpdateUrl()
    {
        return $this->urlBuilder->getUrl('collectorcheckout/update');
    }

    /**
     * Get view file url
     *
     * @param       $fileId
     * @param array $params
     * @return string
     */
    public function getViewFileUrl($fileId, array $params = [])
    {
        try {
            $params = array_merge(['_secure' => $this->request->isSecure()], $params);

            return $this->assetRepo->getUrlWithParams($fileId, $params);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);

            return $this->urlBuilder->getUrl('', ['_direct' => 'core/index/notFound']);
        }
    }
}
