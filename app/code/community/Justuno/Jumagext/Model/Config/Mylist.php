<?php
class Justuno_Jumagext_Model_Config_Mylist{

    public function toOptionArray()
    {   
       
        $attributes = array();
        $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');

        $attributes[] = ['value' => '', 'label' => __('Please Select')];
        foreach ($productAttrs as $productAttr) { /** @var Mage_Catalog_Model_Resource_Eav_Attribute $productAttr */
            if($productAttr->getFrontendLabel() != ''  ) {
                $attributes[] = ['value' => $productAttr->getAttributeCode(), 'label' => __($productAttr->getFrontendLabel())];
            }
        }
        return $attributes;
    }

}

?>