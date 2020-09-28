<?php
use Mage_Catalog_Model_Resource_Eav_Attribute as A;
# 2019-11-26
final class Justuno_M1_Config_Brand {
	/**
	 * 2019-11-26
	 * @return array(array(string => string))
	 */
    function toOptionArray() {
        $r = [['value' => '', 'label' => __('Please Select')]]; /** @var array(array(string => string)) $a */
		foreach (Mage::getResourceModel('catalog/product_attribute_collection') as $a) {/** @var A $a */
            if ('' !== ($l = $a->getFrontendLabel())) {
                $r[] = ['label' => $l, 'value' => $a->getAttributeCode()];
            }
        }
        return $r;
    }
}