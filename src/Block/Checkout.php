<?php

namespace Webbhuset\CollectorCheckout\Block;

/**
 * Class Checkout
 *
 * @package Webbhuset\CollectorCheckout\Block
 */
class Checkout extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string $frame html block with iframe
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

    protected $dataToken;
    protected $dataVersion;
    protected $dataLang;
    protected $dataActionColor;
    protected $dataActionTextColor;
    protected $iframeSrc;

    /**
     * Checkout constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context      $context
     * @param \Magento\Checkout\Model\CompositeConfigProvider       $configProvider
     * @param array                                                 $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null     $serializer
     * @param \Magento\Framework\Serialize\SerializerInterface|null $serializerInterface
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        \Magento\Framework\Serialize\SerializerInterface $serializerInterface = null
    ) {
        parent::__construct($context, $data);
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->configProvider = $configProvider;

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

    public function setDataToken($dataToken)
    {
        $this->dataToken = $dataToken;

        return $this;
    }

    public function setDataVersion($dataVersion)
    {
        $this->dataVersion = $dataVersion;

        return $this;
    }

    public function setDataLang($dataLang)
    {
        $this->dataLang = $dataLang;

        return $this;
    }

    public function setDataActionColor($dataActionColor)
    {
        $this->dataActionColor = $dataActionColor;

        return $this;
    }

    public function setDataActionTextColor($dataActionTextColor)
    {
        $this->dataActionTextColor = $dataActionTextColor;

        return $this;
    }

    public function setIframeSrc($iframeSrc)
    {
        $this->iframeSrc = $iframeSrc;

        return $this;
    }

    public function getDataToken()
    {
        return $this->dataToken;
    }

    public function getDataVersion()
    {
        return $this->dataVersion;
    }

    public function getDataLang()
    {
        return $this->dataLang;
    }

    public function getDataActionColor()
    {
        return $this->dataActionColor;
    }

    public function getDataActionTextColor()
    {
        return $this->dataActionTextColor;
    }

    public function getIframeSrc()
    {
        return $this->iframeSrc;
    }

    /**
     * Returns the url used for updating quote data while interacting with the iframe
     *
     * @return string
     */
    public function getUpdateUrl()
    {
        return $this->getUrl('walleycheckout/update');
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
}
