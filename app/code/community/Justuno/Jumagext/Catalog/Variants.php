<?php
use Mage_Catalog_Model_Product as P;
use Mage_CatalogInventory_Model_Stock_Item as SI;
// 2019-10-30
final class Justuno_Jumagext_Catalog_Variants {
	/**
	 * 2019-10-30
	 * @used-by \Justuno_Jumagext_ResponseController::catalogAction
	 * @param P $p
	 * @return array(array(string => mixed))
	 */
	static function p(P $p) { /** @var array(array(string => mixed)) $r */
		if ('configurable' !== $p->getTypeId()) {
			$r = self::variant($p);
		}
		else {
			$ct = $p->getTypeInstance(); /** @var Mage_Catalog_Model_Product_Type_Configurable $ct */
			$opts = array_column($ct->getConfigurableAttributesAsArray($p), 'attribute_code', 'id');
			$r = array_values(array_map(function(P $p) use($opts) {return
				self::variant($p, $opts)
			;}, $ct->getUsedProducts(null, $p)));
		}
		return $r;
	}

	/**
	 * 2019-10-30
	 * @param P $p
	 * @param array(int => string) $opts [optional]
	 * @return array(string => mixed)
	 */
	private static function variant(P $p, $opts = []) {
		// 2019-08-28 Otherwise $p does not contain the product's price
		$p = $p->load($p->getId()); /** @var P $p */
		$si = new SI; /** @var SI $si */
		$si->loadByProduct($p);
		$r = [
			'ID' => $p->getId()
			,'InventoryQuantity' => (int)$si->getQty()
			,'MSRP' => $p['msrp']
			,'SalePrice' => $p->getPrice()
			,'SKU' => $p->getSku()
			,'Title' => $p->getName()
		];
		foreach ($opts as $id => $code) {
			$r["Option$id"] = $p->getAttributeText($code);
		}
		return $r;
	}
}