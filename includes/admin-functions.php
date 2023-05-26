<?php

/**
 * Admin auth site (connect to BirdSend account)
 *
 * @return void
 */
function bswp_admin_auth_site() {
	if ( ! empty( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'bswp-settings' && ! empty( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'auth-site' ) {
		if ( ! empty( $_GET[ 'client_id' ] ) && ! empty( $_GET[ 'client_secret' ] ) ) {
			if ( empty( $_GET[ 'nonce' ] ) || ! wp_verify_nonce( $_GET[ 'nonce' ], 'birdsend-auth-site' ) ) {
				wp_redirect( 'admin.php?page=bswp-settings&msg=invalid_nonce' );
				exit;
			}

			$client_id = (int) $_GET[ 'client_id' ];
			$client_secret = sanitize_text_field( $_GET[ 'client_secret' ] );

			update_user_meta( get_current_user_id(), 'bswp_client', [ 'client_id' => $client_id, 'client_secret' => $client_secret ] );
			
			$query = http_build_query([
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri' => admin_url( 'admin.php?page=bswp-settings&action=auth-site' ),
				'response_type' => 'code',
				'scope' => 'write',
			] );
			
			wp_redirect(bswp_app_url( 'oauth/authorize' ).'?'.$query );
			exit;
		}

		if (! empty( $_GET[ 'code' ] ) ) {
			if (! $client = get_user_meta( get_current_user_id(), 'bswp_client', true) ) {
				wp_redirect( 'admin.php?page=bswp-settings&msg=invalid_client' );
				exit;
			}
			bswp_request_token( $client[ 'client_id' ], $client[ 'client_secret' ], $_GET[ 'code' ] );
			// Sync all forms
			bswp_forms_sync_all();
			wp_redirect( 'admin.php?page=bswp-settings&msg=connected' );
			exit;
		}
	}
}
add_action( 'admin_init', 'bswp_admin_auth_site' );

/**
 * Admin disconnect site
 *
 * @return void
 */
function bswp_admin_disconnect_site() {
	if (! empty( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'bswp-settings' && ! empty( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'disconnect-site' ) {
		if ( empty( $_GET[ 'nonce' ] ) || ! wp_verify_nonce( $_GET[ 'nonce' ], 'birdsend-disconnect-site' ) ) {
			wp_redirect( 'admin.php?page=bswp-settings&msg=invalid_nonce' );
			exit;
		}

		bswp_do_disconnect();
		wp_redirect( 'admin.php?page=bswp-settings&msg=disconnected' );
		exit;
	}
}
add_action( 'admin_init', 'bswp_admin_disconnect_site' );

/**
 * Admin notice
 *
 * @return void
 */
function bswp_admin_notice() {
	if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'bswp-settings' ) {
		if ( isset( $_GET[ 'error' ] ) && in_array( $_GET[ 'error' ], [ 'cant_connect', 'invalid_client' ] ) ) {
			echo '<div class="notice notice-error">';
			echo '<p>There was error connecting your BirdSend account. Please try again or contact us if the problem persists!</p>';
			echo '</div>';
		}

		if ( isset( $_GET[ 'msg' ] ) && $_GET[ 'msg' ] == 'invalid_nonce' ) {
			echo '<div class="notice notice-error">';
			echo '<p>Invalid request!</p>';
			echo '</div>';
		}

		if ( isset( $_GET[ 'msg' ] ) && $_GET[ 'msg' ] == 'options_updated' ) {
			echo '<div class="notice notice-success">';
			echo '<p>Success! BirdSend Pixel Settings has been updated.</p>';
			echo '</div>';
		}

		if ( isset( $_GET[ 'msg' ] ) && $_GET[ 'msg' ] == 'connected' ) {
			echo '<div class="notice notice-success">';
			echo '<p>Success! Your BirdSend account has been connected.</p>';
			echo '</div>';
		}

		if ( isset( $_GET[ 'msg' ] ) && $_GET[ 'msg' ] == 'disconnected' ) {
			echo '<div class="notice notice-success">';
			echo '<p>Success! Your BirdSend account has been disconnected.</p>';
			echo '</div>';
		}

		if ( isset( $_GET[ 'msg' ] ) && $_GET[ 'msg' ] == 'sync-all-scheduled' ) {
			echo '<div class="notice notice-success">';
			echo '<p>Your forms will be synced shortly.</p>';
			echo '</div>';
		}

		if ( isset( $_GET[ 'msg' ] ) && $_GET[ 'msg' ] == 'sync-form-success' ) {
			echo '<div class="notice notice-success">';
			echo '<p>Sync form successful.</p>';
			echo '</div>';
		}

		if ( isset( $_GET[ 'msg' ] ) && $_GET[ 'msg' ] == 'sync-form-error' ) {
			echo '<div class="notice notice-error">';
			echo '<p>Sync form error!</p>';
			echo '</div>';
		}
	}
}
add_action( 'admin_notices', 'bswp_admin_notice' );

/**
 * Disconnect birdsend token
 *
 * @return void
 */
function bswp_do_disconnect() {
	delete_option( 'bswp_token' );
	delete_option( 'bswp_pixel_code' );
	delete_user_meta( get_current_user_id(), 'bswp_client' );
}

/**
 * Admin form actions
 *
 * @return void
 */
function bswp_admin_form_actions() {
	if (! empty( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'bswp-settings' && ! empty( $_POST[ 'submit' ] ) ) {
		switch ( $_POST[ 'submit' ] ) {
			case 'developer':
				$app_url = wp_http_validate_url( $_POST[ 'bswp_app_url' ] );
				$api_url = wp_http_validate_url( $_POST[ 'bswp_api_url' ] );
				
				update_option( 'bswp_app_url', $app_url );
				update_option( 'bswp_api_url', $api_url );
				
				wp_redirect( 'admin.php?page=bswp-settings&action=developer' );
				exit;

			case 'shortcode-remover':
				$removed_shortcodes = sanitize_text_field( $_POST[ 'bswp_removed_shortcodes' ] );
				update_option( 'bswp_removed_shortcodes', $removed_shortcodes );

				wp_redirect( 'admin.php?page=bswp-settings&action=shortcode-remover' );
				exit;

			case 'sync-all':
				bswp_forms_sync_all();

				wp_redirect( 'admin.php?page=bswp-settings&action=forms&msg=sync-all-scheduled' );
				exit;

			case 'sync-form':
				$sync = bswp_forms_sync( $_POST['form_id'] );

				wp_redirect( 'admin.php?page=bswp-settings&action=forms&msg=sync-form-'.$sync );
				exit;
		}
	}
}
add_action( 'admin_init', 'bswp_admin_form_actions' );

// ------------------------------------------------------------------------------------------------------------------

add_action( 'wp_ajax_bswp_ajax_get_forms', 'bswp_ajax_get_forms' );

/**
 * API get forms
 *
 * @return array
 */
function bswp_ajax_get_forms() {
	echo json_encode( bswp_get_forms() );
	wp_die();
}

// ------------------------------------------------------------------------------------------------------------------

/**
 * Paginate forms data
 *
 * @param array $params
 *
 * @return array
 */
function bswp_paginate_forms( $params = array() ) {
	global $wpdb;

	$page = isset( $params['page'] ) ? $params['page'] : 1;
	$limit = isset( $params['per_page'] ) ? $params['per_page'] : 15;
	$offset = ($page - 1) * $limit;

	$path = admin_url( 'admin.php?page=bswp-settings&action=forms' );
	$conditions = 'WHERE active=1';

	if ( $search = isset( $params['search'] ) ? $params['search'] : '' ) {
		$path .= '&search=' . urlencode($search);
		$conditions .= ' AND `name` LIKE %s';
		$search = '%'.$wpdb->esc_like($search).'%';
	} else {
		$conditions .= ' AND 1=%d';
		$search = 1;
	}

	$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}bswp_forms {$conditions}" );
	$last_page = ceil( $total / $limit );

	$data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bswp_forms {$conditions} ORDER BY name LIMIT %d,%d", $search, $offset, $limit ) );

	$links = array(
		'first' => $path . '&' . http_build_query( array( 'p' => 1, 'pp' => $limit ) ),
        'last' => $path . '&' . http_build_query( array( 'p' => $last_page, 'pp' => $limit ) ),
        'prev' => $page > 1 ? $path . '&' . http_build_query( array( 'p' => $page - 1, 'pp' => $limit ) ) : null,
        'next' => $page < $last_page ? $path . '&' . http_build_query( array( 'p' => $page + 1, 'pp' => $limit ) ) : null
	);

	$meta = array(
		'current_page' => $page,
        'from' => $offset + 1,
        'last_page' => $last_page,
        'path' => $path,
        'per_page' => $limit,
        'to' => $offset + $limit,
        'total' => $total,
	);

	return compact( 'data', 'links', 'meta' );
}

/**
 * Generate pagination html
 *
 * @param array $pagination
 * @param int $range
 *
 * @return string
 */
function bswp_pagination_html( $pagination, $range = 5 ) {
	$meta = $pagination['meta'];
	$links = $pagination['links'];
	
	$first_page = max( 1, $meta['current_page'] - $range );
	$last_page = min( $meta['last_page'], $meta['current_page'] + $range );

	$pages = array();
	for ( $i = $first_page; $i <= $last_page; $i++ ) {
		$pages[ $i ] = $meta['path'] . '&' . http_build_query( array( 'p' => $i, 'pp' => $meta['per_page'] ) );
	}

	$pages_html = '';
	foreach ($pages as $page => $url) {
		$pages_html .= '<li class="' . ( $meta['current_page'] == $page ? 'active yellow darken-1' : '' ) . '"><a href="' . $url . '">' . $page . '</a></li>';
	}

	return '<ul class="pagination">'.
			'<li><a href="' . $links['first'] . '">First</a></li>'.
			'<li class="' . ( ! $links['prev'] ? 'disabled' : '' ) . '"><a href="' . ( $links['prev'] ?: '#!' ) . '"><i class="material-icons">chevron_left</i></a></li>'.
			$pages_html.
			'<li class="' . ( ! $links['next'] ? 'disabled' : '' ) . '"><a href="' . ( $links['next'] ?: '#!' ) . '"><i class="material-icons">chevron_right</i></a></li>'.
			'<li><a href="' . $links['last'] . '">Last</a></li>'.
		'</ul>';
}