<?php
class Justuno_Jumagext_IndexController extends Mage_Core_Controller_Front_Action{
    function getcartAction(){
        $result = Mage::getModel("checkout/cart")->getItems();
        $this->getResponse()->setHeader('Content-type','application/json',true);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    function getordersAction(){
        die('Here');
    }

    function indexAction () {
        ini_set("display_errors", 1);
        $base_url       = Mage::getBaseUrl(); /*Mage_Core_Model_Store::URL_TYPE_WEB*/
        $apiResources   = "products?page=1&limit=5";

        $isAdminUser    = false;
        $adminUrl       = "justuno_admin";
        $callbackUrl    = $base_url . "callbackUrl/";
        $host           = $base_url;
//echo $callbackUrl; exit(0);
        $consumerKey    = '5c0f49164bacfe23a3a84c7e00e55301';
        $consumerSecret = '44757e0b7cc1b1b40f3dbbe52f53c2a4';

        $temporaryCredentialsRequestUrl = $host . "oauth/initiate?oauth_callback=" . urlencode($callbackUrl);
        $adminAuthorizationUrl = ($isAdminUser) ? $host . $adminUrl . "/oauth_authorize" : $host . "oauth/authorize";
        $accessTokenRequestUrl = $host . "oauth/token";

        $apiUrl         = $host . "api/rest/";
        session_start();

        if (!isset($_GET['oauth_token']) && isset($_SESSION['state']) && $_SESSION['state'] == 1) {
            $_SESSION['state'] = 0;
        }

        //echo resourceUrl;
        //exit;

        try {
            $authType = ($_SESSION['state'] == 2) ? OAUTH_AUTH_TYPE_AUTHORIZATION : OAUTH_AUTH_TYPE_URI;
            $oauthClient = new OAuth($consumerKey, $consumerSecret, OAUTH_SIG_METHOD_HMACSHA1, $authType);
            $oauthClient->enableDebug();

            //echo $_SESSION['state'] . " STATE";
            if (!isset($_GET['oauth_token']) && !$_SESSION['state']) {

                ini_set('max_execution_time', -1);
                $requestToken = $oauthClient->getRequestToken($temporaryCredentialsRequestUrl);
                $_SESSION['secret'] = $requestToken['oauth_token_secret'];
                $_SESSION['state'] = 1;
                //header('Location: ' . $adminAuthorizationUrl . '?oauth_token=' . $requestToken['oauth_token']);
                $redirectUrl = $adminAuthorizationUrl . '?oauth_token=' . $requestToken['oauth_token'];
                //$redirectUrl = Mage::getUrl('oauth/authorize', array('oauth_token'=>$requestToken['oauth_token']));
                //$this->_redirectUrl($redirectUrl);
                Mage::app()->getFrontController()->getResponse()->setRedirect($redirectUrl); /*->sendResponse()*/

            } else if ($_SESSION['state'] == 1) {

                $oauthClient->setToken($_GET['oauth_token'], $_SESSION['secret']);
                $accessToken = $oauthClient->getAccessToken($accessTokenRequestUrl);
                $_SESSION['state'] = 2; //echo "<br>In Else-IF, and STATE changed to ".$_SESSION['state']; exit(0);
                $_SESSION['token'] = $accessToken['oauth_token'];
                $_SESSION['secret'] = $accessToken['oauth_token_secret'];
                // echo $callbackUrl; echo "<pre>"; print_R($_SESSION); die('check');
                //header('Location: ' . $callbackUrl);
                Mage::app()->getFrontController()->getResponse()->setRedirect($callbackUrl);
                exit;

            } else {
                $oauthClient->setToken($_SESSION['token'], $_SESSION['secret']);

                $resourceUrl = $apiUrl.$apiResources;
                $oauthClient->fetch($resourceUrl, array(), 'GET', array('Content-Type' => 'application/json', 'Accept' => 'application/json'));

                $productsList = json_decode($oauthClient->getLastResponse());

                echo "<pre>"; print_r($productsList); die("rukk");

                $catDetails = $categoryData = $formattedJson = array();


                    foreach($productsList as $product) {
                        $id         = $product->entity_id;
                        $loadpro    = Mage::getModel('catalog/product')->load($id);
                        $categories = $loadpro->getCategoryIds();
                        $created    = $loadpro->created_at;
                        $createdAt  = explode("T", $created);
                        $time       = explode( "-", $createdAt['1'] );
                        $dateTime   = $createdAt['0'] . ' ' .$time[0];

                        $updated    = $loadpro->updated_at;
                        $updatedAt  = explode("T", $updated);
                        $updatetime = explode( "-", $updatedAt['1'] );
                        $updateTime = $updatedAt['0'] . ' ' .$updatetime[0];

                        $prodata    = $loadpro->getData();
                        $media      = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);



                        if(!empty($prodata['media_gallery']['images']) ) {
                            $gallery = $prodata['media_gallery']['images'];
                            $incr = 1;
                            foreach($gallery as $pimage ){
                                $image['ImageUrl'.$incr] =  $media.$pimage['file'];
                            }
                        } else {
                            $image = $media.'catalog/product'. $prodata['image'];
                        }
                        // echo "<pre>"; print_r($image); die;

                        $reviews = Mage::getModel('review/review')
                                    ->getResourceCollection()
                                    ->addStoreFilter(Mage::app()->getStore()->getId())
                                    ->addEntityFilter('product', $id)
                                    ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
                                    ->setDateOrder()
                                    ->addRateVotes();
                        /**
                         * average of ratings/reviews
                         */
                        $avg = 0;
                        $ratings = array();
                        $reviewCount = count($reviews);
                        if (count($reviews) > 0) {
                            foreach ($reviews->getItems() as $review) {
                                foreach( $review->getRatingVotes() as $vote ) {
                                    $ratings[] = $vote->getPercent();
                                }
                            }
                            $avg = array_sum($ratings)/count($ratings);
                        }
                        if(!empty ($categories)) {
                            foreach ($categories as $category_id) {
                                $catData   = Mage::getModel('catalog/category')->load($category_id);
                                if(!empty($catData->getImage())) {
                                    $catimg =  $media . 'catalog/category/' .$catData->getImage();
                                } else $catimg = '';

                                $catDetails['ID']           = $catData->getEntityId();
                                $catDetails['Name']         = $catData->getName();
                                $catDetails['Description']  = strip_tags($catData->getDescription());
                                $catDetails['Keyword']      = $catData->getMetaKeywords();
                                $catDetails['URL']          = $catData->getUrl($catData->getData());
                                $catDetails['ImageURL']     = $catimg;
                                $categoryData[$catData->getEntityId()] = $catDetails;
                            }
                        }
                        // echo "<pre>"; print_r($categoryData); die('sa');
                        unset($catDetails);




                        $formattedJson[]  = array_merge( array(
                                                'ID'          => $product->sku,
                                                'MSRP'        => $product->special_price,
                                                'Price'       => $product->price,
                                                'SalePrice'   => $product->special_price,
                                                'Title'       => $product->name,
                                                'URL'         => $storeUrl.$url,
                                                'CreatedAt'   => $dateTime,
                                                'UpdatedAt'   => $updateTime,
                                                'ReviewsCount' => '',
                                                'ReviewsRatingSum' => '',
                                                'BrandId'     => '',
                                                'BrandName'   => '',
                                                'Categories'  => $categoryData
                                            ), $image);
                    }
                // }
                echo json_encode($formattedJson); exit('test');
                // echo "</pre>";


            }
        } catch (OAuthException $e) {
            echo "<pre>";
            print_r($e->getMessage());
            echo "<br/>";
            print_r($e->lastResponse);
            echo "</pre>";
        }
    }

}
