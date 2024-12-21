<?php
declare(strict_types=1);

namespace Webbhuset\CollectorCheckout\Block\Admin\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Webbhuset\CollectorCheckout\Block\Admin\Form\Field\Badges as BadgesField;

class BadgesMapper extends AbstractFieldArray
{
    /**
     * @var BadgesField
     */
    private $badgesRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('method', [
            'label' => __('Shipping methods'),
            'renderer' => $this->getBadgesRenderer()
        ]);
        $this->addColumn('color', ['label' => __('Color'), 'class' => 'required-entry']);
        $this->addColumn('text', ['label' => __('Text'), 'class' => 'required-entry']);

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
     * @return BadgesField
     * @throws LocalizedException
     */
    private function getBadgesRenderer()
    {
        if (!$this->badgesRenderer) {
            $this->badgesRenderer = $this->getLayout()->createBlock(
                BadgesField::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->badgesRenderer;
    }
}
