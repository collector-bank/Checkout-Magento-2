<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Block\Admin\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Webbhuset\CollectorCheckout\Block\Admin\Form\Field\Icons as IconFields;

class IconMapper extends AbstractFieldArray
{
    /**
     * @var IconFields
     */
    private $iconRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('method', [
            'label' => __('Shipping methods'),
            'renderer' => $this->getIconRenderer()
        ]);
        $this->addColumn('icon', ['label' => __('Icon'), 'class' => 'required-entry']);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return IconFields
     * @throws LocalizedException
     */
    private function getIconRenderer()
    {
        if (!$this->iconRenderer) {
            $this->iconRenderer = $this->getLayout()->createBlock(
                IconFields::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->iconRenderer;
    }
}
