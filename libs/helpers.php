<?php

/**
 * App URL
 *
 * @param string $path Path
 *
 * @return string
 */
function bswp_app_url( $path = '' ) {
	return esc_url( rtrim( get_option( 'bswp_app_url', BSWP_APP_URL ), '/' ) . '/' . $path );
}

/**
 * API URL
 *
 * @param string $path Path
 *
 * @return string
 */
function bswp_api_url( $path = '' ) {
	return esc_url( rtrim( get_option( 'bswp_api_url', BSWP_API_URL ), '/' ) . '/' . $path );
}

/**
 * Removed shortcodes
 *
 * @return string
 */
function bswp_removed_shortcodes() {
	return get_option( 'bswp_removed_shortcodes' );
}

/**
 * Connect using auth code
 *
 * @param string $code  Auth code
 * @param string $scope Scope
 *
 * @return array
 */
function bswp_request_token( $client_id, $client_secret, $code, $scope = '' ) {
	$http = new GuzzleHttp\Client;
	try {
		$response = $http->post( bswp_api_url( 'oauth/token' ), [
			'form_params' => [
				'grant_type' => 'authorization_code',
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri' => admin_url( 'admin.php?page=bswp-settings&action=auth-site' ),
				'code' => $code,
			]
		]);
		$response = json_decode( (string) $response->getBody(), true );
		update_option( 'bswp_token', sanitize_text_field( $response[ 'access_token' ] ) );
		update_option( 'bswp_refresh_token', sanitize_text_field( $response[ 'refresh_token' ] ) );

		return $response;
	} catch ( \Exception $e ) {
		if ( WP_DEBUG ) {
			echo $e->getMessage();
		} else {
			wp_redirect( admin_url( 'admin.php?page=bswp-settings&error=cant_connect' ) );
		}
		exit;
	}
}

/**
 * Get OAuth client credentials (site-level, with user-meta migration fallback)
 *
 * @return array|false
 */
function bswp_get_oauth_client() {
	if ( $client = get_option( 'bswp_client' ) ) {
		return $client;
	}

	$users = get_users( array(
		'meta_key' => 'bswp_client',
		'number'   => 1,
		'fields'   => 'ID',
	) );

	if ( $users ) {
		$client = get_user_meta( $users[0], 'bswp_client', true );
		if ( $client ) {
			update_option( 'bswp_client', $client );
			return $client;
		}
	}

	return false;
}

/**
 * Log when a renewal failure email was not sent
 *
 * @param string $reason
 * @param array  $properties
 *
 * @return void
 */
function bswp_log_renewal_failed_email_skipped( $reason, $properties = array() ) {
	bswp_activity_log(
		'renewal_failed_email_skipped',
		'Renewal failure email was not sent',
		array_merge( array( 'reason' => $reason ), $properties )
	);
}

/**
 * Send admin email when BirdSend connection renewal fails
 *
 * @param bool $disconnect_on_failure Whether the plugin disconnected after failure.
 *
 * @return void
 */
function bswp_send_renewal_failed_email( $disconnect_on_failure = false ) {
	if ( ! apply_filters( 'bswp_send_renewal_failed_email', true, $disconnect_on_failure ) ) {
		bswp_log_renewal_failed_email_skipped( 'disabled_by_filter' );
		return;
	}

	$last_sent = get_option( 'bswp_token_refresh_failed_email_at' );
	if ( $last_sent ) {
		$elapsed = time() - mysql2date( 'U', $last_sent, true );
		if ( $elapsed < DAY_IN_SECONDS ) {
			bswp_log_renewal_failed_email_skipped( 'throttled', array(
				'last_sent_at' => $last_sent,
				'retry_after_seconds' => DAY_IN_SECONDS - $elapsed,
			) );
			return;
		}
	}

	$to = get_option( 'admin_email' );
	if ( ! $to ) {
		bswp_log_renewal_failed_email_skipped( 'no_admin_email' );
		return;
	}

	$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$site_url = home_url();
	$settings_url = admin_url( 'admin.php?page=bswp-settings' );

	$subject = sprintf(
		'[%s] BirdSend connection could not be renewed',
		$site_name
	);

	if ( $disconnect_on_failure ) {
		$intro = 'BirdSend could not renew the connection to your WordPress site. The plugin has disconnected and forms or integrations may stop working until you connect again.';
	} else {
		$intro = 'BirdSend could not automatically renew the connection to your WordPress site. Your site may still show as connected, but forms and integrations may stop working.';
	}

	$message = "Hello,\n\n";
	$message .= $intro . "\n\n";
	$message .= "Site: {$site_name}\n";
	$message .= "URL: {$site_url}\n\n";
	$message .= "To restore the connection:\n";
	$message .= "1. Log in to your WordPress admin\n";
	$message .= "2. Open BirdSend\n";
	$message .= "3. Click \"Disconnect My BirdSend Account\"\n";
	$message .= "4. Connect your BirdSend account again\n\n";
	$message .= "BirdSend settings: {$settings_url}\n\n";
	$message .= "— BirdSend WordPress plugin\n";

	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	if ( wp_mail( $to, $subject, $message, $headers ) ) {
		update_option( 'bswp_token_refresh_failed_email_at', current_time( 'mysql', true ) );
		bswp_activity_log(
			'renewal_failed_email',
			'Admin email sent for failed connection renewal',
			array(
				'to' => $to,
				'subject' => $subject,
				'disconnected' => (bool) $disconnect_on_failure,
			)
		);
	} else {
		bswp_activity_log(
			'renewal_failed_email_failed',
			'Failed to send admin email for failed connection renewal',
			array(
				'to' => $to,
				'subject' => $subject,
				'disconnected' => (bool) $disconnect_on_failure,
			)
		);
	}
}

/**
 * Handle token refresh failure
 *
 * @param string $message
 * @param bool   $disconnect_on_failure
 *
 * @return false
 */
function bswp_handle_refresh_token_failure( $message, $disconnect_on_failure ) {
	if ( $disconnect_on_failure ) {
		update_option( 'bswp_token_expired', bswp_token() );
		delete_option( 'bswp_token' );
	} else {
		update_option( 'bswp_token_refresh_failed_at', current_time( 'mysql', true ) );
		bswp_activity_log( 'token_refresh_failed', 'Scheduled token refresh failed', array( 'message' => $message ) );
	}

	if ( $disconnect_on_failure ) {
		bswp_activity_log( 'token_refresh_failed', 'Connection renewal failed during API use', array( 'message' => $message ) );
	}

	bswp_send_renewal_failed_email( $disconnect_on_failure );

	if ( WP_DEBUG ) {
		error_log( $message );
	}

	return false;
}

/**
 * Refresh access token
 *
 * @param bool $disconnect_on_failure Whether to disconnect on refresh failure (reactive API calls).
 *
 * @return array|false
 */
function bswp_refresh_token( $disconnect_on_failure = true ) {
	if (! $refresh_token = get_option( 'bswp_refresh_token' ) ) {
		return false;
	}

	if (! $client = bswp_get_oauth_client() ) {
		return bswp_handle_refresh_token_failure( 'OAuth client credentials not found.', $disconnect_on_failure );
	}

	$http = new GuzzleHttp\Client;
	try {
		$response = $http->post( bswp_api_url( 'oauth/token' ), [
			'form_params' => [
				'grant_type' => 'refresh_token',
				'refresh_token' => $refresh_token,
				'client_id' => $client[ 'client_id' ],
				'client_secret' => $client[ 'client_secret' ],
				'scope' => 'write',
			]
		]);
		$response = json_decode( (string) $response->getBody(), true );
		update_option( 'bswp_token', sanitize_text_field( $response[ 'access_token' ] ) );
		update_option( 'bswp_refresh_token', sanitize_text_field( $response[ 'refresh_token' ] ) );
		update_option( 'bswp_token_refreshed_at', current_time( 'mysql', true ) );
		delete_option( 'bswp_token_refresh_failed_at' );
		delete_option( 'bswp_token_refresh_failed_email_at' );
		bswp_activity_log( 'token_refresh', 'Token refresh succeeded' );

		return $response;
	} catch ( GuzzleHttp\Exception\RequestException $e ) {
		if ( $e->hasResponse() && $e->getResponse()->getStatusCode() == 401 ) {
			return bswp_handle_refresh_token_failure( $e->getMessage(), $disconnect_on_failure );
		}

		return bswp_handle_refresh_token_failure( $e->getMessage(), $disconnect_on_failure );
	} catch ( \Exception $e ) {
		return bswp_handle_refresh_token_failure( $e->getMessage(), $disconnect_on_failure );
	}
}

/**
 * Get and verify token
 *
 * @return string|bool
 */
function bswp_token() {
	if (! $token = get_option( 'bswp_token' ) ) {
		return false;
	}
	return is_array( $token ) ? $token[ 'access_token' ] : $token;
}

/**
 * Get the site timezone label for display
 *
 * @param int|null $timestamp Unix timestamp in site timezone context.
 *
 * @return string
 */
function bswp_get_display_timezone_label( $timestamp = null ) {
	if ( null === $timestamp ) {
		$timestamp = time();
	}

	$tz_string = get_option( 'timezone_string' );
	if ( $tz_string ) {
		try {
			$tz = new DateTimeZone( $tz_string );
			$dt = new DateTime( '@' . $timestamp );
			$dt->setTimezone( $tz );
			return $dt->format( 'T' );
		} catch ( Exception $e ) {
			return str_replace( '_', ' ', $tz_string );
		}
	}

	$offset = (float) get_option( 'gmt_offset' );
	if ( 0.0 === $offset ) {
		return 'UTC';
	}

	$sign = $offset >= 0 ? '+' : '';
	return 'UTC' . $sign . rtrim( rtrim( number_format( $offset, 2, '.', '' ), '0' ), '.' );
}

/**
 * Format a UTC datetime for admin display with site timezone label
 *
 * @param string|null $datetime UTC datetime string from database.
 *
 * @return string
 */
function bswp_format_datetime( $datetime ) {
	if ( empty( $datetime ) ) {
		return '';
	}

	$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	$formatted = get_date_from_gmt( $datetime, $format );
	$timestamp = mysql2date( 'U', $datetime, true );
	$tz_label = bswp_get_display_timezone_label( $timestamp );

	return $formatted . ' (' . $tz_label . ')';
}

/**
 * Whether QA testing tools are enabled on the Developer page
 *
 * @return bool
 */
function bswp_qa_tools_enabled() {
	return true;
}

/**
 * Next scheduled connection renewal as a formatted datetime string
 *
 * @return string
 */
function bswp_get_next_token_refresh_datetime() {
	$timestamp = wp_next_scheduled( 'bswp_biweekly_token_refresh' );

	if ( ! $timestamp ) {
		return '';
	}

	return bswp_format_datetime( gmdate( 'Y-m-d H:i:s', $timestamp ) );
}

/**
 * Get pixel code
 *
 * @return string
 */
function bswp_pixel_code() {
	if (! $code = get_option( 'bswp_pixel_code' ) ) {
		return bswp_get_pixel_code();
	}
	return bswp_format_pixel_code( $code );
}

/**
 * Get pixel code
 *
 * @return string
 */
function bswp_get_pixel_code() {
	if ( $response = bswp_api_request( 'GET', 'pixels/code' ) ) {

		$replace = [ '<script>', '</script>' ];
		$code = $response[ 'code' ];
		$code = trim( str_replace( $replace, '', $code ) );

		update_option( 'bswp_pixel_code', sanitize_text_field( $code ) );
		return bswp_format_pixel_code( $code );
	}
	return false;
}

/**
 * Format pixel code
 *
 * @param string $code
 *
 * @return string
 */
function bswp_format_pixel_code( $code ) {
	$replace = [ '<script>', '</script>' ];
	return wp_kses( trim( str_replace( $replace, '', $code ) ), '' );
}

/**
 * API request
 *
 * @param string $method Method
 * @param string $path   Path
 * @param array  $data   Data
 *
 * @return array
 */
function bswp_api_request( $method, $path, $data = array(), $throwException = false ) {
	if (! $token = bswp_token() ) {
		return;
	}
	$http = new GuzzleHttp\Client;
	try {
		$options = array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $token,
				'User-Agent' => 'birdsend-wp'
			)
		);

		if ( $data ) {
			if ( 'get' == strtolower( $method ) ) {
				$options[ 'query' ] = $data;
			} else {
				$options[ 'json' ] = $data;
			}
		}
			
		$response = $http->request( $method, bswp_api_url( 'v1/' . $path ), $options);
		$response = json_decode( (string) $response->getBody(), true );

		return $response;
	} catch ( \Exception $e ) {
		if ($e->hasResponse() && $e->getResponse()->getStatusCode() == 401 && bswp_refresh_token()) {
			return bswp_api_request( $method, $path, $data );
		}

		if ( WP_DEBUG ) {
			echo $e->getMessage();
			error_log( $e->getMessage() );
		}

		if ($throwException) {
			throw $e;
		}
	}
	return false;
}

/**
 * Is enabled
 *
 * @return bool
 */
function bswp_is_enabled() {
    return bswp_is_connected();
}

/**
 * Is connected
 *
 * @return bool
 */
function bswp_is_connected() {
    return !! bswp_token();
}

/**
 * Get Product Category
 * 
 */

function bswp_wc_categories($args = array()) {
    $data         = array();
    $taxonomy     = 'product_cat';
    $orderby      = 'name';  
    $show_count   = 0;      // 1 for yes, 0 for no
    $pad_counts   = 0;      // 1 for yes, 0 for no
    $hierarchical = 1;      // 1 for yes, 0 for no  
    $title        = '';  
    $empty        = 0;

    if (empty($args)) {
        $args = array(
            'taxonomy'     => $taxonomy,
            'child_of'     => 0,
            'parent'       => 0,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty
        );
    }
    $all_categories = get_categories( $args );
    foreach ($all_categories as $cat) {
        $category_id = $cat->term_id;       
        $data[$category_id]['id']   = $category_id;
        $data[$category_id]['label'] = $cat->name; 
        $data[$category_id]['slug'] = $cat->slug;
        $args2 = array(
                'taxonomy'     => $taxonomy,
                'child_of'     => 0,
                'parent'       => $category_id,
                'orderby'      => $orderby,
                'show_count'   => $show_count,
                'pad_counts'   => $pad_counts,
                'hierarchical' => $hierarchical,
                'title_li'     => $title,
                'hide_empty'   => $empty
        );
        $childs = bswp_wc_categories( $args2 );
        if (!empty($childs)) {
            $data[$category_id]['children'] = $childs;
        }   
    }
    return $data;
}

/**
 * Reset Product Category
 * 
 */
function bswp_reset_array($data) {
    $data = array_values($data);
    foreach ($data as $key => $val) {
        if (isset($val['children'])) {
            $child = array_values($val['children']);
            $data[$key]['children'] = $child;
            foreach ($child as $key2 => $val2) {
                if (isset($val2['children'])) {
                    $child2 = array_values($val2['children']);
                    $data[$key]['children'][$key2]['children'] = $child2;
                }
            }        
        }
    }
    return $data;
}


function bwsp_get_parent($category_id) {

	$parents = array();
	$categories = get_ancestors( $category_id, 'product_cat' ); 
	foreach($categories as $cat) {
		array_push($parents, $cat);
	} 
	return $parents;
}

/**
 * Activity log
 *
 * @param string $name
 * @param string $description
 * @param array $properties
 * @param int $subject_id
 * @param string $subject_type
 *
 * @return mixed
 */
function bswp_activity_log($name = 'default', $description = null, $properties = array(), $subject_id = null, $subject_type = null) {
	global $wpdb;

	return $wpdb->insert(
		"{$wpdb->prefix}bswp_logs",
		array(
			'name' => $name,
			'description' => $description,
			'properties' => maybe_serialize($properties),
			'subject_id' => $subject_id,
			'subject_type' => $subject_type,
			'created_at' => current_time( 'Y-m-d H:i:s', true )
		)
	);
}