<?php
use Justuno_Jumagext_Response as R;
// 2019-10-31
final class Justuno_Jumagext_Orders {
	/**
	 * 2019-10-31
	 * @used-by Justuno_Jumagext_ResponseController::ordersAction()
	 */
	static function p() {
		R::authorize();
		$req = Mage::app()->getRequest(); /** @var Mage_Core_Controller_Request_Http $req */
		$query_params = $req->getParams();
		$ordersCollection = Mage::getModel('sales/order')->getCollection();
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
		  $ordersCollection->addFieldToFilter('updated_at', array('from' => $fromDate, 'to' => $toDate));
		}
		if(!empty($query_params['sortOrders'])) {
			$ordersCollection->getSelect()->order($query_params['sortOrders'].' ASC');
		}
		$page = !empty($query_params['currentPage']) ? $query_params['currentPage'] : 1;
		$limit = !empty($query_params['pageSize']) ? $query_params['pageSize'] : 10;
		$ordersCollection->getSelect()->limit($limit, $page-1);
		$ordersArray = array();
		foreach($ordersCollection->getData() as $order) {
			if(!empty($order['customer_id'])) {
				$customerData = self::getCustomerData($order['customer_id']);
			}
			$orderItemsCollection = Mage::getModel('sales/order_item')->getCollection()->addAttributeToFilter(
				'order_id', $order['entity_id']
			);
			foreach($orderItemsCollection->getData() as $item) {
				$lineItems = array(
					'ProductId'   => $item['product_id'],
					'OrderId'     => $item['order_id'],
					'VariantId'   => '',
					'Price'       => $item['price'],
					'TotalDiscount'=> $item['discount_amount']
				);
			}
			$cntry = $ip = $TotalRecords = '';
			$order_temp = [
				'CountryCode' => $cntry
				,'CreatedAt' => $order['created_at']
				,'Currency' => $order['order_currency_code']
				,'Customer' => $customerData
				,'CustomerId' => $order['customer_id']
				,'Email' => $order['customer_email']
				,'ID' => $order['increment_id']
				,'IP' => $ip
				,'LineItems' => $lineItems
				,'OrderNumber' => $order['entity_id']
				,'ShippingPrice' => $order['shipping_amount']
				,'Status' => $order['status']
				,'SubtotalPrice' => $order['subtotal']
				,'TotalDiscounts' => $order['base_discount_amount']
				,'TotalItems' => $order['total_item_count']
				,'TotalPrice' => $order['grand_total']
				,'TotalTax' => $order['tax_amount']
				,'UpdatedAt' => $order['updated_at']
			];
			$ordersArray[] = $order_temp;
		}
		print_r(json_encode($ordersArray));
	}

	/**
	 * 2019-10-27
	 * @used-by ordersAction()
	 * @param $customerId
	 * @return array
	 */
	private static function getCustomerData($customerId) {
		$customerObj = Mage::getModel('customer/customer')->load($customerId);
		$customer = $customerObj->getData();
		$def_bill_address = $customerObj->getDefaultBillingAddress()->getData();

		$orders = Mage::getModel('sales/order')->getCollection()->addFieldToFilter('customer_id',$customerId);
		$OrdersCount = $orders->count();
		$totalSpent = 0;
		foreach ($orders as $order) {
			$total = $order->getGrandTotal();
			$totalSpent+= $total;
		}
		$customerArray = array(
			'id'        => $customer["entity_id"],
			'email'     => $customer["email"],
			'CreatedAt' => $customer["created_at"],
			'UpdatedAt' => $customer["updated_at"],
			'FirstName' => $customer["firstname"],
			'LastName'  => $customer["lastname"],
			'OrdersCount' => $OrdersCount,
			'TotalSpend'  => $totalSpent,
			'Tags'        => '',
			'address1'    => $def_bill_address["street"],
			'address2'    => '',
			'City'        => $def_bill_address["city"],
			'Zip'         => $def_bill_address["postcode"],
			'ProvinceCode'=> '',
			'CountryCode' => $def_bill_address["country_id"]
		);
		return $customerArray;
	}
}