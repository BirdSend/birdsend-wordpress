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

	$forms = $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}bswp_forms ORDER BY name LIMIT 100" );
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

	if (! $form = bswp_get_form( $id, array( 'id', 'name', 'version', $column ) ) ) {
		return;
	}

	$html = maybe_unserialize( $widget->{$column} );

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
		return $render;
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
					( id, name, triggers, updated_at, raw_html, wg_html, version, last_sync_at, stats_displays_original, stats_submissions_original )
					VALUES ( %d, %s, %s, %s, NULL, NULL, %s, UTC_TIMESTAMP, %d, %d )
					ON DUPLICATE KEY UPDATE name=name, triggers=triggers, updated_at=updated_at, version=version, stats_displays_original=stats_displays_original, stats_submissions_original=stats_submissions_original";

				$wpdb->query( $wpdb->prepare( $query, $row['form_id'], $row['name'], json_encode( $row['triggers'] ), $row['updated_at'], $row['version'], $row['stats']['displays'], $row['stats']['submissions'] ) );

				$ids[] = $row['form_id'];
			}

			// If not single then continue to the next page.
			if (! $single && $response['meta']['current_page'] < $response['meta']['last_page']) {
				return bswp_forms_sync_page($response['meta']['current_page'] + 1, $single, $ids);
			}

			if (! $single) {
				// Delete forms not in the sync since they have probably been deleted.
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bswp_forms WHERE id NOT IN (".implode(',', $ids).")" ) );
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
					'triggers' => json_encode( $response['triggers'] ),
					'updated_at' => $response['updated_at'],
					'raw_html' => null,
					'wg_html' => null,
					'version' => $response['version'],
					'last_sync_at' => current_time( 'Y-m-d H:i:s' ),
					'stats_displays_original' => $response['stats']['displays'],
					'stats_submissions_original' => $response['stats']['submissions']
				),
				array( 'id' => $id )
			);
			return 'success';
		}
	} catch (\Exception $e) {
		if ( $e->getResponse()->getStatusCode() == 404 ) {
			$wpdb->delete( "{$wpdb->prefix}bswp_forms", array( 'id' => $id ) );
			return 'success';
		}
	}

	return 'error';
}