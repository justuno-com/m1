<?php
final class Justuno_Jumagext_RestController extends Mage_Core_Controller_Front_Action {
    protected $siteBaseURL;

    protected $moduleName;
    protected $routerName;
    protected $controllerName;

    protected $isAdminUser;
    protected $adminAuthorizationUrl;

    function _construct()
    {
        //$siteBaseURL = "http://10.91.0.122/mage1";
        //$siteBaseURL = Mage::getBaseUrl();
        //$siteBaseURL = Mage::getStoreConfig(Mage_Core_Model_Url::XML_PATH_SECURE_URL);
        $this->siteBaseURL = Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_WEB, true );
        $this->moduleName = Mage::app()->getRequest()->getModuleName();
        $this->routerName = Mage::app()->getRequest()->getRouteName();
        $this->controllerName = Mage::app()->getRequest()->getControllerName();

        $this->isAdminUser = true;
        $this->adminAuthorizationUrl = ($this->isAdminUser) ? $this->siteBaseURL."admin/oauth_authorize" : $this->siteBaseURL."oauth/authorize";
    }

    function indexAction() {
 
        $params = array(
            'siteUrl' => $this->siteBaseURL.'oauth',
            'requestTokenUrl' => $this->siteBaseURL.'oauth/initiate',
            'accessTokenUrl' => $this->siteBaseURL.'oauth/token',
            'authorizeUrl' => $this->adminAuthorizationUrl,
            'consumerKey' => 'b33cd08e0f8a478e265dab30dde35d23',//Consumer key registered in server administration
            'consumerSecret' => '12b34813bea12dc64bc86da0907f5216',//Consumer secret registered in server administration
            'callbackUrl' => $this->siteBaseURL.$this->routerName."/".$this->controllerName.'/callback',//Url of callback action below
            'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER
        );
        //echo "<pre>";
        /*print_r($params);
        echo "</pre>";*/
 
        // Initiate oAuth consumer with above parameters
        $consumer = new Zend_Oauth_Consumer($params);
        // Get request token
        $requestToken = $consumer->getRequestToken();
        // Get session
        $session = Mage::getSingleton('core/session');
        // Save serialized request token object in session for later use
        $session->setRequestToken(serialize($requestToken));
        // Redirect to authorize URL
        //$consumer->redirect();

        //$customer_data = array("username"=>"avnish1@gmail.com","password"=>"India1947");
        //$ret = $this->generateUserToken($customer_data);
        

        /*$oAuthClient = Mage::getModel('justuno_jumagext/oauth_client');
        //$oAuthClient->reset();
        $oAuthClient->init($params);
        $resp = $oAuthClient->authenticate($params);
        //echo "<br>Client authorized";
        echo "<br>".gettype($resp)."<br><pre>";
        print_r($resp);
        return;*/


        //echo "<pre>";
        parse_str($requestToken, $array); 
        //print_r($array);
        
        /*$acessToken = new Zend_Oauth_Token_Access;
        $acessToken->setParams(array(
            'oauth_token' => $array['oauth_token'],
            'oauth_token_secret' => $array['oauth_token_secret']
        ));
        print_r($acessToken);*/


        /*$restClient = $consumer->getHttpClient($params);
        //print_r($restClient);
        $restClient->setUri($siteBaseURL.'api/rest/products');
        $restClient->setHeaders('Accept', 'application/json');
        $restClient->setMethod(Zend_Http_Client::GET);
        $response = $restClient->request();
        //print_r($response);
        // Here we can see that response body contains json list of products
        $resp = $response->getBody();
        print_r(json_decode($resp));
        return;*/


        // Read and unserialize request token from session
        $oauthToken = $array["oauth_token"];
        $oauthTokenSecret = $array["oauth_token_secret"];

        $page_url = $siteBaseURL."/admin/oauth_authorize?oauth_token=".$oauthToken;
        $html = file_get_contents($page_url);
        //echo $html;
        //return;

        $doc = new DOMDocument('1.0');
        $doc->loadHTMLFile($page_url);
        //$titles = $doc->getElementsByTagName('title');
        //echo $titles->item(0)->nodeValue;
        $titles = $doc->getElementsByTagName('input');
        $form_key = "";
        foreach ($titles as $element) {
            $type = $element->getAttribute('type');
            $name = $element->getAttribute('name');
            $value = $element->getAttribute('value');
            //echo $type."...".$name."...".$value;
            if($type=="hidden" && $name=="form_key") {
                echo $type."...".$name."...".$value."<br>";
                $form_key = $value;
            }
        }

        if($form_key!="") {
            $fields = array();
            foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
            rtrim($fields_string, '&');

            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch,CURLOPT_URL, $url);
            curl_setopt($ch,CURLOPT_POST, count($fields));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

            //execute post
            $result = curl_exec($ch);

            //close connection
            curl_close($ch);
        }
        return;

        
        $url = $siteBaseURL."/admin/oauth_authorize?oauth_token=".$oauthToken;
        echo $url;
        echo "<pre>";
        //print_r($_SESSION);
        //Mage::app()->getFrontController()->getResponse()->setRedirect($url);
        $oauthClient = new OAuth($params["consumerKey"], $params["consumerSecret"], OAUTH_SIG_METHOD_HMACSHA1);
        $oauthClient->setToken($oauthToken, $oauthTokenSecret);
        $oauthClient->fetch($url, array("username"=>"admin"), 'POST', array('Content-Type' => 'application/json', 'Accept' => 'application/json'));
        $productsList = json_decode($oauthClient->getLastResponse());
        print_r($productsList);

        // Get HTTP client from access token object
        //$restClient = $acessToken->getHttpClient($params);
        // Set REST resource URL
        //$restClient->setUri($siteBaseURL.'admin/oauth_authorize?oauth_token=');
        // In Magento it is neccesary to set json or xml headers in order to work
        //$restClient->setHeaders('Accept', 'application/json');
        // Get method
        //$restClient->setMethod(Zend_Http_Client::POST);
        //Make REST request
        //$response = $restClient->request();

        echo "End of index function...";
    }
 
    function callbackAction() {
 
        //oAuth parameters
        $siteBaseURL = Mage::getBaseUrl( Mage_Core_Model_Store::URL_TYPE_WEB, true );
        
        $moduleName = Mage::app()->getRequest()->getModuleName();
        $routerName = Mage::app()->getRequest()->getRouteName();
        $controllerName = Mage::app()->getRequest()->getControllerName();

        $params = array(
            'siteUrl' => $siteBaseURL.'oauth',
            'requestTokenUrl' => $siteBaseURL.'oauth/initiate',
            'accessTokenUrl' => $siteBaseURL.'oauth/token',
            'authorizeUrl' => $siteBaseURL.'admin/oAuth_authorize',//This URL is used only if we authenticate as Admin user type
            'consumerKey' => 'b33cd08e0f8a478e265dab30dde35d23',//Consumer key registered in server administration
            'consumerSecret' => '12b34813bea12dc64bc86da0907f5216',//Consumer secret registered in server administration
            'callbackUrl' => $siteBaseURL.$routerName."/".$controllerName.'/callback',//Url of callback action below
        );
 
        // Get session
        $session = Mage::getSingleton('core/session');
        // Read and unserialize request token from session
        $requestToken = unserialize($session->getRequestToken());
        // Initiate oAuth consumer
        $consumer = new Zend_Oauth_Consumer($params);
        // Using oAuth parameters and request Token we got, get access token
        $acessToken = $consumer->getAccessToken($_GET, $requestToken);
        // Get HTTP client from access token object
        $restClient = $acessToken->getHttpClient($params);
        // Set REST resource URL
        $restClient->setUri($siteBaseURL.'api/rest/products');
        // In Magento it is neccesary to set json or xml headers in order to work
        $restClient->setHeaders('Accept', 'application/json');
        // Get method
        $restClient->setMethod(Zend_Http_Client::GET);
        //Make REST request
        $response = $restClient->request();
        // Here we can see that response body contains json list of products
        Zend_Debug::dump($response);
 
        return;
    }
 
    /*function callbackrejectAction() {
        echo "Callback rejected";
    }*/
 
    /*function doshitAction()
    {
        $params = array(
            'siteUrl' => 'http://www.appfactory.loc/magento/oauth',
            'requestTokenUrl' => 'http://www.appfactory.loc/magento/oauth/initiate',
            'accessTokenUrl' => 'http://www.appfactory.loc/magento/oauth/token',
            'consumerKey' => '531a7d194914fbd207766bcb022cdc94',
            'consumerSecret' => 'f51868e362b9211d4fde5ebf412080b0',
            'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER
        );

        // Initiate oAuth consumer
        $consumer = new Zend_Oauth_Consumer($params);
        // Using oAuth parameters and request Token we got, get access token
        $acessToken = new Zend_Oauth_Token_Access;
        $acessToken->setParams(array(
            'oauth_token' => 'e3d089e2b420cd3b940c3cf67587a95d',
            'oauth_token_secret' => 'ab2f46861defe6ee5740f7a748301367'
        ));


        // do a request
        $restClient = $acessToken->getHttpClient($params);
        $restClient->setUri('http://www.appfactory.loc/magento/api/rest/products');
        $restClient->setHeaders('Accept', 'application/json');
        $restClient->setMethod(Zend_Http_Client::GET);
        $response = $restClient->request();
        // Here we can see that response body contains json list of products
        print_r($response->getBody());

        return $response->getBody();
    }*/

    /*function testAction()
    {
        $restClient->setHeaders('Content-type', 'application/json');
        $restClient->setMethod(Zend_Http_Client::POST);
        $restClient->setParameterPost(array(
            'type_id'           => 'simple',
            'attribute_set_id'  => 4,
            'sku'               => 'SKUTEST' . uniqid(),
            'weight'            => 1,
            'status'            => 1,
            'visibility'        => 4,
            'name'              => 'Simple Product',
            'description'       => 'Simple Description',
            'short_description' => 'Simple Short Description',
            'price'             => 69.95,
            'tax_class_id'      => 0));
        $response = $restClient->request();
    }*/

    function loginAction()   /*  PERHAPS WORKS ONLY IN MAGENTO-2 */
    {
        $username = "avnish1@gmail.com";
        $password = "India1947";
        $userData = array("username" => $username, "password" => $password);
        $ch = curl_init("http://10.91.0.122/mage1/index.php/rest/V1/integration/customer/token");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Length: ".strlen(json_encode($userData))));

        $token = curl_exec($ch);
        print_r($token);

        $ch = curl_init("http://10.91.0.122/mage1/index.php/rest/V1/customers/authenticate/".$username."/".$password);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer ".json_decode($token)));

        $result = curl_exec($ch);

        $result = json_decode($result, 1);
        echo '<pre>';print_r($result);
    }

    function loginnAction()
    {
        $callbackUrl = $siteBaseURL."oauth_admin.php";
        $temporaryCredentialsRequestUrl = $siteBaseURL."oauth/initiate?oauth_callback=" . urlencode($callbackUrl);
        $adminAuthorizationUrl = $siteBaseURL.'admin/oauth_authorize';
        $accessTokenRequestUrl = $siteBaseURL.'oauth/token';
        $apiUrl = $siteBaseURL.'api/rest';
        $consumerKey = 'b33cd08e0f8a478e265dab30dde35d23';
        $consumerSecret = '12b34813bea12dc64bc86da0907f5216';

        session_start();
        if (!isset($_GET['oauth_token']) && isset($_SESSION['state']) && $_SESSION['state'] == 1) {
            $_SESSION['state'] = 0;
        }
        try {
            $authType = ($_SESSION['state'] == 2) ? OAUTH_AUTH_TYPE_AUTHORIZATION : OAUTH_AUTH_TYPE_URI;
            $oauthClient = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, $authType);
            $oauthClient->enableDebug();

            if (!isset($_GET['oauth_token']) && !$_SESSION['state']) {
                $requestToken = $oauthClient->getRequestToken($temporaryCredentialsRequestUrl);
                $_SESSION['secret'] = $requestToken['oauth_token_secret'];
                $_SESSION['state'] = 1;
                header('Location: ' . $adminAuthorizationUrl . '?oauth_token=' . $requestToken['oauth_token']);
                exit;
            } else if ($_SESSION['state'] == 1) {
                $oauthClient->setToken($_GET['oauth_token'], $_SESSION['secret']);
                $accessToken = $oauthClient->getAccessToken($accessTokenRequestUrl);
                $_SESSION['state'] = 2;
                $_SESSION['token'] = $accessToken['oauth_token'];
                $_SESSION['secret'] = $accessToken['oauth_token_secret'];
                header('Location: ' . $callbackUrl);
                exit;
            } else {
                $oauthClient->setToken($_SESSION['token'], $_SESSION['secret']);
                $resourceUrl = "$apiUrl/products";
                $productData = json_encode(array(
                    'type_id'           => 'simple',
                    'attribute_set_id'  => 4,
                    'sku'               => 'simple' . uniqid(),
                    'weight'            => 1,
                    'status'            => 1,
                    'visibility'        => 4,
                    'name'              => 'Simple Product',
                    'description'       => 'Simple Description',
                    'short_description' => 'Simple Short Description',
                    'price'             => 99.95,
                    'tax_class_id'      => 0,
                ));
                $headers = array('Content-Type' => 'application/json');
                $oauthClient->fetch($resourceUrl, $productData, OAUTH_HTTP_METHOD_POST, $headers);
                print_r($oauthClient->getLastResponseInfo());
            }
        } catch (OAuthException $e) {
            print_r($e);
        }
    }

    Public function generateUserToken($data)
    {
        // get the username & password of the user
        $username = $data['username'];
        $password = $data['password'];

        //echo "Email: ".$username;
        $websiteId = Mage::app()->getWebsite()->getId();
        //echo "<br>Website ID: ".$websiteId;

        $customer = Mage::getModel('customer/customer')->setWebsiteId($websiteId);//->loadByEmail($username);

        // check username password exists on our system or not
        //if (!$customer->authenticate($username, $password)) {
            // user is not registered with this Magento 1 website
        //}

        // load customer details if exist
        $customerObj = $customer->loadByEmail($username);
        $customerId =  $customerObj->getEntityId();

        // check user token is already generated or not and return token if exist and return the token from database if exist
        $customerapitokensObj = Mage::getModel('customerapi/customerapitokens');
        //print_r(get_class_methods($customerapitokensObj));
        return gettype($customerapitokensObj);
        $customerapitokensObj->load($customerId, 'customer_id');
        if ($customerapitokensObj->getToken()){
            echo 'IND';
            $token = $customerapitokensObj->getToken();
            return $token;
        }
        return "last";

        // generate token and save customer tokens
        try {
                    // create MD5 token with username and password and date comibanation to make token unique
            $date  = date("Y-m-d H:i:s")."".$username."".$password;
            $token = md5(uniqid($date, true));
                    
            $customerapitokensObj->setCustomerId($customerId);
            $customerapitokensObj->setToken($token);
            $customerapitokensObj->setStatus(1);
            $customerapitokensObj->setDate(date("Y-m-d H:i:s"));
            $newUserData = $customerapitokensObj->save();
                    
            return $token; 
        } catch (Exception $e) {
            $this->_critical($e->getMessage(), Mage_Api2_Model_Server::HTTP_UNAUTHORIZED);
        }
    }
}