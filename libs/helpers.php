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
 * Refresh access token
 *
 * @return array
 */
function bswp_refresh_token() {
	if (! $refresh_token = get_option( 'bswp_refresh_token' ) ) {
		return false;
	}

	if (! $client = get_user_meta( get_current_user_id(), 'bswp_client', true) ) {
		return false;
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

		return $response;
	} catch ( \Exception $e ) {
		if ($e->hasResponse() && $e->getResponse()->getStatusCode() == 401) {
			// Backup the current token and disconnect
			update_option( 'bswp_token_expired', bswp_token() );
			delete_option( 'bswp_token' );
		}

		if ( WP_DEBUG ) {
			echo $e->getMessage();
			error_log( $e->getMessage() );
		}
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
				'Authorization' => 'Bearer ' . $token
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