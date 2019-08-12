<?php

/**
 * Main webhook handler
 *
 * @return void
 */
function bswp_webhook_handler() {
	if ( ! empty( $_GET[ 'bswp_webhook' ] ) ) {
		do_action( 'bswp_webhook_' . $_GET[ 'bswp_webhook' ] );
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
	$meta = isset( $_GET[ 'bswp_meta_keys' ] ) ? $_GET[ 'bswp_meta_keys' ] : [];

	if ( in_array( 'post-types', $meta ) ) {
		$post_types_args = [ 'public' => true ];
		$data[ 'post_types' ] = array_values( array_map( function ( $type ) {
			return [
				'name' => $type->name,
				'label' => $type->label,
			];
		}, get_post_types( $post_types_args, 'objects' ) ) );
	}

	if (in_array('post-tags', $meta)) {
		$post_tags_args = [ 'hide_empty' => false ];
		$data[ 'post_tags' ] = array_map( function ( $tag ) {
			return [
				'slug' => $tag->slug,
				'name' => $tag->name,
			];
		}, get_tags( $post_tags_args));
	}

	if (in_array('post-categories', $meta)) {
		$post_categories_args = [];
		$data[ 'post_categories' ] = array_map( function ( $category ) {
			return [
				'slug' => $category->slug,
				'name' => $category->name,
			];
		}, get_categories( $post_categories_args ) );
	}

	header( 'Content-Type: application/json' );
	echo json_encode( $data );
}
add_action( 'bswp_webhook_get_metadata', 'bswp_webhook_get_metadata' );