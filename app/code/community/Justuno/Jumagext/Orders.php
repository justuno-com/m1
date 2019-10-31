<?php
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
		$oc->getSelect()->limit($req->getParams('pageSize', 10), $req->getParams('currentPage', 1) - 1);
		$ordersArray = [];
		foreach ($oc as $o) { /** @var O $o */
			if(!empty($o['customer_id'])) {
				$customerData = self::getCustomerData($o['customer_id']);
			}
			$oic = new OIC; /** @var OIC $oic */
			$oic->addAttributeToFilter('order_id', $o['entity_id']);
			foreach($oic as $oi) { /** @var OI $oi */
				$lineItems = [
					'OrderId' => $oi['order_id']
					,'Price' => $oi['price']
					,'ProductId' => $oi['product_id']
					,'TotalDiscount' => $oi['discount_amount']
					,'VariantId' => ''
				];
			}
			$cntry = $ip = $TotalRecords = '';
			$order_temp = [
				'CountryCode' => $cntry
				,'CreatedAt' => $o['created_at']
				,'Currency' => $o['order_currency_code']
				,'Customer' => $customerData
				,'CustomerId' => $o['customer_id']
				,'Email' => $o['customer_email']
				,'ID' => $o['increment_id']
				,'IP' => $ip
				,'LineItems' => $lineItems
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
			$ordersArray[] = $order_temp;
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
		$def_bill_address = $c->getDefaultBillingAddress()->getData();
		$orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id', $id);
		$OrdersCount = $orders->count();
		$totalSpent = 0;
		foreach ($orders as $order) {
			$total = $order->getGrandTotal();
			$totalSpent+= $total;
		}
		return [
			'address1' => $def_bill_address['street']
			,'address2' => ''
			,'City' => $def_bill_address['city']
			,'CountryCode' => $def_bill_address['country_id']
			,'CreatedAt' => $c['created_at']
			,'email' => $c['email']
			,'FirstName' => $c['firstname']
			,'id' => $c['entity_id']
			,'LastName' => $c['lastname']
			,'OrdersCount' => $OrdersCount
			,'ProvinceCode' => ''
			,'Tags' => ''
			,'TotalSpend'  => $totalSpent
			,'UpdatedAt' => $c['updated_at']
			,'Zip' => $def_bill_address['postcode']
		];
	}
}