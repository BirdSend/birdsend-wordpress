<?php

/**
 * Call BirdSend API from Woocommerce
 *
 * @return void
 */
function bswo_post_order( $order_id, $action = 'purchase_product', $items= array()) {
	$order = wc_get_order( $order_id );
	$email = $order->get_billing_email();
	if ( empty( $items ) ) {
		$items = $order->get_items();
	}
	$ipaddress = getenv( 'HTTP_CLIENT_IP' ) ?:
				  getenv( 'HTTP_X_FORWARDED_FOR' ) ?:
				  getenv( 'HTTP_X_FORWARDED' ) ?:
				  getenv( 'HTTP_FORWARDED_FOR' ) ?:
				  getenv( 'HTTP_FORWARDED' ) ?:
				  getenv( 'REMOTE_ADDR' );
	$site = get_site_url();
	$http = new GuzzleHttp\Client( [ 'verify' => false, 'http_errors' => true ] );
	$categories = array();
	foreach ( $items as $item ) {
		$category = get_the_terms( $item['product_id'], 'product_cat' );
		foreach ( $category as $cat ) {
			$parent   = bwsp_get_parent( $cat->term_id );
			array_push( $categories, $cat->term_id );
			if ( $parent ) {
				$categories = array_merge( $categories, $parent );
			}
		}
		$products[] = $item['product_id'];
	}

	try {
		if ( $email ) {
			$options['email'] = $email;
			$options['ipaddress'] = $ipaddress;
			$options['categories'] = array_values( array_unique( $categories ) );
			$options['products'] = $products;
			$options['action'] = $action;
			$options['site_url'] = $site;
			$options['order_id'] = $order_id;
		}
		$post = [
			'form_params' => [ $options ],
		];
		
		$url = bswp_app_url( 'listener' );
		$response = $http->request( 'POST', $url, $post );

	} catch ( \Exception $e ) {
		if ( WP_DEBUG ) {
			$order->add_order_note("BirdSend update failed: " . $e->getMessage());	
		}
	} 
	return false;
}

/**
 * Create Conversion
 */
function bswp_add_conversion( $order_id ) {
	$order = wc_get_order( $order_id );
	$items = $order->get_items();
	$email = $order->get_billing_email();
	
	if ( $email ) {
		$contact = bswp_api_request("GET", "contacts", ['search_by' => 'email', 'keyword' => $email]);
		//if contact exist
		if ( $contact ) {
			//Create Product
			foreach ($items as $item) {
				$item = wc_get_product($item['product_id']);
				$product = bswp_create_conversion_product($item);
				$data = [
					"contact_id" => $contact['data'][0]['contact_id'],
					"conversion_product_id" => $product['conversion_product_id'],
					"amount"     => $item->get_price(),
					"payment_id" => $order_id,
					"currency"	 => 'USD',
				];
				bswp_create_conversion($data);
			}	
			
		}
	}
	return false;
}

/**
 * Remove Conversion
 */
function bswp_remove_conversion( $order_id ) {
	$order = wc_get_order( $order_id );
	$items = $order->get_items();
	$email = $order->get_billing_email();

	if ($email) {
		$contact = bswp_api_request("GET", "contacts", ['search_by' => 'email', 'keyword' => $email]);
		if ( $contact ) {
			foreach ($items as $item) {
				//Get Item conversion product
				$conversion_products = bswp_api_request(
					"GET",
					"conversion_products?keyword=source:woocommerce;name:".$item->get_name()
				);
				$product_id = $conversion_products['data'][0]['conversion_product_id'];
				$converted = bswp_api_request(
					"GET", 
					"conversions?keyword=contact_id:".$contact['data'][0]['contact_id'].";conversion_product_id:".$product_id.";payment_id:".$order_id);
				//Create Product
				if ( $converted ) {
					foreach ( $converted['data'] as $convert ) {
						$convert_id = $convert['conversion_id'];
						bswp_api_request("DELETE", "conversions/".$convert_id);
					}
				}
			}
		}
	}
}

/**
 * Create Conversion product
 */
function bswp_create_conversion_product( $item ) {
	$product = [
		"name"	=> $item->get_name(),
		"source" => "woocommerce",
		"sku" 	=>  get_home_url()."-".$item->get_sku(),
		"value" => $item->get_price(),
	];
	$response = bswp_api_request("POST", "conversion_products", $product);
	return $response;
}

/**
 * API to conversion
 */
function bswp_create_conversion($conversion) {
	bswp_api_request("POST", "conversions", $conversion);
}

/**
 * API to refund
 */
function bswp_refund_conversion($conversion) {
	bswp_api_request("DEL", "conversions", $conversion);
}

/**
 * Send when order completed
 *
 * @param int $order_id	Order id
 */
function purchase_success( $order_id ) {
	bswo_post_order( $order_id, 'purchase_product' );
	bswp_add_conversion($order_id);
}
add_action( 'woocommerce_order_status_completed', 'purchase_success', 1 );

/**
 * Send when order refunded
 */
function refund_success( $order_id ) {
	bswo_post_order( $order_id, 'refund' );
	bswp_remove_conversion($order_id);
}
add_action( 'woocommerce_order_status_refunded', 'refund_success', 1 ); 

/**
 * Abandon
 */
function new_order( $order_id ) {
 	bswo_post_order( $order_id, 'abandon' );
}
add_action( 'woocommerce_update_order', 'new_order', 1 );

/**
 * Cancel Subscription
 */
function cancel_subscription( $subscription ) {
	$orders =  $subscription->get_related_orders();
	foreach ($orders as $order) {
		$id = $order;
	}
	if ( $subscription->get_status() == "cancelled" ) {
		$items = $subscription->get_items();
		bswo_post_order( $id, 'subscription_cancel', $items );
	}
}
add_action( 'woocommerce_subscription_status_updated', 'cancel_subscription', 1 );

/**
 * Record 
 */
function convert_subscription( $subscription ) {
	$orders =  $subscription->get_related_orders();
	foreach ($orders as $order) {
		$id = $order;
	}
	bswp_add_conversion($id);

}
add_action('woocommerce_subscription_payment_complete','convert_subscription', 1);
?>