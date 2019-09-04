<?php

/**
 * Call BirdSend API from Woocommerce
 *
 * @return void
 */
function bswo_post_order( $order_id, $action = 'purchase_product' ) {
	$order = wc_get_order( $order_id );
	$email = $order->get_billing_email();
	$items = $order->get_items();
	$ipaddress = getenv( 'HTTP_CLIENT_IP' ) ?:
				  getenv( 'HTTP_X_FORWARDED_FOR' ) ?:
				  getenv( 'HTTP_X_FORWARDED' ) ?:
				  getenv( 'HTTP_FORWARDED_FOR' ) ?:
				  getenv( 'HTTP_FORWARDED' ) ?:
				  getenv( 'REMOTE_ADDR' );
	$site = get_site_url();
	$http = new GuzzleHttp\Client( [ 'verify' => false ] );
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
		}
		$post = [
			'form_params' => [ $options ],
		];
		$url = bswp_app_url( 'listener' );
		$response = $http->request( 'POST', $url, $post );

	} catch ( GuzzleHttp\Exception\ClientException $e ) {
		if ( WP_DEBUG ) {
			echo $e->getMessage();
		}
	}
	return false;
}

/**
 * Send when order completed
 *
 * @param int $order_id	Order id
 */
function purchase_success( $order_id ) {
	bswo_post_order( $order_id, 'purchase_product' );
}
add_action( 'woocommerce_order_status_completed', 'purchase_success', 1 );

/**
 * Send when order refunded
 */
function refund_success( $order_id ) {
	bswo_post_order( $order_id, 'refund' );
}
add_action( 'woocommerce_order_status_refunded', 'refund_success', 1 ); 

// function cancel_success() {
// 	bswo_post_order($order_id,'cancel');
// }

// add_action('woocommerce_subscription_payment_complete','cancel_success', 1);
?>