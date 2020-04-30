<?php

namespace Webbhuset\CollectorCheckout\Block;

/**
 * Class Success
 *
 * @package Webbhuset\CollectorCheckout\Block
 */
class Success extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string html block with the iframe
     */
    protected $iframe;
    /**
     * @var \Magento\Framework\Serialize\Serializer\JsonHexTag|mixed
     */
    protected $serializer;
    /**
     * @var \Magento\Checkout\Model\CompositeConfigProvider
     */
    protected $configProvider;
    /**
     * @var array with analytics javascript data layer
     */
    protected $analytics;
    /**
     * @var array with enhanved ecommerce javascript data layer
     */
    protected $enhancedEcommerce;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Success constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context      $context
     * @param \Magento\Checkout\Model\CompositeConfigProvider       $configProvider
     * @param \Magento\Store\Model\StoreManagerInterface            $storeManager
     * @param array                                                 $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null     $serializer
     * @param \Magento\Framework\Serialize\SerializerInterface|null $serializerInterface
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Magento\Framework\Serialize\SerializerInterface $serializerInterface = null
    ) {
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;

        if (class_exists('Magento\Framework\Serialize\Serializer\JsonHexTag')) {
            $this->serializer = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Serialize\Serializer\JsonHexTag::class);
        } else {
            $this->serializer = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        }
    }

    /**
     * Returns the html block with the iframe
     *
     * @return mixed
     */
    public function getIframe()
    {
        return $this->iframe;
    }

    /**
     * Sets iframe class variable
     *
     * @param $iframe
     * @return $this
     */
    public function setIframe($iframe)
    {
        $this->iframe = $iframe;

        return $this;
    }

    /**
     * Returns the url used for updating quote data while interacting with the iframe
     *
     * @return string
     */
    public function getUpdateUrl()
    {
        return $this->getUrl('collectorcheckout/update');
    }

    /**
     * Returns the javascript config
     *
     * @return array
     */
    public function getCheckoutConfig()
    {
        return $this->configProvider->getConfig();
    }

    /**
     * Returns the javascript config serialized
     *
     * @return string
     */
    public function getSerializedCheckoutConfig()
    {
        return  $this->serializer->serialize($this->getCheckoutConfig());
    }

    /**
     * Returns a json_encoded string of analytics datalayer variables
     *
     * @return false|string
     */
    public function getAnalyticsDatalayer()
    {
        return json_encode($this->analytics);
    }

    /**
     * Returns a json_encoded string of ecommerce datalayer variables
     *
     * @return false|string
     */
    public function getEnhancedEcommerceDatalayer()
    {
        return json_encode($this->enhancedEcommerce);
    }

    /**
     * Sets the GTM data layer arrays based on order data
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function setSuccessOrder(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $this->setAnalyticsDatalayer($order);
        $this->setEnhancedEcommerceDatalayer($order);
    }

    /**
     * Sets the analytics data layer array based on order data
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function setAnalyticsDatalayer(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $products = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $products[] = [
                'sku'      => $item->getSku(),
                'name'     => $item->getName(),
                'price'    => $item->getPrice(),
                'quantity' => round($item->getQtyOrdered())
            ];
        }

        $this->analytics = [
            'transactionId'       => $order->getIncrementId(),
            'transactionAffiliation' => $this->storeManager->getStore()->getName(),
            'transactionTotal'    => $order->getGrandTotal(),
            'transactionTax'      => $order->getTaxAmount(),
            'transactionShipping' => $order->getShippingAmount(),
            'transactionProducts' => $products
        ];
    }

    /**
     * Sets the enhanced ecommerce data layer array based on order data
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function setEnhancedEcommerceDatalayer(\Magento\Sales\Api\Data\OrderInterface $order)
    {
        $products = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $products[] = [
                'id'      => $item->getSku(),
                'name'     => $item->getName(),
                'price'    => $item->getPrice(),
                'quantity' => round($item->getQtyOrdered())
            ];
        }

        $this->enhancedEcommerce = [
            'ecommerce' => [
                'purchase' => [
                    'actionField' => [
                        'id'       => $order->getIncrementId(),
                        'affiliation' => $this->storeManager->getStore()->getName(),
                        'revenue'    => $order->getGrandTotal(),
                        'tax'      => $order->getTaxAmount(),
                        'shipping' => $order->getShippingAmount(),
                    ],
                    'products' => $products
                ]
            ]
        ];
    }
}
