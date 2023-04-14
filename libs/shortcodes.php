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

	if ( ( $id = $attr['form'] ) && $html = bswp_get_form_html( $id ) ) {
		foreach ( $html['css'] as $index => $src ) {
			bswp_enqueue_form_style( $src, $html['ver'] );
		}
		return $html['html'];
	}
}

add_shortcode( 'birdsend', 'bswp_show_form_shortcode' );

/**
 * Show removed shortcode
 *
 * @return string
 */
function bswp_show_removed_shortcode()
{
	return '<!-- bs removed shortcode -->';
}

/**
 * Overwrite shortcodes
 *
 * @return void
 */
function bswp_overwrite_removed_shortcodes() {
	$removed_shortcodes = bswp_removed_shortcodes();

	if ( $removed_shortcodes = array_filter( explode( ',', $removed_shortcodes ) ) ) {
		foreach ( $removed_shortcodes as $shortcode ) {
			$shortcode = trim( $shortcode );
			remove_shortcode( $shortcode );
    		add_shortcode( $shortcode, 'bswp_show_removed_shortcode' );
		}
	}
}

add_action( 'wp_loaded', 'bswp_overwrite_removed_shortcodes' );