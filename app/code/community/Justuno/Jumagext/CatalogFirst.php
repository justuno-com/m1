<?php
use Justuno_Jumagext_Response as R;
// 2019-10-31
final class Justuno_Jumagext_CatalogFirst {
	/**
	 * 2019-10-31
	 * @used-by Justuno_Jumagext_ResponseController::catalogFirstAction()
	 */
	static function p() {
		R::authorize();
		$req = Mage::app()->getRequest(); /** @var Mage_Core_Controller_Request_Http $req */
		$consumer = new Zend_Oauth_Consumer([
			'accessTokenUrl' => R::url('oauth/token')
			,'authorizeUrl' => R::url('oauth/authorize')
			,'callbackUrl' => R::url("{$req->getRouteName()}/{$req->getControllerName()}/callback")
			,'requestScheme' => Zend_Oauth::REQUEST_SCHEME_HEADER
			,'requestTokenUrl' => R::url('oauth/initiate')
			,'siteUrl' => R::url('oauth')
		]);
		$c = $consumer->getHttpClient(); /** @var Zend_Http_Client $c */
		$c->setHeaders('Accept', 'application/json');
		$c->setMethod(Zend_Http_Client::GET);
		$c->setUri(R::url('api/rest/products?' . self::query([
			'currentPage' => $req->getParam('currentPage')
			,'pageSize' => $req->getParam('pageSize')
			,'sortOrders' => $req->getParam('sortOrders')
		])));
		print_r($c->request()->getBody());
	}

	/**
	 * 2019-10-27
	 * @used-by p()
	 * @param $query
	 * @return string
	 */
	private static function query($query) {
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
}