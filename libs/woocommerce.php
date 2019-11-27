<?php

/**
 * Call BirdSend API from WooCommerce
 *
 * @param int    $order_id Order id.
 * @param string $action Action type.
 * @param array  $items List of products/items.
 *
 * @return boolean
 */
function bswo_post_order( $order_id, $action = 'purchase_product', $items = array() ) {
	$order_id = absint( $order_id );
	$order    = wc_get_order( $order_id );
	if ( ! $order ) {
		return false;
	}

	$email = sanitize_email( $order->get_billing_email() );
	if ( empty( $items ) ) {
		$items = $order->get_items();
	}

	$ipaddress  = bwsp_get_ipaddress();
	$site_url   = get_site_url();
	$http       = new GuzzleHttp\Client( array( 'verify' => false ) );
	$categories = array();

	foreach ( $items as $item ) {
		$product_id = absint( $item['product_id'] );
		$category   = get_the_terms( $product_id, 'product_cat' );
		foreach ( $category as $cat ) {
			$parent = bwsp_get_parent( $cat->term_id );
			array_push( $categories, $cat->term_id );
			if ( $parent ) {
				$categories = array_merge( $categories, $parent );
			}
		}
		$products[] = $product_id;
	}

	try {
		if ( $email ) {
			$options['email']      = $email;
			$options['ipaddress']  = $ipaddress;
			$options['categories'] = array_values( array_unique( $categories ) );
			$options['products']   = $products;
			$options['action']     = sanitize_text_field( $action );
			$options['site_url']   = esc_url_raw( $site_url );
			$options['order_id']   = $order_id;
		}
		$post = array(
			'form_params' => array( $options ),
		);

		$url      = wp_http_validate_url( bswp_app_url( 'listener' ) );
		$response = $http->request( 'POST', $url, $post );

	} catch ( GuzzleHttp\Exception\ClientException $e ) {
		if ( WP_DEBUG ) {
			wp_die( esc_attr( $e->getMessage() ) );
		}
		return false;
	}
	return true;
}

/**
 * Create Conversion
 *
 * @param int $order_id Order id.
 *
 * @return boolean
 */
function bswp_add_conversion( $order_id ) {
	$order_id = absint( $order_id );
	$order    = wc_get_order( $order_id );
	if ( ! $order ) {
		return false;
	}

	$items = $order->get_items();
	$email = sanitize_email( $order->get_billing_email() );
	if ( $email ) {
		$contact = bswp_api_request(
			'GET',
			'contacts',
			array(
				'search_by' => 'email',
				'keyword'   => $email,
			)
		);
		// if contact exists.
		if ( $contact ) {
			// Create Product.
			foreach ( $items as $item ) {
				$product_id = absint( $item['product_id'] );
				$item       = wc_get_product( $product_id );
				if ( ! $item ) {
					continue;
				}

				$product = bswp_create_conversion_product( $item );
				if ( ! $product ) {
					continue;
				}
				$data = array(
					'contact_id'            => absint( $contact['data'][0]['contact_id'] ),
					'conversion_product_id' => absint( $product['conversion_product_id'] ),
					'amount'                => $item->get_price(),
					'payment_id'            => $order_id,
					'currency'              => 'USD',
				);
				bswp_create_conversion( $data );
			}
			return true;
		}
	}
	return false;
}

/**
 * Remove Conversion
 *
 * @param int $order_id Order id.
 *
 * @return boolean
 */
function bswp_remove_conversion( $order_id ) {
	$order_id = absint( $order_id );
	$order    = wc_get_order( $order_id );
	if ( ! $order ) {
		return false;
	}

	$items = $order->get_items();
	$email = sanitize_email( $order->get_billing_email() );
	if ( $email ) {
		$contact = bswp_api_request(
			'GET',
			'contacts',
			array(
				'search_by' => 'email',
				'keyword'   => $email,
			)
		);
		if ( $contact ) {
			foreach ( $items as $item ) {
				// Get purchased products.
				$purchased_products = bswp_api_request(
					'GET',
					'conversion_products?keyword=source:woocommerce;name:' . $item->get_name()
				);

				if ( ! $purchased_products ) {
					continue;
				}

				$product_id = absint( $purchased_products['data'][0]['conversion_product_id'] );
				$contact_id = absint( $contact['data'][0]['contact_id'] );
				$converted  = bswp_api_request(
					'GET',
					'conversions?keyword=contact_id:' . $contact_id . ';conversion_product_id:' . $product_id . ';payment_id:' . $order_id
				);
				// Delete conversion data.
				if ( $converted ) {
					foreach ( $converted['data'] as $convert ) {
						$convert_id = absint( $convert['conversion_id'] );
						bswp_api_request( 'DELETE', 'conversions/' . $convert_id );
					}
				}
			}
			return true;
		}
	}

	return false;
}

/**
 * Create Conversion product
 *
 * @param WC_Product $item WooCommerce product item.
 *
 * @return array|boolean Array on success; Boolean false on failure.
 */
function bswp_create_conversion_product( $item ) {
	if ( ! $item instanceof WC_Product ) {
		return false;
	}

	$home_url = esc_url_raw( get_home_url() );
	$product  = array(
		'name'   => $item->get_name(),
		'source' => 'woocommerce',
		'sku'    => $home_url . '-' . $item->get_sku(),
	);
	$response = bswp_api_request( 'POST', 'conversion_products', $product );
	return $response;
}

/**
 * Create conversion on BirdSend via API
 *
 * @param array $conversion Conversion data.
 *
 * @return array|boolean Array on success; Boolean false on failure.
 */
function bswp_create_conversion( $conversion ) {
	if ( ! is_array( $conversion ) ) {
		return false;
	}
	return bswp_api_request( 'POST', 'conversions', $conversion );
}

/**
 * Refund/remove conversion on BirdSend via API
 *
 * @param array $conversion Conversion data.
 *
 * @return array|boolean Array on success; Boolean false on failure.
 */
function bswp_refund_conversion( $conversion ) {
	if ( ! is_array( $conversion ) ) {
		return false;
	}
	return bswp_api_request( 'DEL', 'conversions', $conversion );
}

/**
 * Send when order completed
 *
 * @param int $order_id Order id.
 */
function purchase_success( $order_id ) {
	$order_id = absint( $order_id );
	bswo_post_order( $order_id, 'purchase_product' );
	bswp_add_conversion( $order_id );
}
add_action( 'woocommerce_order_status_completed', 'purchase_success', 1 );

/**
 * Send when order refunded
 *
 * @param int $order_id Order id.
 */
function refund_success( $order_id ) {
	bswo_post_order( $order_id, 'refund' );
	bswp_remove_conversion( $order_id );
}
add_action( 'woocommerce_order_status_refunded', 'refund_success', 1 );

/**
 * Abandon
 *
 * @param int $order_id Order id.
 */
function new_order( $order_id ) {
	bswo_post_order( $order_id, 'abandon' );
}
add_action( 'woocommerce_update_order', 'new_order', 1 );

/**
 * Cancel Subscription
 *
 * @param WC_Subscription $subscription WooCommerce subscription object.
 */
function cancel_subscription( $subscription ) {
	$orders = $subscription->get_related_orders();
	foreach ( $orders as $order ) {
		$id = $order;
	}
	if ( $subscription->get_status() == 'cancelled' ) {
		$items = $subscription->get_items();
		bswo_post_order( $id, 'subscription_cancel', $items );
	}
}
add_action( 'woocommerce_subscription_status_updated', 'cancel_subscription', 1 );

/**
 * Record Subscription as conversion
 *
 * @param WC_Subscription $subscription WooCommerce subscription object.
 */
function convert_subscription( $subscription ) {
	$orders = $subscription->get_related_orders();
	foreach ( $orders as $order ) {
		$id = $order;
	}
	bswp_add_conversion( $id );

}
add_action( 'woocommerce_subscription_payment_complete', 'convert_subscription', 1 );

