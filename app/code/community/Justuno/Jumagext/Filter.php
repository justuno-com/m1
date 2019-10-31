<?php
use Mage_Catalog_Model_Resource_Product_Collection as PC;
use Mage_Sales_Model_Resource_Order_Collection as OC;
use Varien_Data_Collection_Db as C;
// 2019-10-10
final class Justuno_Jumagext_Filter {
	/**
	 * 2019-10-31
	 * @used-by Justuno_Jumagext_Catalog::p()
	 * @used-by Justuno_Jumagext_Orders::p()
	 * @param $c $c
	 */
	static function p(C $c) {
		self::byDate($c);
		$req = Mage::app()->getRequest(); /** @var Mage_Core_Controller_Request_Http $req */
		/** @var string $dir */ /** @var string $suffix */
		list($dir, $suffix) = $c instanceof PC ? ['DESC', 'Products'] : ['ASC', 'Orders'];
		if ($field = $req->getParam("sort$suffix")) { /** @var string $field */
			$c->getSelect()->order("$field $dir");
		}
		$c->getSelect()->limit($req->getParam('pageSize', 10), $req->getParam('currentPage', 1) - 1);
	}

	/**
	 * 2019-10-31
	 * @used-by p()
	 * @param $c $c
	 */
	private static function byDate(C $c) {
		if ($since = Mage::app()->getRequest()->getParam('updatedSince')) { /** @var string $since */
			/**
			 * 2019-10-31
			 * @param string $s
			 * @return string
			 */
			$d = function($s) {
				$f = 'Y-m-d H:i:s'; /** @var string $f */
				$tz = Mage::getStoreConfig('general/locale/timezone'); /** @var string $tz */
				$dt = new DateTime(date($f, strtotime($s)), new DateTimeZone($tz));	/** @var DateTime $dt */
				return date($f, $dt->format('U'));
			};
			$c->addFieldToFilter('updated_at', ['from' => $d($since), 'to' => $d('2035-01-01 23:59:59')]);
		}
	}
}