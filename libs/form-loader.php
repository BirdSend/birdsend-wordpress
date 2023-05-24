<?php

/**
 * Forms init
 *
 * @return void
 */
function bswp_forms_init() {
	if ( is_single() || is_page() ) {
		echo '<script>var _bswp = { formLoader: false, messageUrl: "' . add_query_arg( array( 'bswp_form_gdpr' => 1 ), home_url() ) . '" }; var _bswpForms = { ics: [], wgs: [], nics: [] };</script>';
	}
}
add_action( 'wp_head', 'bswp_forms_init' );

/**
 * Scan on click snippets
 *
 * @param string $content
 *
 * @return array|null
 */
function bswp_forms_scan_on_click_snippets( $content ) {
	$doc = new \DOMDocument;
	$doc->loadHTML( $content );

	$queries = array( 'data-birdsend-form' => '//a[@data-birdsend-form!=""]', 'data-birsend-form' => '//a[@data-birsend-form!=""]' );
	$ids = array();

	foreach ( $queries as $key => $query ) {
		$xpath = new \DOMXpath( $doc );
		$nodeList = $xpath->query( $query );

		for ( $i=0; $i<$nodeList->length; $i++ ) {
			$node = $nodeList->item( $i );
			$ids[] = (int) $node->getAttribute( $key );
		}
	}

	return array_unique( $ids );
}

/**
 * Prepare form placements
 *
 * @param string $content
 *
 * @return string
 */
function bswp_prepare_form_placements( $content ) {
	if ( is_single() || is_page() ) {
		wp_enqueue_script( 'bwsp-form', BSWP_JS . 'form.js', array(), BSWP_VERSION, true);

		$ocs = bswp_forms_scan_on_click_snippets( $content );
		$icForms = bswp_get_forms_on_current_page( true );

		// after every paragraph
		$content = bswp_forms_placed_after_paragraph_content( $icForms, $content );

		// in the middle of content
		$middle = bswp_forms_placed_middle_content( $icForms );
		$content = bswp_insert_after_paragraph_index( $middle, 'middle', $content );

		// before and after content
		$before = bswp_forms_placed_before_content( $icForms );
		$after = bswp_forms_placed_after_content( $icForms );

		$content = $before . $content . $after;

		// popup / welcome-screen forms
		if ( $nicForms = bswp_get_forms_on_current_page( false, $ocs ) ) {
			$others = bswp_forms_auto_trigger( $nicForms );
			$content = $content . $others;
		}
	}
	return $content;
}
add_filter( 'the_content', 'bswp_prepare_form_placements' );

/**
 * Forms placed on particular placement
 *
 * @param array  $forms
 * @param string $placement
 *
 * @return string
 */
function bswp_forms_placed_content( $forms, $placement ) {
	$forms = array_filter( $forms, function ($form) use ( $placement ) {
		return in_array( $placement, \BSWP\Helper::get( $form->triggers, 'placements.active', array() ) );
	} );

	$content = '';

	foreach ( $forms as $form ) {
		if ( $html = bswp_get_form_html( $form->id ) ) {
			foreach ( $html['css'] as $index => $src ) {
				bswp_enqueue_form_style( $src, $html['ver'] );
			}
			$content .= $html['html'];
			$content .= '<script>_bswpForms.ics.push(' . json_encode( \BSWP\Helper::except( $html, array( 'css', 'html' ) ) ) . ');</script>';
		}
	}

	return $content;
}

/**
 * Forms placed before content
 *
 * @param array $forms
 *
 * @return string
 */
function bswp_forms_placed_before_content( $forms ) {
	return bswp_forms_placed_content( $forms, 'placement-top-of-post' );
}

/**
 * Forms placed after content
 *
 * @param array $forms
 *
 * @return string
 */
function bswp_forms_placed_after_content( $forms ) {
	return bswp_forms_placed_content( $forms, 'placement-after-post' );
}

/**
 * Forms placed in the middle of content
 *
 * @param array $forms
 *
 * @return string
 */
function bswp_forms_placed_middle_content( $forms ) {
	return bswp_forms_placed_content( $forms, 'placement-middle-of-post' );
}

/**
 * Forms placed after paragraph content
 *
 * @param array  $forms
 * @param string $content
 *
 * @return string
 */
function bswp_forms_placed_after_paragraph_content( $forms, $content ) {
	$forms = array_filter( $forms, function ($form) {
		return in_array( 'placement-after-every-paragraph', \BSWP\Helper::get( $form->triggers, 'placements.active', array() ) );
	} );

	$paragraphs = explode( '</p>', $content );
	$count = max( 0, count( $paragraphs ) - 1 );

	foreach ( $forms as $form ) {
		$indexes = array();

		if ( $input = (int) \BSWP\Helper::get( $form->triggers, 'placements.inputs.placement-after-every-paragraph.value' ) ) {
			$index = $input;
			while ( $index <= $count ) {
				$indexes[] = $index;
				$index += $input;
			}
		}

		if ( $indexes && $html = bswp_get_form_html( $form->id ) ) {
			foreach ( $html['css'] as $index => $src ) {
				bswp_enqueue_form_style( $src, $html['ver'] );
			}

			foreach ( $indexes as $index ) {
				$content = bswp_insert_after_paragraph_index( $html['html'], $index, $content );
			}
		}
	}

	return $content;
}

/**
 * Insert content after paragraph index
 *
 * @param string $insert
 * @param int $index
 * @param string $content
 *
 * @return string
 */
function bswp_insert_after_paragraph_index( $insert, $index, $content ) {
	$closing_p = '</p>';
	$paragraphs = explode( $closing_p, $content );
	
	if ( $index == 'middle' ) {
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

/**
 * Forms auto trigger
 *
 * @param array $forms
 *
 * @return string
 */
function bswp_forms_auto_trigger( $forms ) {
	if ( ! count( $forms ) ) {
		return;
	}

	$data = array();

	foreach ( $forms as $form ) {
		if ( $html = bswp_get_form_html( $form->id ) ) {
			foreach ( $html['css'] as $index => $src ) {
				bswp_enqueue_form_style( $src, $html['ver'] );
			}
			$data[] = $html;
		}
	}

	$script = '<script>_bswpForms.nics = ' . json_encode( $data ) . ';</script>';
	return $content . $script;
}