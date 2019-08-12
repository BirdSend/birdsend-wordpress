<?php

/**
 * Call BirdSend API from Woocommerce 
 *
 * @return void
 */

 function bswo_post_order($order_id) {
   	$order 	= wc_get_order( $order_id );
	$user 	= $order->get_user();
	$items 	= $order->get_items();
	$http 	= new GuzzleHttp\Client;
	echo "<pre>";

	foreach($items as $item) {

		$terms[] = get_the_terms ($item['product_id'], 'product_cat' );
		print_r($item);
	}
	var_dump($terms);
	die();
	try {
		$options = array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $token
			)
		);

		if ($user) {
			$options['form_params'] = $user;
		}

		$response = $http->request('POST', "http://bird.co/webhook/wplistener", $options);
		$response = json_decode((string) $response->getBody(), true);

		return $response;
	} catch (GuzzleHttp\Exception\ClientException $e) {
		if (WP_DEBUG) {
			echo $e->getMessage();
		}
	}
	die();
	return false;
}

add_action('woocommerce_order_status_completed','bswo_post_order', 1);
 	//add_action('woocommerce_subscription_payment_complete','bswo_post_order', 1);
 	//add_action('woocommerce_subscription_payment_complete','bswo_post_order', 1);

?>