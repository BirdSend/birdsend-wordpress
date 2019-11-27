<?php

/**
 * BirdSend App URL
 *
 * @param string $path Additional path.
 *
 * @return string
 */
function bswp_app_url( $path = '' ) {
	return rtrim( get_option( 'bswp_app_url', BSWP_APP_URL ), '/' ) . '/' . $path;
}

/**
 * BirdSend API URL
 *
 * @param string $path Additional path.
 *
 * @return string
 */
function bswp_api_url( $path = '' ) {
	return rtrim( get_option( 'bswp_api_url', BSWP_API_URL ), '/' ) . '/' . $path;
}

/**
 * Connect using auth code
 *
 * @param int    $client_id BirdSend API application id.
 * @param string $client_secret BirdSend API application secret.
 * @param string $code BirdSend API authentication code.
 * @param string $scope BirdSend API application scope or permission.
 *
 * @return mixed
 */
function bswp_request_token( $client_id, $client_secret, $code, $scope = '' ) {
	$http = new GuzzleHttp\Client();
	try {
		$post_url = wp_http_validate_url( bswp_api_url( 'oauth/token' ) );
		$response = $http->post(
			$post_url,
			array(
				'form_params' => array(
					'grant_type'    => 'authorization_code',
					'client_id'     => absint( $client_id ),
					'client_secret' => sanitize_text_field( $client_secret ),
					'redirect_uri'  => admin_url( 'admin.php?page=bswp-settings&action=auth-site' ),
					'code'          => sanitize_text_field( $code ),
				),
			)
		);
		$response = json_decode( (string) $response->getBody(), true );
		update_option( 'bswp_token', sanitize_text_field( $response['access_token'] ) );

		return $response;
	} catch ( \Exception $e ) {
		if ( WP_DEBUG ) {
			wp_die( esc_attr( $e->getMessage() ) );
		} else {
			wp_redirect( admin_url( 'admin.php?page=bswp-settings&error=cant_connect' ) );
			exit;
		}
	}
}

/**
 * Get and verify token
 *
 * @return string|bool
 */
function bswp_token() {
	$token = get_option( 'bswp_token' );
	if ( ! $token ) {
		return false;
	}
	return is_array( $token ) ? $token['access_token'] : $token;
}

/**
 * Get pixel code
 *
 * @return string
 */
function bswp_pixel_code() {
	$code = get_option( 'bswp_pixel_code' );
	if ( ! $code ) {
		return bswp_get_pixel_code();
	}
	return esc_js( bswp_format_pixel_code( $code ) );
}

/**
 * Get pixel code
 *
 * @return string|bool
 */
function bswp_get_pixel_code() {
	$response = bswp_api_request( 'GET', 'pixels/code' );
	if ( $response ) {

		$replace = array( '<script>', '</script>' );
		$code    = $response['code'];
		$code    = trim( str_replace( $replace, '', $code ) );

		update_option( 'bswp_pixel_code', sanitize_text_field( $code ) );
		return esc_js( bswp_format_pixel_code( $code ) );
	}
	return false;
}

/**
 * Format pixel code
 *
 * @param string $code Pixel snippet code.
 *
 * @return string
 */
function bswp_format_pixel_code( $code ) {
	$replace = array( '<script>', '</script>' );
	return trim( str_replace( $replace, '', $code ) );
}

/**
 * API request
 *
 * @param string $method API request method.
 * @param string $path   API path.
 * @param array  $data   API request data.
 *
 * @return array|bool
 */
function bswp_api_request( $method, $path, $data = array() ) {
	$token = bswp_token();
	if ( ! $token ) {
		return false;
	}
	$http = new GuzzleHttp\Client();
	try {
		$options = array(
			'headers' => array(
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $token,
			),
		);

		if ( $data ) {
			if ( 'get' === strtolower( $method ) ) {
				$options['query'] = $data;
			} else {
				$options['json'] = $data;
			}
		}

		$api_url  = wp_http_validate_url( bswp_api_url( 'v1/' . $path ) );
		$response = $http->request( $method, $api_url, $options );
		$response = json_decode( (string) $response->getBody(), true );

		return $response;
	} catch ( \Exception $e ) {
		if ( WP_DEBUG ) {
			wp_die( esc_attr( $e->getMessage() ) );
		}
	}
	return false;
}

/**
 * Check if user's BirdSend account has connected.
 *
 * @return bool
 */
function bswp_is_enabled() {
	return ! ! bswp_token();
}

/**
 * Get Product Category
 *
 * @param array $args Product category arguments.
 *
 * @return array
 */
function bswp_wc_categories( $args = array() ) {
	$defaults = array(
		'taxonomy'     => 'product_cat',
		'child_of'     => 0,
		'parent'       => 0,
		'orderby'      => 'name',
		'show_count'   => 0, // 1 for yes, 0 for no.
		'pad_counts'   => 0, // 1 for yes, 0 for no.
		'hierarchical' => 1,
		'title_li'     => '',
		'hide_empty'   => 0,
	);
	$args     = array_merge( $defaults, $args );

	$data       = array();
	$categories = get_categories( $args );
	foreach ( $categories as $cat ) {
		$category_id = absint( $cat->term_id );

		$data[ $category_id ]['id']    = $category_id;
		$data[ $category_id ]['label'] = $cat->name;
		$data[ $category_id ]['slug']  = $cat->slug;

		$defaults['parent'] = $category_id; // Change parent id.
		$childs             = bswp_wc_categories( $defaults );
		if ( ! empty( $childs ) ) {
			$data[ $category_id ]['children'] = $childs;
		}
	}
	return $data;
}

/**
 * Reset Product Category
 *
 * @param  array $data Product category data.
 *
 * @return array
 */
function bswp_reset_array( $data ) {
	$data = array_values( $data );
	foreach ( $data as $key => $val ) {
		if ( isset( $val['children'] ) ) {
			$child                    = array_values( $val['children'] );
			$data[ $key ]['children'] = $child;
			foreach ( $child as $key2 => $val2 ) {
				if ( isset( $val2['children'] ) ) {
					$child2                                        = array_values( $val2['children'] );
					$data[ $key ]['children'][ $key2 ]['children'] = $child2;
				}
			}
		}
	}
	return $data;
}


/**
 * Get product category parent
 *
 * @param  int $category_id Category id.
 *
 * @return array
 */
function bwsp_get_parent( $category_id ) {
	$category_id = absint( $category_id );
	$parents     = array();
	$categories  = get_ancestors( $category_id, 'product_cat' );
	foreach ( $categories as $cat ) {
		array_push( $parents, $cat );
	}
	return $parents;
}

/**
 * Get client IP address
 *
 * @return string
 */
function bwsp_get_ipaddress() {
	if ( false !== getenv( 'HTTP_CLIENT_IP' ) ) {
		$ip = getenv( 'HTTP_CLIENT_IP' );
	} elseif ( false !== getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
		$ip = getenv( 'HTTP_X_FORWARDED_FOR' );
	} elseif ( false !== getenv( 'HTTP_X_FORWARDED' ) ) {
		$ip = getenv( 'HTTP_X_FORWARDED' );
	} elseif ( false !== getenv( 'HTTP_FORWARDED_FOR' ) ) {
		$ip = getenv( 'HTTP_FORWARDED_FOR' );
	} elseif ( false !== getenv( 'HTTP_FORWARDED' ) ) {
		$ip = getenv( 'HTTP_FORWARDED' );
	} else {
		$ip = ( false !== getenv( 'REMOTE_ADDR' ) ) ? getenv( 'REMOTE_ADDR' ) : '0.0.0.0';
	}
	$ip = ( filter_var( $ip, FILTER_VALIDATE_IP ) ) ? $ip : '0.0.0.0';
	return $ip;
}

