<?php
use Justuno_Jumagext_OI as OIH;
use Justuno_Jumagext_Response as R;
use Mage_Customer_Model_Customer as C;
use Mage_Sales_Model_Order as O;
use Mage_Sales_Model_Order_Address as A;
use Mage_Sales_Model_Order_Item as OI;
use Mage_Sales_Model_Resource_Order_Collection as OC;
// 2019-10-31
final class Justuno_Jumagext_Orders {
	/**
	 * 2019-10-31
	 * @used-by Justuno_Jumagext_ResponseController::ordersAction()
	 */
	static function p() {
		R::authorize();
		$req = Mage::app()->getRequest(); /** @var Mage_Core_Controller_Request_Http $req */
		$oc = new OC; /** @var OC $oc */
		self::filterByDate($oc);
		if ($sortOrders = $req->getParam('sortOrders')) {
			$oc->getSelect()->order("$sortOrders ASC");
		}
		$oc->getSelect()->limit($req->getParam('pageSize', 10), $req->getParam('currentPage', 1) - 1);
		R::res(array_values(array_map(function(O $o) {return [
			'CountryCode' => $o->getBillingAddress()->getCountryId()
			,'CreatedAt' => $o->getCreatedAt()
			,'Currency' => $o->getOrderCurrencyCode()
			/**
			 * 2019-10-31
			 * Orders: «if the customer checked out as a guest
			 * we need still need a Customer object and it needs the ID to be a randomly generated UUID
			 * or other random string»: https://github.com/justuno-com/m1/issues/30
			 */
			,'Customer' => self::customer($o)
			/**
			 * 2019-10-31
			 * Orders: «if the customer checked out as a guest
			 * we need still need a Customer object and it needs the ID to be a randomly generated UUID
			 * or other random string»: https://github.com/justuno-com/m1/issues/30
			 */
			,'CustomerId' => $o->getCustomerId() ?: $o->getCustomerEmail()
			,'Email' => $o->getCustomerEmail()
			,'ID' => $o->getIncrementId()
			,'IP' => $o->getRemoteIp()
			,'LineItems' => array_values(array_map(function(OI $i) {return [
				'OrderId' => $i->getOrderId()
				// 2019-10-31
				// Orders: «lineItem prices currently being returned in the orders feed are 0 always»:
				// https://github.com/justuno-com/m1/issues/31
				,'Price' => OIH::price($i)
				,'ProductId' => OIH::top($i)->getProductId()
				,'TotalDiscount' => (float)$i->getDiscountAmount()
				// 2019-10-31
				// Orders: «VariantID for lineItems is currently hardcoded as ''»:
				// https://github.com/justuno-com/m1/issues/29
				,'VariantId' => $i->getProductId()
			];}, array_filter($o->getAllItems(), function(OI $i) {return !$i->getChildrenItems();})))
			,'OrderNumber' => $o->getId()
			,'ShippingPrice' => (float)$o->getShippingAmount()
			,'Status' => $o->getStatus()
			,'SubtotalPrice' => (float)$o->getSubtotal()
			,'TotalDiscounts' =>(float) $o->getDiscountAmount()
			,'TotalItems' => (int)$o->getTotalItemCount()
			,'TotalPrice' => (float)$o->getGrandTotal()
			,'TotalTax' => (float)$o->getTaxAmount()
			,'UpdatedAt' => $o->getUpdatedAt()
		];}, $oc->getItems())));
	}

	/**
	 * 2019-10-27
	 * 2019-10-31
	 * Orders: «if the customer checked out as a guest
	 * we need still need a Customer object and it needs the ID to be a randomly generated UUID
	 * or other random string»: https://github.com/justuno-com/m1/issues/30
	 * @used-by p()
	 * @param O $o
	 * @return array(string => mixed)
	 */
	private static function customer(O $o) {
		$c = new C; /** @var C $c */
		$oc = new OC; /** @var OC $oc */
		if (!$o->getCustomerId()) {
			$oc->addFieldToFilter('customer_email', $o->getCustomerEmail());
		}
		else {
			$c->load($o->getCustomerId());
			$oc->addFieldToFilter('customer_id', $o->getCustomerId());
		}
		$ba = $o->getBillingAddress(); /** @var A $ba */
		return [
			'Address1' => $ba->getStreet(1)
			,'Address2' => $ba->getStreet(2)
			,'City' => $ba->getCity()
			,'CountryCode' => $ba->getCountryId()
			,'CreatedAt' => $c['created_at']
			,'Email' => $o->getCustomerEmail()
			,'FirstName' => $o->getCustomerFirstname()
			/**
			 * 2019-10-31
			 * Orders: «if the customer checked out as a guest
			 * we need still need a Customer object and it needs the ID to be a randomly generated UUID
			 * or other random string»: https://github.com/justuno-com/m1/issues/30
			 */
			,'ID' => $o->getCustomerId() ?: $o->getCustomerEmail()
			,'LastName' => $o->getCustomerLastname()
			,'OrdersCount' => $oc->count()
			,'ProvinceCode' => $ba->getRegionCode()
			,'Tags' => ''
			,'TotalSpend' => array_sum(array_map(function(O $o) {return $o->getGrandTotal();}, $oc->getItems()))
			,'UpdatedAt' => $c['updated_at']
			,'Zip' => $ba->getPostcode()
		];
	}

	/**
	 * 2019-10-31
	 * @used-by p()
	 * @param OC $oc
	 */
	private static function filterByDate(OC $oc) {
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
			$oc->addFieldToFilter('updated_at', ['from' => $d($since), 'to' => $d('2035-01-01 23:59:59')]);
		}
	}
}