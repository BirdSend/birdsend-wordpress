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
			$client = array( 'client_id' => $client_id, 'client_secret' => $client_secret );

			update_user_meta( get_current_user_id(), 'bswp_client', $client );
			update_option( 'bswp_client', $client );
			
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
			bswp_schedule_token_refresh();
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

		if ( isset( $_GET[ 'action' ] ) && $_GET[ 'action' ] == 'developer' && isset( $_GET[ 'msg' ] ) ) {
			switch ( $_GET[ 'msg' ] ) {
				case 'qa-renewal-success':
					echo '<div class="notice notice-success is-dismissible"><p>QA: Connection renewal completed successfully. Check the Activity Log below for a <code>token_refresh</code> entry.</p></div>';
					break;
				case 'qa-renewal-failed':
					echo '<div class="notice notice-error is-dismissible"><p>QA: Connection renewal failed. The site should still show as connected. Check the Activity Log for a <code>token_refresh_failed</code> entry and look for the warning notice on wp-admin pages.</p></div>';
					break;
				case 'qa-preview-notice':
					echo '<div class="notice notice-success is-dismissible"><p>QA: Simulated warning notice enabled. Open any wp-admin page to review the yellow BirdSend warning. Your connection is not broken.</p></div>';
					break;
				case 'qa-clear-notice':
					echo '<div class="notice notice-success is-dismissible"><p>QA: Warning notice cleared.</p></div>';
					break;
				case 'support-reset-sync':
					echo '<div class="notice notice-success is-dismissible"><p>Sync status reset. The customer can use <strong>Sync all forms</strong> on the Forms page again.</p></div>';
					break;
				case 'support-sync-success':
					echo '<div class="notice notice-success is-dismissible"><p>Forms sync completed successfully. Ask the customer to refresh the Forms page and confirm their forms look correct.</p></div>';
					break;
				case 'support-sync-failed':
					echo '<div class="notice notice-error is-dismissible"><p>Forms sync could not reach BirdSend or returned no data. Confirm the site is connected, then try again. Escalate to the tech team if the problem continues.</p></div>';
					break;
			}
		}
	}
}
add_action( 'admin_notices', 'bswp_admin_notice' );

/**
 * Dismiss token refresh failure notice
 *
 * @return void
 */
function bswp_dismiss_token_refresh_notice() {
	if ( empty( $_GET['action'] ) || $_GET['action'] !== 'bswp_dismiss_token_refresh_notice' ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'birdsend-dismiss-token-refresh' ) ) {
		wp_die( 'Invalid request.' );
	}

	delete_option( 'bswp_token_refresh_failed_at' );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_init', 'bswp_dismiss_token_refresh_notice' );

/**
 * Admin notice for scheduled token refresh failure
 *
 * @return void
 */
function bswp_token_refresh_failed_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! get_option( 'bswp_token_refresh_failed_at' ) ) {
		return;
	}

	$dismiss_url = wp_nonce_url(
		admin_url( 'admin.php?action=bswp_dismiss_token_refresh_notice' ),
		'birdsend-dismiss-token-refresh',
		'nonce'
	);

	$settings_url = admin_url( 'admin.php?page=bswp-settings' );

	echo '<div class="notice notice-warning is-dismissible">';
	echo '<p><strong>BirdSend:</strong> We could not automatically renew your connection to BirdSend. Your WordPress site may still show as connected, but forms and integrations may stop working.<br>';
	echo 'To restore the connection, open BirdSend in your WordPress admin, click "Disconnect My BirdSend Account", then connect your BirdSend account again. ';
	echo '<a href="' . esc_url( $settings_url ) . '">Go to BirdSend</a> ';
	echo '| <a href="' . esc_url( $dismiss_url ) . '">Dismiss this notice</a></p>';
	echo '</div>';
}
add_action( 'admin_notices', 'bswp_token_refresh_failed_notice' );

/**
 * Disconnect birdsend token
 *
 * @return void
 */
function bswp_do_disconnect() {
	wp_clear_scheduled_hook( 'bswp_biweekly_token_refresh' );
	delete_option( 'bswp_token' );
	delete_option( 'bswp_refresh_token' );
	delete_option( 'bswp_pixel_code' );
	delete_option( 'bswp_client' );
	delete_option( 'bswp_token_refreshed_at' );
	delete_option( 'bswp_token_refresh_failed_at' );
	delete_option( 'bswp_token_refresh_failed_email_at' );
	delete_user_meta( get_current_user_id(), 'bswp_client' );
}

/**
 * Admin form actions
 *
 * @return void
 */
function bswp_admin_form_actions() {
	if (! empty( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'bswp-settings' && ! empty( $_POST[ 'submit' ] ) ) {
		$developer_url = admin_url( 'admin.php?page=bswp-settings&action=developer' );

		switch ( $_POST[ 'submit' ] ) {
			case 'qa-run-renewal':
			case 'qa-preview-notice':
			case 'qa-clear-notice':
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( 'QA tools are not available.' );
				}

				if ( empty( $_POST['bswp_qa_nonce'] ) || ! wp_verify_nonce( $_POST['bswp_qa_nonce'], 'bswp-qa-tools' ) ) {
					wp_redirect( $developer_url . '&msg=invalid_nonce' );
					exit;
				}

				if ( $_POST['submit'] === 'qa-run-renewal' ) {
					$result = bswp_refresh_token( false );
					wp_redirect( $developer_url . '&msg=' . ( $result ? 'qa-renewal-success' : 'qa-renewal-failed' ) );
					exit;
				}

				if ( $_POST['submit'] === 'qa-preview-notice' ) {
					update_option( 'bswp_token_refresh_failed_at', current_time( 'mysql', true ) );
					bswp_activity_log( 'token_refresh_failed', 'Simulated warning notice (QA test)', array( 'preview' => true ) );
					wp_redirect( $developer_url . '&msg=qa-preview-notice' );
					exit;
				}

				delete_option( 'bswp_token_refresh_failed_at' );
				wp_redirect( $developer_url . '&msg=qa-clear-notice' );
				exit;

			case 'support-reset-sync':
			case 'support-run-sync':
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_die( 'Support tools are not available.' );
				}

				if ( empty( $_POST['bswp_support_nonce'] ) || ! wp_verify_nonce( $_POST['bswp_support_nonce'], 'bswp-support-tools' ) ) {
					wp_redirect( $developer_url . '&msg=invalid_nonce' );
					exit;
				}

				if ( $_POST['submit'] === 'support-reset-sync' ) {
					bswp_forms_sync_all_reset();
					wp_redirect( $developer_url . '&msg=support-reset-sync' );
					exit;
				}

				$result = bswp_forms_sync_all_immediate();
				wp_redirect( $developer_url . '&msg=' . ( $result ? 'support-sync-success' : 'support-sync-failed' ) );
				exit;

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

	$total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}bswp_forms {$conditions}", $search ) );
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
 * Paginate activity log data
 *
 * @param array $params
 *
 * @return array
 */
function bswp_paginate_logs( $params = array() ) {
	global $wpdb;

	$page = max( 1, absint( isset( $params['page'] ) ? $params['page'] : 1 ) );
	$limit = max( 1, min( 100, absint( isset( $params['per_page'] ) ? $params['per_page'] : 20 ) ) );
	$offset = ( $page - 1 ) * $limit;

	$path = admin_url( 'admin.php?page=bswp-settings&action=developer' );
	$table = "{$wpdb->prefix}bswp_logs";
	$where = 'WHERE 1=1';
	$prepare_args = array();

	if ( ! empty( $params['name'] ) ) {
		$name = sanitize_text_field( $params['name'] );
		$path .= '&name=' . urlencode( $name );
		$where .= ' AND `name` = %s';
		$prepare_args[] = $name;
	}

	$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
	$total = $prepare_args
		? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $prepare_args ) )
		: (int) $wpdb->get_var( $count_sql );

	$last_page = max( 1, (int) ceil( $total / $limit ) );
	if ( $page > $last_page ) {
		$page = $last_page;
		$offset = ( $page - 1 ) * $limit;
	}

	$data_sql = "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d, %d";
	$query_args = array_merge( $prepare_args, array( $offset, $limit ) );
	$data = $wpdb->get_results( $wpdb->prepare( $data_sql, $query_args ) );

	$links = array(
		'first' => $path . '&' . http_build_query( array( 'p' => 1, 'pp' => $limit ) ),
		'last' => $path . '&' . http_build_query( array( 'p' => $last_page, 'pp' => $limit ) ),
		'prev' => $page > 1 ? $path . '&' . http_build_query( array( 'p' => $page - 1, 'pp' => $limit ) ) : null,
		'next' => $page < $last_page ? $path . '&' . http_build_query( array( 'p' => $page + 1, 'pp' => $limit ) ) : null,
	);

	$meta = array(
		'current_page' => $page,
		'from' => $total ? $offset + 1 : 0,
		'last_page' => $last_page,
		'path' => $path,
		'per_page' => $limit,
		'to' => min( $offset + $limit, $total ),
		'total' => $total,
	);

	return compact( 'data', 'links', 'meta' );
}

/**
 * Get full activity log properties as plain text
 *
 * @param string|null $properties
 *
 * @return string
 */
function bswp_get_activity_log_properties_text( $properties ) {
	if ( empty( $properties ) ) {
		return '';
	}

	$data = maybe_unserialize( $properties );
	if ( ! is_array( $data ) ) {
		return (string) $properties;
	}

	$formatted = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );

	return $formatted ? $formatted : '';
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