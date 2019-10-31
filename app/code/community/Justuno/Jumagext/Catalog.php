<?php
use Justuno_Jumagext_Response as R;
use Mage_Catalog_Model_Product as P;
use Mage_Catalog_Model_Product_Visibility as V;
use Mage_Review_Model_Review_Summary as RS;
use Mage_Tag_Model_Resource_Tag_Collection as TC;
use Mage_Tag_Model_Tag as T;
// 2019-10-31
final class Justuno_Jumagext_Catalog {
	/**
	 * 2019-10-31
	 * @used-by Justuno_Jumagext_ResponseController::catalogAction()
	 */
	static function p() {
		R::authorize();
		$query_params = Mage::app()->getRequest()->getParams();
		/** @var Mage_Catalog_Model_Resource_Product_Collection $products */
		$products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
		/**
		 * 2019-10-30
		 * 1) «if a product has a Status of "Disabled" we'd still want it in the feed,
		 * but we'd want to set the inventoryquantity to -9999»:
		 * https://github.com/justuno-com/m1/issues/4
		 * 2) I do not use
		 * 		$products->setVisibility([V::VISIBILITY_BOTH, V::VISIBILITY_IN_CATALOG, V::VISIBILITY_IN_SEARCH]);
		 * because it filters out disabled products.
		 */
		$products->addAttributeToFilter('visibility', ['in' => [
			V::VISIBILITY_BOTH, V::VISIBILITY_IN_CATALOG, V::VISIBILITY_IN_SEARCH
		]]);
		if (!empty($query_params['updatedSince'])) {
			$fromDate = date('Y-m-d H:i:s', strtotime($query_params['updatedSince']));
			$toDate = date('Y-m-d H:i:s', strtotime('2035-01-01 23:59:59'));
			$timezone = $timezone =  Mage::getStoreConfig('general/locale/timezone');
			$fromDate = new DateTime($fromDate, new DateTimeZone($timezone));
			$fromDate = $fromDate->format('U');
			$fromDate = date("Y-m-d H:i:s",$fromDate);
			$toDate = new DateTime($toDate, new DateTimeZone($timezone));
			$toDate = $toDate->format('U');
			$toDate = date("Y-m-d H:i:s",$toDate);
			$products->addFieldToFilter('updated_at', array('from' => $fromDate, 'to' => $toDate));
		}
		if(!empty($query_params['sortProducts'])) {
			$products->getSelect()->order($query_params['sortProducts'].' DESC');
		}
		$page = !empty($query_params['currentPage']) ? $query_params['currentPage'] : 1;
		$limit = !empty($query_params['pageSize']) ? $query_params['pageSize'] : 10;
		$products->getSelect()->limit($limit, $page-1);
		$productsArray = array();
		$brand_attr = Mage::getStoreConfig('justuno/justuno_settings/brand_attributure');
		foreach ($products as $p) { /** @var P $p */
			// 2019-08-28 Otherwise $p does not contain the product's price
			// 2019-08-30 The collection does not load the media gallery.
			$p = $p->load($p->getId()); /** @var P $p */
			$cats = $p->getCategoryIds();
			$categoryData = array();
			foreach ($cats as $category_id) {
				/** @var Mage_Catalog_Model_Category $_cat */
				$_cat = Mage::getModel('catalog/category')->load($category_id);
				$cat_tmp['Description'] = $_cat['description'];
				// 2019-10-30
				// «json construct types are not correct for some values»:
				// https://github.com/justuno-com/m1/issues/8
				$cat_tmp['ID'] = $_cat->getId();
				// 2019-10-30
				// «In Categories imageURL is being sent back as a boolean in some cases,
				// it should always be sent back as a string,
				// if there is not url just don't send the property back»:
				// https://github.com/justuno-com/m1/issues/12
				$cat_tmp['ImageURL'] = $_cat->getImageUrl() ?: null;
				$cat_tmp['Keywords'] = $_cat['meta_keywords'];
				$cat_tmp['Name'] = $_cat->getName();
				$cat_tmp['URL'] = $_cat->getUrl();
				$categoryData[] = $cat_tmp;
			}
			// 2019-10-30
			// "Add the `ReviewsCount` and `ReviewsRatingSum` values to the `catalog` response":
			// https://github.com/justuno-com/m1/issues/15
			$p->getRatingSummary();
			$rs = new RS; /** @var RS $rs */
			$rs->load($p->getId());
			$prod_temp = [
				'Categories' => $categoryData
				,'CreatedAt' => $p['created_at']
				// 2019-10-30
				// «The parent ID is pulling the sku, it should be pulling the ID like the variant does»:
				// https://github.com/justuno-com/m1/issues/19
				,'ID' => $p->getId()
				/**
				 * 2019-10-30
				 * 1) «MSRP, Price, SalePrice, Variants.MSRP, and Variants.SalePrice all need to be Floats,
				 * or if that is not possible then Ints»: https://github.com/justuno-com/m1/issues/10
				 * 2) «If their isn't an MSRP for some reason just use the salesprice»:
				 * https://github.com/justuno-com/m1/issues/6
				 * 2019-10-31
				 * «The MSRP should pull in this order MSRP > Price > Dynamic Price»:
				 * https://github.com/justuno-com/m1/issues/20
				 */
				,'MSRP' => (float)($p['msrp'] ?: ($p['price'] ?: $p->getPrice()))
				 /**
				  * 2019-10-30
				  * «MSRP, Price, SalePrice, Variants.MSRP, and Variants.SalePrice all need to be Floats,
				  * or if that is not possible then Ints»: https://github.com/justuno-com/m1/issues/10
				  * 2019-10-31
				  * «Price should be Price > Dynamic Price»: https://github.com/justuno-com/m1/issues/21
				  */
				,'Price' => (float)($p['price'] ?: $p->getPrice())
				// 2019-10-30 «ReviewsCount and ReviewSums need to be Ints»: https://github.com/justuno-com/m1/issues/11
				,'ReviewsCount' => (int)$rs->getReviewsCount()
				// 2019-10-30 «ReviewsCount and ReviewSums need to be Ints»: https://github.com/justuno-com/m1/issues/11
				,'ReviewsRatingSum' => (int)$rs->getRatingSummary()
				// 2019-10-30
				// «MSRP, Price, SalePrice, Variants.MSRP, and Variants.SalePrice all need to be Floats,
				// or if that is not possible then Ints»: https://github.com/justuno-com/m1/issues/10
				,'SalePrice' => (float)$p->getPrice()
				,'Tags' => self::tags($p)
				,'Title' => $p['name']
				,'UpdatedAt' => $p['updated_at']
				// 2019-10-30 https://github.com/justuno-com/m1/issues/16
				,'URL' => R::url($p['url_path'] ?: $p['url_key'])
				/**
				 * 2019-10-30
				 * «if a product doesn't have parent/child like structure,
				 * I still need at least one variant in the Variants array»:
				 * https://github.com/justuno-com/m1/issues/5
				 */
				,'Variants' => Justuno_Jumagext_Catalog_Variants::p($p)
			] + Justuno_Jumagext_Catalog_Images::p($p);
			if ('configurable' === $p->getTypeId()) {
				$ct = $p->getTypeInstance(); /** @var Mage_Catalog_Model_Product_Type_Configurable $ct */
				$opts = array_column($ct->getConfigurableAttributesAsArray($p), 'attribute_code', 'id');
				/**
				 * 2019-10-30
				 * «within the ProductResponse and the Variants OptionType is being sent back as OptionType90, 91, etc...
				 * We need these sent back starting at OptionType1, OptionType2»:
				 * https://github.com/justuno-com/m1/issues/14
				 */
				foreach (array_values($opts) as $id => $code) {
					$id++;
					$prod_temp["OptionType$id"] = $code;
				}
			}
			if(!empty($brand_attr)) {
				$brand_attr_val = !empty($p[$brand_attr]) ? $p[$brand_attr] : "";
				$prod_temp["BrandId"] = $brand_attr;
				$prod_temp["BrandName"] = $brand_attr_val;
			}
			// 2019-10-30
			// «if a property is null or an empty string do not send it back»:
			// https://github.com/justuno-com/m1/issues/9
			$productsArray[] = $prod_temp;
		}
		R::res($productsArray);
	}

	/**
	 * 2019-10-27
	 * @used-by catalogAction()
	 * @param P $p
	 * @return array(array(string => int|string))
	 */
	private static function tags(P $p) {
		$tc = new TC; /** @var TC $tc */
		$tc->addPopularity();
		$tc->addStatusFilter(T::STATUS_APPROVED);
		$tc->addProductFilter($p->getId());
		$tc->setFlag('relation', true);
		$tc->setActiveFilter();
		return array_values(array_map(function(T $t) {return [
			'ID' => $t->getId(), 'Name' => $t->getName()
		];}, $tc->getItems()));
	}
}