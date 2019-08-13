<?php
/**
 * Hook BirdSend menu into WP admin sidebar
 * @return void
 */
function bswp_add_admin() {
	add_menu_page( 'BirdSend', 'BirdSend', 'manage_options', 'bswp-settings', 'bswp_settings', BSWP_URL . 'assets/img/birdsend-icon.svg', '30.183456' );
}
add_action( 'admin_menu', 'bswp_add_admin' );

/**
 * Hook JS and CSS files into WP admin on some specific pages only
 * @param  string $hook Current page hook name
 * @return void
 */
function bswp_admin_scripts( $hook ) {
	wp_enqueue_style( 'bswp-admin', BSWP_CSS . 'admin.css' );
	
	$panels = array(
		'toplevel_page_bswp-settings',
	);
	
	if ( !in_array($hook, $panels) ) return;

	wp_register_script( 'materialize', BSWP_JS . 'materialize.min.js', array( 'jquery' ), '1.0.0', true);
	wp_enqueue_script( 'bwsp-admin', BSWP_JS . 'admin.js', array( 'materialize' ), '1.0.0', true);
	wp_enqueue_style( 'material-icons', '//fonts.googleapis.com/icon?family=Material+Icons' );
	wp_enqueue_style( 'materialize', BSWP_CSS . 'materialize.min.css' );
}
add_action( 'admin_enqueue_scripts', 'bswp_admin_scripts' );

/**
 * Admin settings page
 */
function bswp_settings() {
	$action = '';
	
	if ( isset( $_GET[ 'action' ] ) ) {
		$action = $_GET[ 'action' ];
	}

	switch ( $action ) {
		case 'developer':
			include_once( 'admin-developer.php' );
			break;
		default:
			include_once( 'admin-settings.php' );
			break;
	}
}