<?php

/**
 * Show form shortcode
 *
 * @param array $attr
 *
 * @return string
 */
function bswp_show_form_shortcode( $attr ) {
	$default = [ 'form' => '' ];

	if ( is_array( $attr ) ) {
		$attr = array_merge( $default, $attr );
	} else {
		$attr = $default;
	}

	return '<div data-birdsend-form="' . $attr[ 'form' ] . '"></div>';
}

add_shortcode( 'birdsend', 'bswp_show_form_shortcode' );