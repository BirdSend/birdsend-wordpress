<?php

/**
 * Show form shortcode
 *
 * @param array $attr Shortcode attribute.
 *
 * @return string
 */
function bswp_show_form_shortcode( $attr ) {
	$default = array( 'form' => '' );

	if ( is_array( $attr ) ) {
		$attr = array_merge( $default, $attr );
	} else {
		$attr = $default;
	}

	return '<div data-birdsend-form="' . $attr['form'] . '"></div>';
}

add_shortcode( 'birdsend', 'bswp_show_form_shortcode' );
