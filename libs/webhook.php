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
	$order = wc_get_order( $order_id );
	echo json_encode( $order->get_data());
}
add_action( 'bswp_webhook_get_order_by_id', 'bswp_webhook_get_order_by_id');

/**
 * Webhook handler: form_updated
 *
 * @return void
 */
function bswp_webhook_form_updated() {
	global $wpdb;

	$data = json_decode( trim( file_get_contents( 'php://input' ) ), true );
	bswp_activity_log('form', 'updated', $data, $data['form_id'], 'forms');

	$data = array(
		'id' => $data['form_id'],
		'name' => $data['name'],
		'active' => $data['active'],
		'type' => $data['type'],
		'triggers' => json_encode( $data['triggers'] ),
		'placements_count' => $data['placements_count'],
		'updated_at' => $data['updated_at'],
		'version' => $data['version'],
		'last_sync_at' => current_time( 'Y-m-d H:i:s', true ),
		'stats_displays_original' => $data['stats']['displays'],
		'stats_submissions_original' => $data['stats']['submissions']
	);

	if ( ( $form = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}bswp_forms WHERE id = {$data['id']}" ) )
		&& $form->version != $data['version']
	) {
		$data['raw_html'] = null;
		$data['wg_html'] = null;
	}

	if ( $form ) {
		// We use update to reserve display_stats value
		$wpdb->update( "{$wpdb->prefix}bswp_forms", $data, array( 'id' => $data['id'] ) );
	} else {
		$wpdb->replace( "{$wpdb->prefix}bswp_forms", $data );
	}

	wp_cache_flush();

	header( 'Content-Type: application/json' );
	echo json_encode(['success' => true, 'id' => $data['form_id']]);
}
add_action( 'bswp_webhook_form_updated', 'bswp_webhook_form_updated');

/**
 * Webhook handler: form_deleted
 *
 * @return void
 */
function bswp_webhook_form_deleted() {
	global $wpdb;

	$data = json_decode( trim( file_get_contents( 'php://input' ) ), true );
	bswp_activity_log('form', 'deleted', $data, $data['form_id'], 'forms');

	$success = false;

	if ( ( $form = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}bswp_forms WHERE id = {$data['form_id']}" ) ) ) {
		$wpdb->update( "{$wpdb->prefix}bswp_forms", array( 'active' => 0 ), array( 'id' => $data['form_id'] ) );
		$success = true;

		wp_cache_flush();
	}

	header( 'Content-Type: application/json' );
	echo json_encode(['success' => $success, 'id' => $data['form_id']]);
}
add_action( 'bswp_webhook_form_deleted', 'bswp_webhook_form_deleted');

/**
 * Webhook handler: gdpr_update
 *
 * @return void
 */
function bswp_webhook_gdpr_updated() {
	global $wpdb;

	$data = json_decode( trim( file_get_contents( 'php://input' ) ), true );
	bswp_activity_log('gdpr', 'updated', $data);

	update_option( 'bswp_gdpr', $data );

	header( 'Content-Type: application/json' );
	echo json_encode(['success' => $success]);
}
add_action( 'bswp_webhook_gdpr_updated', 'bswp_webhook_gdpr_updated');