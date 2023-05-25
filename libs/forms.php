<?php

/**
 * Request to sync all forms
 *
 * @return void
 */
function bswp_forms_sync_all() {
	if ( ! get_option('bswp_forms_sync_all') ) {
		update_option( 'bswp_forms_sync_all', 1 );
	}
}

/**
 * Execute sync all forms
 *
 * @return void
 */
function bswp_forms_sync_all_exec() {
	if ( get_option( 'bswp_forms_sync_all' ) == 1 ) {
		update_option( 'bswp_forms_sync_all', 9 );

		bswp_forms_sync_page();

		update_option( 'bswp_forms_sync_all', 0 );
	}
}

/**
 * Get forms via api
 *
 * @param bool $cached
 *
 * @return array
 */
function bswp_get_forms( $columns = array( '*' ), $cached = true ) {
	global $wpdb;

	if ( in_array( '*', $columns ) ) {
		$columns = '*';
	} else {
		$columns = implode(',', $columns);
	}

	$key = 'forms_' . md5( $columns );

	if ($cached && $forms = wp_cache_get( $key, 'bswp' )) {
		return $forms;
	}

	$forms = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}bswp_forms WHERE active = 1 ORDER BY name LIMIT 100" );
	wp_cache_add( $key, $forms, 'bswp', 600 );

	return $forms;
}

/**
 * Get form
 *
 * @param int   $id
 * @param array $columns
 * @param bool  $cached
 *
 * @return mixed
 */
function bswp_get_form( $id, $columns = array( '*' ), $cached = false ) {
	global $wpdb;

	if ( in_array( '*', $columns ) ) {
		$columns = '*';
	} else {
		$columns = implode(',', $columns);
	}

	$key = "form_{$id}_" . md5( $columns );

	if ($cached && $forms = wp_cache_get( $key, 'bswp' )) {
		return $forms;
	}

	$form = $wpdb->get_row( "SELECT {$columns} FROM {$wpdb->prefix}bswp_forms WHERE id = {$id}" );
	wp_cache_add( $key, $form, 'bswp', 600 );

	return $form;
}

/**
 * Get form html
 *
 * @param int     $id
 * @param boolean $widget
 *
 * @return array
 */
function bswp_get_form_html( $id, $widget = false ) {
	global $wpdb;

	$column = $widget ? 'wg_html' : 'raw_html';

	if (! $form = \BSWP\Models\Form::find( $id ) ) {
		return;
	}

	$html = $form->{$column};

	if (! $html && $render = bswp_api_request( 'GET', 'wp/forms/' . $id . '/render' . ( $widget ? '/widget' : '' ) )) {
		$render['ver'] = $form->version;

		$wpdb->update(
			"{$wpdb->prefix}bswp_forms",
			array(
				$column => maybe_serialize( $render )
			),
			array( 'id' => $id )
		);

		wp_cache_flush();
		$html = $render;
	}

	if ( $form->type == 'in-content' && ! $widget ) {
		$html['html'] = '<div class="bs-in-content-form" style="display: none !important;">' . $html['html'] . '</div>';
	}

	return $html;
}

/**
 * Enqueue form style
 *
 * @param string $src
 * @param string $ver
 *
 * @return void
 */
function bswp_enqueue_form_style( $src, $ver = null )
{
	wp_enqueue_style( 'bswp-form-style-' . md5( $src ), $src, false, $ver );
}

/**
 * Sync forms per page
 *
 * @param integer $page
 * @param boolean $single
 * @param array   $ids
 *
 * @return void
 */
function bswp_forms_sync_page($page = 1, $single = false, $ids = array() ) {
	global $wpdb;

	if ( $response = bswp_api_request( 'GET', 'wp/forms', [ 'per_page' => 100, 'page' => $page ] ) ) {
		if ( ! empty( $response['data'] ) ) {
			foreach ( $response['data'] as $row ) {
				$query = "INSERT INTO {$wpdb->prefix}bswp_forms
					( id, name, active, type, triggers, placements_count, updated_at, raw_html, wg_html, version, last_sync_at, stats_displays_original, stats_submissions_original )
					VALUES ( %d, %s, %d, %s, %s, %d, %s, NULL, NULL, %s, UTC_TIMESTAMP, %d, %d )
					ON DUPLICATE KEY UPDATE name=VALUES(name), active=VALUES(active), type=VALUES(type), triggers=VALUES(triggers), placements_count=VALUES(placements_count), updated_at=VALUES(updated_at), version=VALUES(version), stats_displays_original=VALUES(stats_displays_original), stats_submissions_original=VALUES(stats_submissions_original)";

				$wpdb->query( $wpdb->prepare( $query, $row['form_id'], $row['name'], $row['active'], $row['type'], json_encode( $row['triggers'] ), $row['placements_count'], $row['updated_at'], $row['version'], $row['stats']['displays'], $row['stats']['submissions'] ) );

				$ids[] = $row['form_id'];
			}

			// If not single then continue to the next page.
			if (! $single && $response['meta']['current_page'] < $response['meta']['last_page']) {
				return bswp_forms_sync_page($response['meta']['current_page'] + 1, $single, $ids);
			}

			if (! $single) {
				// Delete forms not in the sync since they have probably been deleted.
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}bswp_forms SET active = 0 WHERE id NOT IN (".implode(',', $ids).") AND id <> %d", 0 ) );
			}
		}
	}
}

/**
 * Sync single form
 *
 * @param int $id
 *
 * @return string
 */
function bswp_forms_sync( $id ) {
	global $wpdb;

	if (! $form = bswp_get_form( $id ) ) {
		return 'error';
	}

	try {
		if ( $response = bswp_api_request( 'GET', 'wp/forms/' . $id, array(), true ) ) {
			$wpdb->update(
				"{$wpdb->prefix}bswp_forms",
				array(
					'name' => $response['name'],
					'type' => $response['type'],
					'active' => 1,
					'triggers' => json_encode( $response['triggers'] ),
					'placements_count' => $response['placements_count'],
					'updated_at' => $response['updated_at'],
					'raw_html' => null,
					'wg_html' => null,
					'version' => $response['version'],
					'last_sync_at' => current_time( 'Y-m-d H:i:s', true ),
					'stats_displays_original' => $response['stats']['displays'],
					'stats_submissions_original' => $response['stats']['submissions']
				),
				array( 'id' => $id )
			);
			return 'success';
		}
	} catch (\Exception $e) {
		if ( $e->getResponse()->getStatusCode() == 404 ) {
			$wpdb->update(
				"{$wpdb->prefix}bswp_forms",
				array( 'active' => 0 ),
				array( 'id' => $id )
			);
			return 'success';
		}
	}

	return 'error';
}

/**
 * Get forms on current page
 *
 * @return array
 */
function bswp_get_forms_on_current_page( $placement = false, $ids = array(), $cached = true ) {
	global $wpdb;

	$page_profile = \BSWP\Models\Form::getCurrentPageProfile();
	$key = "forms_" . md5( json_encode( array( 'u' => $page_profile['url'], 'p' => $placement, 'i' => $ids ) ) );

	if ($cached && $forms = wp_cache_get( $key, 'bswp' )) {
		return $forms;
	}

	$conditions = 'active = 1 AND type <> "in-content"';
	if ( $placement ) {
		$conditions = 'active = 1 AND type = "in-content" AND placements_count > 0';
	}

	if ( $ids ) {
		$conditions = '(' . $conditions . ') OR id IN (' . implode( ',', $ids ) . ')';
	}

	$forms = $wpdb->get_results(
		$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bswp_forms WHERE triggers IS NOT NULL AND {$conditions} AND id <> %d ORDER BY updated_at DESC", 0 )
	);

	$forms = array_map( function ($form) {
		return new \BSWP\Models\Form( $form );
	}, $forms );

	$types = array();
	$forms = array_filter( $forms, function ($form) use ($page_profile, $ids, &$types) {
		if ( $eligible = in_array( $form->id, $ids ) 
			|| ( $form->isEligible( $page_profile ) && ( $form->allowMultiple() || ! in_array( $form->type, $types ) ) )
		) {
			$types[] = $form->type;
		}
		return $eligible;
	} );

	wp_cache_add( $key, $forms, 'bswp', 600 );
	return $forms;
}

/**
 * Update display stats
 *
 * @param int $id
 *
 * @return void
 */
function bswp_form_update_display_stats( $id ) {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}bswp_forms SET stats_displays = stats_displays + 1 WHERE id = %d AND active = 1", $id ) );
}

/**
 * Check or drop form display cookie
 *
 * @param int $id
 *
 * @return bool
 */
function bswp_form_display_cookie( $id ) {
	$name = 'bswp-display-'.$id;

	if ( isset( $_COOKIE[$name] ) ) {
		return true;
	}

	// set a cookie for 30 days
	setcookie( $name, true, time() + ( 60*60*24*30 ) );
	return false;
}

/**
 * Form display stats pixel
 *
 * @return void
 */
function bswp_form_display_stats_pixel() {
	if ( ! empty( $_GET['bswp_form_display_stats_pixel'] ) && ! empty( $_GET['id'] ) ) {
		$id = $_GET['id'];

		if ( bswp_get_form( $id ) ) {
			if ( ! bswp_form_display_cookie( $id ) ) {
				bswp_form_update_display_stats( $id );
			}

        	$transparent1x1Png = '89504e470d0a1a0a0000000d494844520000000100000001010300000025db56ca00000003504c544500000'.
            	'0a77a3dda0000000174524e530040e6d8660000000a4944415408d76360000000020001e221bc330000000049454e44ae426082';

			header( 'Content-Type: image/png' );
			echo hex2bin( $transparent1x1Png );

			exit;
		}
	}
}
add_action( 'template_redirect', 'bswp_form_display_stats_pixel', 2 );

/**
 * Update submission stats
 *
 * @param int $id
 *
 * @return void
 */
function bswp_form_update_submission_stats( $id ) {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}bswp_forms SET stats_submissions = stats_submissions + 1 WHERE id = %d AND active = 1", $id ) );
}

/**
 * Form submission stats
 *
 * @return void
 */
function bswp_form_submission_stats() {
	if ( ! empty( $_GET['bswp_form_submission_stats'] ) && ! empty( $_GET['id'] ) ) {
		$id = $_GET['id'];

		if ( bswp_get_form( $id ) ) {
			bswp_form_update_submission_stats( $id );
			echo 'OK';
			exit;
		}
	}
}
add_action( 'template_redirect', 'bswp_form_submission_stats', 2 );

/**
 * Form GDPR message
 *
 * @return void
 */
function bswp_form_gdpr() {
	if ( ! empty( $_GET['bswp_form_gdpr'] ) ) {
		$gdpr = get_option( 'bswp_gdpr' );

		if (! $gdpr && $gdpr = bswp_api_request( 'GET', 'wp/forms/gdpr' ) ) {
			update_option( 'bswp_gdpr', $gdpr );
		}

		header( 'Content-Type: application/json' );
		echo json_encode( $gdpr );
		exit;
	}
}
add_action( 'template_redirect', 'bswp_form_gdpr', 2 );