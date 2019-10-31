<?php
use Justuno_Jumagext_OI as OIH;
use Justuno_Jumagext_Response as R;
use Mage_Customer_Model_Customer as C;
use Mage_Sales_Model_Order as O;
use Mage_Sales_Model_Order_Item as OI;
use Mage_Sales_Model_Resource_Order_Collection as OC;
use Mage_Sales_Model_Resource_Order_Item_Collection as OIC;
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
		$dateFormat = 'Y-m-d H:i:s'; /** @var string $dateFormat */
		if ($updatedSince = $req->getParam('updatedSince')) {
		  $fromDate = date($dateFormat, strtotime($updatedSince));
		  $toDate = date($dateFormat, strtotime('2035-01-01 23:59:59'));
		  $timezone = $timezone =  Mage::getStoreConfig('general/locale/timezone');
		  $fromDate = new DateTime($fromDate, new DateTimeZone($timezone));
		  $fromDate = $fromDate->format('U');
		  $fromDate = date($dateFormat,$fromDate);
		  $toDate = new DateTime($toDate, new DateTimeZone($timezone));
		  $toDate = $toDate->format('U');
		  $toDate = date($dateFormat,$toDate);
		  $oc->addFieldToFilter('updated_at', array('from' => $fromDate, 'to' => $toDate));
		}
		if ($sortOrders = $req->getParam('sortOrders')) {
			$oc->getSelect()->order("$sortOrders ASC");
		}
		$oc->getSelect()->limit($req->getParam('pageSize', 10), $req->getParam('currentPage', 1) - 1);
		$ordersArray = [];
		foreach ($oc as $o) { /** @var O $o */
			if(!empty($o['customer_id'])) {
				$customerData = self::getCustomerData($o['customer_id']);
			}
			$oic = new OIC; /** @var OIC $oic */
			$oic->addAttributeToFilter('order_id', $o['entity_id']);
			$ordersArray[] = [
				'CountryCode' => ''
				,'CreatedAt' => $o['created_at']
				,'Currency' => $o['order_currency_code']
				,'Customer' => $customerData
				,'CustomerId' => $o['customer_id']
				,'Email' => $o['customer_email']
				,'ID' => $o['increment_id']
				,'IP' => ''
				,'LineItems' => array_values(array_map(function(OI $i) {return [
					'OrderId' => $i->getOrderId()
					// 2019-10-31
					// "Orders: «lineItem prices currently being returned in the orders feed are 0 always»":
					// https://github.com/justuno-com/m1/issues/31
					,'Price' => OIH::price($i)
					,'ProductId' => OIH::top($i)->getProductId()
					,'TotalDiscount' => $i->getDiscountAmount()
					// 2019-10-31
					// "Orders: «VariantID for lineItems is currently hardcoded as ''»":
					// https://github.com/justuno-com/m1/issues/29
					,'VariantId' => $i->getProductId()
				];}, array_filter($oic->getItems(), function(OI $i) {return !$i->getChildrenItems();})))
				,'OrderNumber' => $o['entity_id']
				,'ShippingPrice' => $o['shipping_amount']
				,'Status' => $o['status']
				,'SubtotalPrice' => $o['subtotal']
				,'TotalDiscounts' => $o['base_discount_amount']
				,'TotalItems' => $o['total_item_count']
				,'TotalPrice' => $o['grand_total']
				,'TotalTax' => $o['tax_amount']
				,'UpdatedAt' => $o['updated_at']
			];
		}
		R::res($ordersArray);
	}

	/**
	 * 2019-10-27
	 * @used-by p()
	 * @param $id
	 * @return array
	 */
	private static function getCustomerData($id) {
		$c = new C; /** @var C $c */
		$c->load($id);
		$ba = $c->getDefaultBillingAddress()->getData();
		$oc = new OC; /** @var OC $oc */
		$oc->addFieldToFilter('customer_id', $id);
		return [
			'address1' => $ba['street']
			,'address2' => ''
			,'City' => $ba['city']
			,'CountryCode' => $ba['country_id']
			,'CreatedAt' => $c['created_at']
			,'email' => $c['email']
			,'FirstName' => $c['firstname']
			,'id' => $c['entity_id']
			,'LastName' => $c['lastname']
			,'OrdersCount' => $oc->count()
			,'ProvinceCode' => ''
			,'Tags' => ''
			,'TotalSpend' => array_sum(array_map(function(O $o) {return $o->getGrandTotal();}, $oc->getItems()))
			,'UpdatedAt' => $c['updated_at']
			,'Zip' => $ba['postcode']
		];
	}
}