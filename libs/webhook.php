<?php

/**
 * Main webhook handler
 *
 * @return void
 */
function bswp_webhook_handler() {
	if ( ! empty( $_GET['bswp_webhook'] ) ) {
		do_action( 'bswp_webhook_' . wp_unslash( $_GET['bswp_webhook'] ) );
		exit;
	}
}
add_action( 'template_redirect', 'bswp_webhook_handler', 2 );

/**
 * Webhook handler: get_metadata
 *
 * @return void
 */
function bswp_webhook_get_metadata() {
	$data = [];
	$meta = (array) ( isset( $_GET[ 'bswp_meta_keys' ] ) ? $_GET[ 'bswp_meta_keys' ] : [] );

	if ( in_array( 'post-types', $meta ) ) {
		$post_types_args = [ 'public' => true ];
		$data['post_types'] = array_values(
			array_map(
				function ( $type ) {
					return [
						'name' => $type->name,
						'label' => $type->label,
					];
				},
				get_post_types( $post_types_args, 'objects' )
			)
		);
	}

	if ( in_array( 'post-tags', $meta ) ) {
		$post_tags_args = [ 'hide_empty' => false ];
		$data[ 'post_tags' ] = array_map( function ( $tag ) {
			return [
				'slug' => $tag->slug,
				'name' => $tag->name,
			];
		}, get_tags( $post_tags_args ) );
	}

	if ( in_array( 'post-categories', $meta ) ) {
		$post_categories_args = [];
		$data['post_categories'] = array_map(
			function ( $category ) {
				return [
					'slug' => $category->slug,
					'name' => $category->name,
				];
			},
			get_categories( $post_categories_args )
		);
	}

	header( 'Content-Type: application/json' );
	echo json_encode( $data );
}
add_action( 'bswp_webhook_get_metadata', 'bswp_webhook_get_metadata' );

/**
 * Webhook handler: get woocommerce product list
 *
 * @return JSON
 */
function bswp_webhook_get_product() {
	$cat_slug = isset( $_GET['bswp_cat_slug'] ) ? $_GET['bswp_cat_slug'] : [];
	$args = array(
		'limit' => -1,
	);
	if ( $cat_slug ) {
		$args['category'] = [ $cat_slug ];
	}

	if ( function_exists( 'wc_get_products' ) ) {
		$products = wc_get_products( $args );
		$data = array();
		$i = 0;
		foreach ( $products as $product ) {
			$data[ $i ]['id'] = $product->get_id();
			$data[ $i ]['name'] = $product->get_name();
			$data[ $i ]['slug'] = $product->get_name();
			$data[ $i ]['sku'] = $product->get_sku();
			$i++;
		}
		echo json_encode( [ 'products' => $data ] );
	} else {
		echo 0;
	}
}

add_action( 'bswp_webhook_get_product', 'bswp_webhook_get_product' );


/**
 * Webhook handler : Get woocommerce category
 * 
 * @return JSON
 */
function bswp_webhook_get_categories() {
	header( 'Content-Type: application/json' );
	$data = bswp_wc_categories();
	$data = bswp_reset_array( $data );
	echo json_encode( $data );
}
add_action( 'bswp_webhook_get_categories', 'bswp_webhook_get_categories' );

/**
 * Webhook handler : Get woocommerce order status
 * 
 * @return JSON
 */
function bswp_webhook_get_order_by_id() {
	$order_id = $_GET['id'];
	header( 'Content-Type: application/json' );
	$order =  wc_get_order( $order_id );
	echo json_encode( $order->get_data());
}
add_action( 'bswp_webhook_get_order_by_id', 'bswp_webhook_get_order_by_id');