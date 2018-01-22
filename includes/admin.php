<?php
/**
 * Hook BirdSend menu into WP admin sidebar
 * @return void
 */
function bswp_add_admin() {
	add_menu_page('BirdSend for WordPress', 'BirdSend for WP', 'manage_options', 'bswp-settings', 'bswp_settings');
	//add_submenu_page('bswp-settings', 'Settings', 'Settings', 'manage_options', 'bswp-settings', 'bswp_settings');
}
add_action('admin_menu', 'bswp_add_admin');

/**
 * Hook JS and CSS files into WP admin on some specific pages only
 * @param  string $hook Current page hook name
 * @return void
 */
function bswp_admin_scripts( $hook ) {
	$panels = array(
		'toplevel_page_bswp-settings',
	);
	
	if ( !in_array($hook, $panels) ) return;

	wp_register_script('materialize', '//cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/js/materialize.min.js', array('jquery'), '0.100.2', true);
	wp_enqueue_script('bwsp-admin', BSWP_JS . 'admin.js', array('materialize'), '1.0.0', true);
	wp_enqueue_style('material-icons', '//fonts.googleapis.com/icon?family=Material+Icons');
	wp_enqueue_style('materialize', '//cdnjs.cloudflare.com/ajax/libs/materialize/0.100.2/css/materialize.min.css');
	wp_enqueue_style('bswp-admin', BSWP_CSS . 'admin.css');
}
add_action('admin_enqueue_scripts', 'bswp_admin_scripts');

/**
 * Admin settings page
 */
function bswp_settings() {
	include_once('admin-settings.php');
}