<?php

/**
 * Prepare form placements
 *
 * @param string $content Post content.
 *
 * @return string
 */
function bswp_prepare_form_placements( $content ) {
	if ( is_single() || is_page() ) {
		$content  = '<div data-birdsend-form-placement="before"></div>' . $content;
		$content .= '<div data-birdsend-form-placement="after"></div>';

		// Mark each paragraph with birdsend tag.
		$content = bswp_tag_paragraphs( $content );

		// in the middle of content.
		$content = bswp_insert_after_paragraph_index( '<div data-birdsend-form-placement="middle"></div>', 'middle', $content );
	}
	return $content;
}
add_filter( 'the_content', 'bswp_prepare_form_placements' );

/**
 * String replace once
 *
 * @param string $search Search for the string to be replace.
 * @param string $replace Replacement string.
 * @param string $string Full string that will be modified.
 *
 * @return string
 */
function bswp_str_replace_first( $search, $replace, $string ) {
	$pos = strpos( $string, $search );
	if ( false !== $pos ) {
		$string = substr_replace( $string, $replace, $pos, strlen( $search ) );
	}
	return $string;
}

/**
 * Tag paragraphs
 *
 * @param string $content Post content.
 *
 * @return string
 */
function bswp_tag_paragraphs( $content ) {
	preg_match_all( '/<p[^>]*>.*?<\/p>/i', $content, $matches );

	foreach ( $matches[0] as $index => $match ) {
		$tagged  = bswp_str_replace_first( '<p', '<p data-birdsend-par-index="' . $index . '"', $match );
		$content = bswp_str_replace_first( $match, $tagged, $content );
	}

	return $content;
}

/**
 * Insert content after paragraph index
 *
 * @param string $insert String that will be inserted.
 * @param int    $index The paragraph order number.
 * @param string $content Post content.
 *
 * @return string
 */
function bswp_insert_after_paragraph_index( $insert, $index, $content ) {
	$closing_p  = '</p>';
	$paragraphs = explode( $closing_p, $content );

	if ( 'middle' == $index ) {
		$index = floor( count( $paragraphs ) / 2 );
	}

	foreach ( $paragraphs as $_index => $paragraph ) {
		if ( trim( $paragraph ) ) {
			$paragraphs[ $_index ] .= $closing_p;
		}
		if ( $index == $_index + 1 ) {
			$paragraphs[ $_index ] .= $insert;
		}
	}

	return implode( '', $paragraphs );
}
