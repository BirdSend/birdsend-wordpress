<?php
/**
 * Hook BirdSend menu into WP admin sidebar
 * @return void
 */
function bswp_add_admin() {
    add_menu_page('BirdSend for WordPress', 'BirdSend for WP', 'manage_options', 'bswp-settings', 'bswp_settings', WP_CONTENT_URL . '/plugins/birdsend-wordpress/assets/img/logo-menu.png');
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

    wp_register_script('materialize', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('bwsp-admin', BSWP_JS . 'admin.js', array('materialize'), '1.0.0', true);
    wp_enqueue_style('material-icons', '//fonts.googleapis.com/icon?family=Material+Icons');
    wp_enqueue_style('materialize', 'https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css');
    wp_enqueue_style('bswp-admin', BSWP_CSS . 'admin.css');
}
add_action('admin_enqueue_scripts', 'bswp_admin_scripts');

/**
 * Admin settings page
 */
function bswp_settings() {
    $mode = '';
    
    if (isset($_GET['mode'])) {
        $mode = $_GET['mode'];
    }

    switch ($mode) {
        case 'connect':
            include_once('admin-connect.php');
            break;
        
        default:
            include_once('admin-settings.php');
            break;
    }
}