<?php
class Justuno_Jumagext_ResponseController extends Mage_Core_Controller_Front_Action {
	protected $storeId;
	protected $siteBaseURL;

	protected $moduleName;
	protected $routerName;
	protected $controllerName;

	protected $isAdminUser;
	protected $adminAuthorizationUrl;

	//define(CONSUMER_KEY,"b33cd08e0f8a478e265dab30dde35d23");
	//define(CONSUMER_SECRET,"12b34813bea12dc64bc86da0907f5216");

	function _construct()
	{
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
	function authorizeUser() {
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

	function getBrandAttribute()
	{
		$attribute = Mage::getStoreConfig('justuno/justuno_settings/brand_attributure', $this->storeId);
		return $attribute;
	}

	function catalogAction() {
		$this->authorizeUser();
		$query_params = Mage::app()->getRequest()->getParams();
		$products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*');

		if(!empty($query_params['updatedSince'])) {
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
			//$products->getSelect()->addAttributeToSort($query_params['sortOrders'], 'ASC');
			//$products->getSelect()->addAttributeToSort('name', 'ASC');
		}

		$page = !empty($query_params['currentPage']) ? $query_params['currentPage'] : 1;
		$limit = !empty($query_params['pageSize']) ? $query_params['pageSize'] : 10;
		$products->getSelect()->limit($limit, $page);

		//echo $products->getSelect();//->__toString();
		//exit(0);
		$productsArray = array();
		$brand_attr = $this->getBrandAttribute();

		foreach($products as $product) {
			//print_r($product->getData());
			$PID = $product["entity_id"];

			/*      CATEGORIES     */
			$cats = $product->getCategoryIds();
			//print_r($cats);
			$categoryData = array();
			foreach ($cats as $category_id) {
				$_cat = Mage::getModel('catalog/category')->load($category_id) ;
				$cat_tmp["ID"] = $_cat->getId();
				$cat_tmp["Name"] = $_cat->getName();
				$cat_tmp["Description"] = $_cat->getDescription();
				$cat_tmp["URL"] = $_cat->getUrl();
				$cat_tmp["ImageURL"] = $_cat->getImageUrl();
				$cat_tmp["Keywords"] = $_cat->getMetaKeywords();
				$categoryData[] = $cat_tmp;
			}

			/*      REVIEWS     */
			$summaryData = Mage::getModel('review/review_summary')
						->setStoreId($this->storeId)
						->load($PID);
			//print_r($summaryData);

			$brandId = $brandName = $reviewCount = $totalProducts = "";
			$cat_img_url = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'catalog/product';
			$prod_temp = array(
				'ID'        => $product["sku"],
				'MSRP'      => $product["msrp"],
				'Price'     => $product["price"],
				'SalePrice' => $product["price"],
				'Title'     => $product["name"],
				'ImageURL'  => $cat_img_url.$product->getImage(),
				'URL'         => $this->siteBaseURL.$product["url_path"],
				'CreatedAt'   => $product["created_at"],
				'UpdatedAt'   => $product["updated_at"],
				'ReviewsCount' => $reviewCount,
				'ReviewsRatingSum' => '',
				'Categories'  => $categoryData,
				//'TotalRecords' => "$totalProducts"
			);
			if(!empty($brand_attr)) {
				//echo $brand_attr;
				$brand_attr_val = !empty($product[$brand_attr]) ? $product[$brand_attr] : "";
				$prod_temp["BrandId"] = $brand_attr;
				$prod_temp["BrandName"] = $brand_attr_val;
			}
			$productsArray[] = $prod_temp;
		}
		//print_r($productsArray);
		echo json_encode($productsArray);
	}

	function catalogFirstAction() {
		$this->authorizeUser();
		$params = array(
			'siteUrl' => $this->siteBaseURL.'oauth',
			'requestTokenUrl' => $this->siteBaseURL.'oauth/initiate',
			'accessTokenUrl' => $this->siteBaseURL.'oauth/token',
			'authorizeUrl' => $this->adminAuthorizationUrl,
			//'consumerKey' => 'b33cd08e0f8a478e265dab30dde35d23',//Consumer key registered in server administration
			//'consumerSecret' => '12b34813bea12dc64bc86da0907f5216',//Consumer secret registered in server administration
			'callbackUrl' => $this->siteBaseURL.$this->routerName."/".$this->controllerName.'/callback',//Url of callback action below
			'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER
		);

		// Initiate oAuth consumer with above parameters
		$consumer = new Zend_Oauth_Consumer($params);
		// Get request token
		//$requestToken = $consumer->getRequestToken();
		// Get session
		//$session = Mage::getSingleton('core/session');
		// Save serialized request token object in session for later use
		//$session->setRequestToken(serialize($requestToken));

		$query_params = Mage::app()->getRequest()->getParams();
		$parameters     = array(
			'sortOrders'  => $query_params['sortOrders'],
			'pageSize'    => $query_params['pageSize'],
			'currentPage' => $query_params['currentPage'],
			//'filterBy'    => $query_params['filterBy']
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

	function build_http_query( $query ){
		$query_array = array();
		foreach( $query as $key => $key_value ){
			if($key_value == ''){continue;}
			if( $key == 'sortOrders' ) {
				$query_array[]  = "order=".urlencode( $key_value )."&dir=asc";
			} /*else if($key == "filterBy"){
				$todate =  urlencode( $key_value );
				$query_array[] = "searchCriteria[filter_groups][0][filters][0][field]=updated_at&searchCriteria[filter_groups][0][filters][0][value]=$todate&searchCriteria[filter_groups][0][filters][0][condition_type]=gteq";
			}*/ else if($key == "currentPage"){
				$page =  urlencode( $key_value );
				$query_array[] = "page=".$page;
			} else if($key == "pageSize"){
				$limit =  urlencode( $key_value );
				$query_array[] = "limit=".$limit;
			} /*else {
				$query_array[] = "searchCriteria[$key]=" .urlencode( $key_value );
			}*/
		}

		return implode( '&', $query_array );
	}

	function ordersAction() {
		$this->authorizeUser();
		$query_params = Mage::app()->getRequest()->getParams();
		$ordersCollection = Mage::getModel('sales/order')->getCollection();

		if(!empty($query_params['updatedSince'])) {
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
		  //$ordersCollection->getSelect()->setOrder($query_params['sortOrders'],'DESC');
		}

		if(!empty($query_params['sortOrders'])) {
			$ordersCollection->getSelect()->order($query_params['sortOrders'].' ASC');
			//$ordersCollection->getSelect()->setOrder($query_params['sortOrders'],'DESC');
		}

		$page = !empty($query_params['currentPage']) ? $query_params['currentPage'] : 1;
		$limit = !empty($query_params['pageSize']) ? $query_params['pageSize'] : 10;
		$ordersCollection->getSelect()->limit($limit, $page);
		$ordersArray = array();
		foreach($ordersCollection->getData() as $order) {
			//print_r($order->getData());

			if(!empty($order["customer_id"])) {
				$customerData = $this->getCustomerData($order["customer_id"]);
			}

			$orderItemsCollection = Mage::getModel('sales/order_item')->getCollection()->addAttributeToFilter('order_id', $order["entity_id"]);
			//print_r($orderItemsCollection->getData());
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
				//'TotalRecords'  => "$TotalRecords",
				'LineItems'     => $lineItems,
				'Customer'      => $customerData
			);
			$ordersArray[] = $order_temp;
		}
		print_r(json_encode($ordersArray));
	}

	function getCustomerData($customerId)
	{
		$customerObj = Mage::getModel('customer/customer')->load($customerId);
		$customer = $customerObj->getData();
		//print_r($customer);

		/*if(!empty($customer["default_billing"])) {
			$aid = $customer["default_billing"];
			$address = Mage::getModel('customer/address')->load($aid);
			print_r($customer);
		}*/
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
		//print_r($customerArray);
		return $customerArray;
	}
}