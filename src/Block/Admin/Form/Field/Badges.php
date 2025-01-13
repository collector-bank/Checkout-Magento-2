<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Block\Admin\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Webbhuset\CollectorCheckout\Config\Source\BadgesSource;

class Badges extends Select
{
    /** @var BadgesSource $badgesSource */
    private $badgesSource;

    public function __construct(
        Context $context,
        BadgesSource $badgesSource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->badgesSource = $badgesSource;
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
        return $this->badgesSource->toOptionArray();
    }
}
