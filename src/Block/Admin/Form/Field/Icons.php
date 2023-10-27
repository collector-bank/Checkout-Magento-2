<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Block\Admin\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Webbhuset\CollectorCheckout\Config\Source\IconsSource;

class Icons extends Select
{
    /** @var IconsSource $iconsSource */
    private $iconsSource;

    public function __construct(
        Context $context,
        IconsSource $iconsSource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->iconsSource = $iconsSource;
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function setInputId($value)
    {
        return $this->setId($value);
    }

    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        return $this->iconsSource->toOptionArray();
    }
}
