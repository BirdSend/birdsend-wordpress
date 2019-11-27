<?php

/**
 * Main webhook handler
 *
 * @return void
 */
function bswp_webhook_handler() {
	if ( ! empty( $_GET['bswp_webhook'] ) ) {
		$hook_name = sanitize_text_field( wp_unslash( $_GET['bswp_webhook'] ) );
		do_action( "bswp_webhook_{$hook_name}"  );
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
	$data = array();
	$meta = (array) ( isset( $_GET['bswp_meta_keys'] ) ? sanitize_text_field( wp_unslash( $_GET['bswp_meta_keys'] ) ) : array() );

	if ( in_array( 'post-types', $meta ) ) {
		$post_types_args    = array( 'public' => true );
		$data['post_types'] = array_values(
			array_map(
				function ( $type ) {
					return array(
						'name'  => $type->name,
						'label' => $type->label,
					);
				},
				get_post_types( $post_types_args, 'objects' )
			)
		);
	}

	if ( in_array( 'post-tags', $meta ) ) {
		$post_tags_args    = array( 'hide_empty' => false );
		$data['post_tags'] = array_map(
			function ( $tag ) {
					return array(
						'slug' => $tag->slug,
						'name' => $tag->name,
					);
			},
			get_tags( $post_tags_args )
		);
	}

	if ( in_array( 'post-categories', $meta ) ) {
		$post_categories_args    = array();
		$data['post_categories'] = array_map(
			function ( $category ) {
				return array(
					'slug' => $category->slug,
					'name' => $category->name,
				);
			},
			get_categories( $post_categories_args )
		);
	}

	// Send JSON response.
	wp_send_json( $data );
}
add_action( 'bswp_webhook_get_metadata', 'bswp_webhook_get_metadata' );

/**
 * Webhook handler: get woocommerce product list
 *
 * @return void
 */
function bswp_webhook_get_product() {
	$cat_slug = isset( $_GET['bswp_cat_slug'] ) ? sanitize_text_field( wp_unslash( $_GET['bswp_cat_slug'] ) ) : array();
	$args     = array(
		'limit' => -1,
	);
	if ( $cat_slug ) {
		$args['category'] = array( $cat_slug );
	}

	if ( function_exists( 'wc_get_products' ) ) {
		$products = wc_get_products( $args );
		$data     = array();
		$i        = 0;
		foreach ( $products as $product ) {
			$data[ $i ]['id']   = $product->get_id();
			$data[ $i ]['name'] = $product->get_name();
			$data[ $i ]['slug'] = $product->get_name();
			$data[ $i ]['sku']  = $product->get_sku();
			$i++;
		}
		// Send JSON response.
		wp_send_json( array( 'products' => $data ) );
	} else {
		echo 0;
	}
}

add_action( 'bswp_webhook_get_product', 'bswp_webhook_get_product' );


/**
 * Webhook handler : Get WooCommerce category
 *
 * @return void
 */
function bswp_webhook_get_categories() {
	header( 'Content-Type: application/json' );
	$data = bswp_wc_categories();
	$data = bswp_reset_array( $data );

	// Send JSON response.
	wp_send_json( $data );
}
add_action( 'bswp_webhook_get_categories', 'bswp_webhook_get_categories' );

/**
 * Webhook handler : Get WooCommerce order status
 *
 * @return void
 */
function bswp_webhook_get_order_by_id() {
	if ( empty( $_GET['id'] ) ) {
		echo '';
	}

	$order_id = absint( $_GET['id'] );
	header( 'Content-Type: application/json' );
	$order = wc_get_order( $order_id );

	if ( $order ) {
		// Send JSON response.
		wp_send_json( $order->get_data() );
	} else {
		echo '';
	}
}
add_action( 'bswp_webhook_get_order_by_id', 'bswp_webhook_get_order_by_id' );
