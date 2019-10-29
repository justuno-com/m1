<?php
use Mage_Catalog_Model_Product as P;
use Mage_Catalog_Model_Product_Visibility as V;
use Mage_Review_Model_Review_Summary as RS;
use Mage_Tag_Model_Resource_Tag_Collection as TC;
use Mage_Tag_Model_Tag as T;
final class Justuno_Jumagext_ResponseController extends Mage_Core_Controller_Front_Action {
	/**
	 * 2019-10-27
	 * /jumagext/response/catalog?pageSize=2&currentPage=1&sortProducts=entity_id&updatedSince=2014-01-01
	 * https://www.upwork.com/messages/rooms/room_e6b2d182b68bdb5e9bf343521534b1b6/story_2e22707221dd053eab677398848c8ea3
	 */
	function catalogAction() {
		$this->authorizeUser();
		$query_params = Mage::app()->getRequest()->getParams();
		/** @var Mage_Catalog_Model_Resource_Product_Collection $products */
		$products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');
		$products->setVisibility([V::VISIBILITY_BOTH, V::VISIBILITY_IN_CATALOG, V::VISIBILITY_IN_SEARCH]);
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
		$page = !empty($query_params['currentPage']) ? $query_params['currentPage'] : 0;
		$limit = !empty($query_params['pageSize']) ? $query_params['pageSize'] : 10;
		$products->getSelect()->limit($limit, $page);
		$productsArray = array();
		$brand_attr = Mage::getStoreConfig('justuno/justuno_settings/brand_attributure', $this->storeId);
		foreach ($products as $p) { /** @var P $p */
			$cats = $p->getCategoryIds();
			$categoryData = array();
			foreach ($cats as $category_id) {
				$_cat = Mage::getModel('catalog/category')->load($category_id);
				$cat_tmp['Description'] = $_cat->getDescription();
				$cat_tmp['ID'] = $_cat->getId();
				// 2019-10-30
				// «In Categories imageURL is being sent back as a boolean in some cases,
				// it should always be sent back as a string,
				// if there is not url just don't send the property back»:
				// https://github.com/justuno-com/m1/issues/12
				$cat_tmp['ImageURL'] = $_cat->getImageUrl() ?: null;
				$cat_tmp['Keywords'] = $_cat->getMetaKeywords();
				$cat_tmp['Name'] = $_cat->getName();
				$cat_tmp['URL'] = $_cat->getUrl();
				$categoryData[] = $cat_tmp;
			}
			$cat_img_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';
			// 2019-10-30
			// "Add the `ReviewsCount` and `ReviewsRatingSum` values to the `catalog` response":
			// https://github.com/justuno-com/m1/issues/15
			$p->getRatingSummary();
			$rs = new RS; /** @var RS $rs */
			$rs->load($p->getId());
			$prod_temp = [
				'Categories' => $categoryData
				,'CreatedAt' => $p['created_at']
				,'ID' => $p['sku']
				,'ImageURL' => $cat_img_url.$p->getImage()
				,'MSRP' => $p['msrp']
				,'Price' => $p['price']
				// 2019-10-30
				// «ReviewsCount and ReviewSums need to be Ints»: https://github.com/justuno-com/m1/issues/11
				,'ReviewsCount' => (int)$rs->getReviewsCount()
				// 2019-10-30
				// «ReviewsCount and ReviewSums need to be Ints»: https://github.com/justuno-com/m1/issues/11
				,'ReviewsRatingSum' => (int)$rs->getRatingSummary()
				,'SalePrice' => $p['price']
				,'Tags' => $this->tags($p)
				,'Title' => $p['name']
				,'UpdatedAt' => $p['updated_at']
				,'URL' => $this->siteBaseURL.$p['url_path']
				/**
				 * 2019-10-30
				 * «if a product doesn't have parent/child like structure,
				 * I still need at least one variant in the Variants array»:
				 * https://github.com/justuno-com/m1/issues/5 
				 */
				,'Variants' => Justuno_Jumagext_Catalog_Variants::p($p)
			];
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
			$productsArray[] = Justuno_Jumagext_Response::filter($prod_temp);
		}
		$this->getResponse()->clearHeaders()->setHeader('Content-type','application/json', true);
		$this->getResponse()->setBody(json_encode($productsArray, JSON_PRETTY_PRINT));
	}

	/** 2019-10-27 */
	function catalogFirstAction() {
		$this->authorizeUser();
		$params = array(
			'siteUrl' => $this->siteBaseURL.'oauth',
			'requestTokenUrl' => $this->siteBaseURL.'oauth/initiate',
			'accessTokenUrl' => $this->siteBaseURL.'oauth/token',
			'authorizeUrl' => $this->adminAuthorizationUrl,
			'callbackUrl' => $this->siteBaseURL.$this->routerName."/".$this->controllerName.'/callback',//Url of callback action below
			'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER
		);
		// Initiate oAuth consumer with above parameters
		$consumer = new Zend_Oauth_Consumer($params);
		$query_params = Mage::app()->getRequest()->getParams();
		$parameters     = array(
			'sortOrders'  => $query_params['sortOrders'],
			'pageSize'    => $query_params['pageSize'],
			'currentPage' => $query_params['currentPage'],
		);
		$queryUrl = $this->build_http_query( $parameters );
		$restClient = $consumer->getHttpClient($params);
		$restClient->setUri($this->siteBaseURL.'api/rest/products?'.$queryUrl);
		$restClient->setHeaders('Accept', 'application/json');
		$restClient->setMethod(Zend_Http_Client::GET);
		$response = $restClient->request();
		// Here we can see that response body contains json list of products
		$resp = $response->getBody();
		print_r($resp);
	}

	/** 2019-10-27 */
	function ordersAction() {
		$this->authorizeUser();
		$query_params = Mage::app()->getRequest()->getParams();
		$ordersCollection = Mage::getModel('sales/order')->getCollection();
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
		  $ordersCollection->addFieldToFilter('updated_at', array('from' => $fromDate, 'to' => $toDate));
		}
		if(!empty($query_params['sortOrders'])) {
			$ordersCollection->getSelect()->order($query_params['sortOrders'].' ASC');
		}
		$page = !empty($query_params['currentPage']) ? $query_params['currentPage'] : 1;
		$limit = !empty($query_params['pageSize']) ? $query_params['pageSize'] : 10;
		$ordersCollection->getSelect()->limit($limit, $page);
		$ordersArray = array();
		foreach($ordersCollection->getData() as $order) {
			if(!empty($order["customer_id"])) {
				$customerData = $this->getCustomerData($order["customer_id"]);
			}
			$orderItemsCollection = Mage::getModel('sales/order_item')->getCollection()->addAttributeToFilter(
				'order_id', $order["entity_id"]
			);
			foreach($orderItemsCollection->getData() as $item) {
				$lineItems = array(
					'ProductId'   => $item["product_id"],
					'OrderId'     => $item["order_id"],
					'VariantId'   => '',
					'Price'       => $item["price"],
					'TotalDiscount'=> $item["discount_amount"]
				);
			}
			$cntry = $ip = $TotalRecords = "";
			$order_temp = array(
				'ID'            => $order["increment_id"],
				'OrderNumber'   => $order["entity_id"],
				'CustomerId'    => $order["customer_id"],
				'Email'         => $order["customer_email"],
				'CreatedAt'     => $order["created_at"],
				'UpdatedAt'     => $order["updated_at"],
				'TotalPrice'    => $order["grand_total"],
				'SubtotalPrice' => $order["subtotal"],
				'ShippingPrice' => $order["shipping_amount"],
				'TotalTax'      => $order["tax_amount"],
				'TotalDiscounts'=> $order["base_discount_amount"],
				'TotalItems'    => $order["total_item_count"],
				'Currency'      => $order["order_currency_code"],
				'Status'        => $order["status"],
				'IP'            => $ip,
				'CountryCode'   => $cntry,
				'LineItems'     => $lineItems,
				'Customer'      => $customerData
			);
			$ordersArray[] = $order_temp;
		}
		print_r(json_encode($ordersArray));
	}

	/**
	 * 2019-10-27
	 * @override
	 * @see Mage_Core_Controller_Varien_Action::_construct()
	 * @used-by Mage_Core_Controller_Varien_Action::__construct()
	 */
	protected function _construct() {
		$this->storeId = Mage::app()->getStore()->getStoreId();
		$this->siteBaseURL = Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_WEB, true );
		$this->moduleName = Mage::app()->getRequest()->getModuleName();
		$this->routerName = Mage::app()->getRequest()->getRouteName();
		$this->controllerName = Mage::app()->getRequest()->getControllerName();
		$this->isAdminUser = false;
		$this->adminAuthorizationUrl = ($this->isAdminUser) ? $this->siteBaseURL."admin/oauth_authorize" : $this->siteBaseURL."oauth/authorize";
	}

	/**
	 * 2019-10-27
	 * @used-by catalogAction()
	 * @used-by catalogFirstAction()
	 * @used-by ordersAction()
	 */
	private function authorizeUser() {
		if (!isset($_SERVER['DF_DEVELOPER'])) {
			$apitoken = Mage::getStoreConfig('justuno/justuno_settings/jutoken', $this->storeId);
			$req_token = Mage::app()->getRequest()->getHeader('Authorization');
			if (empty($req_token)) {
				die('Token missing!');
			}
			if ($req_token !== $apitoken) {
				die('Token mismatched!');
			}
		}
	}

	/**
	 * 2019-10-27
	 * @used-by catalogFirstAction()
	 * @param $query
	 * @return string
	 */
	private function build_http_query( $query ){
		$query_array = array();
		foreach ($query as $key => $key_value) {
			if ($key_value == ''){continue;}
			if( $key == 'sortOrders' ) {
				$query_array[]  = "order=".urlencode( $key_value )."&dir=asc";
			}
			else if($key == "currentPage"){
				$page =  urlencode( $key_value );
				$query_array[] = "page=".$page;
			} else if($key == "pageSize"){
				$limit =  urlencode( $key_value );
				$query_array[] = "limit=".$limit;
			}
		}
		return implode( '&', $query_array );
	}

	/**
	 * 2019-10-27
	 * @used-by ordersAction()
	 * @param $customerId
	 * @return array
	 */
	private function getCustomerData($customerId) {
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

	/**
	 * 2019-10-27
	 * @used-by catalogAction()
	 * @param P $p
	 * @return array(array(string => int|string))
	 */
	private function tags(P $p) {
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

	private $storeId;
	private $siteBaseURL;
	private $moduleName;
	private $routerName;
	private $controllerName;
	private $isAdminUser;
	private $adminAuthorizationUrl;
}