<?php

/**
 * Admin auth site
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
			delete_user_meta( get_current_user_id(), 'bswp_client' );
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
			echo '<p>Invalid request!.</p>';
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
	$params = [ 'keyword' => 'active:1;rich:1', 'order_by' => 'name', 'sort' => 'asc', 'per_page' => 100 ];
	$response = [];
	if ( $forms = bswp_api_request( 'GET', 'forms', $params ) ) {
		$response = $forms[ 'data' ];
	}
	echo json_encode( $response );
	wp_die();
}