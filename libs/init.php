<?php
// load composer packages.
require_once( BSWP_PATH . 'vendor/autoload.php');

// load libraries
require_once( BSWP_LIBS . 'helpers.php' );
require_once( BSWP_LIBS . 'functions.php' );
require_once( BSWP_LIBS . 'pixel.php' );
require_once( BSWP_LIBS . 'shortcodes.php' );
require_once( BSWP_LIBS . 'webhook.php' );
require_once( BSWP_LIBS . 'woocommerce.php' );
require_once( BSWP_LIBS . 'widgets.php' );
require_once( BSWP_LIBS . 'forms.php' );
require_once( BSWP_LIBS . 'form-loader.php' );

// load admin files.
if ( is_admin() ) {
    require_once( BSWP_INC . 'admin.php' );
    require_once( BSWP_INC . 'admin-functions.php' );
}

register_activation_hook( BSWP_PLUGIN_FILE_URL, 'bswp_install' );
register_deactivation_hook( BSWP_PLUGIN_FILE_URL, 'bswp_deactivation' );

add_filter( 'cron_schedules', 'bswp_add_cron_interval' );
function bswp_add_cron_interval( $schedules ) { 
    $schedules['bswp_every_minute'] = array(
        'interval' => 60,
        'display'  => esc_html__( '(BS) Every Minute' ), );
    return $schedules;
}

function bswp_install() {
    global $wpdb;

    $installed_ver = get_option('bswp_db_version');
    if ( $installed_ver != BSWP_DB_VERSION ) {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table = "{$wpdb->prefix}bswp_forms";
        $sql = "CREATE TABLE {$table} (
                `id` int(11) unsigned NOT NULL,
                `name` varchar(255) NOT NULL,
                `active` tinyint(1) NOT NULL DEFAULT '0',
                `type` varchar(50) NOT NULL,
                `triggers` longtext DEFAULT NULL,
                `placements_count` int(11) DEFAULT 0,
                `updated_at` timestamp NULL DEFAULT NULL,
                `raw_html` longtext DEFAULT NULL,
                `wg_html` longtext DEFAULT NULL,
                `version` varchar(32) NOT NULL,
                `last_sync_at` timestamp NULL DEFAULT NULL,
                `stats_displays_original` int(11) DEFAULT 0,
                `stats_submissions_original` int(11) DEFAULT 0,
                `stats_displays` int(11) DEFAULT 0,
                `stats_submissions` int(11) DEFAULT 0,
                UNIQUE KEY id (id),
                KEY `{$wpdb->prefix}bswp_forms_active_type_placements_count_index` (`active`, `type`,`placements_count`) USING BTREE
            ) DEFAULT CHARSET=utf8;";

        $table = "{$wpdb->prefix}bswp_logs";
        $sql .= "CREATE TABLE {$table} (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `description` text DEFAULT NULL,
                `subject_id` int(11) DEFAULT NULL,
                `subject_type` varchar(255) DEFAULT NULL,
                `properties` text DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT NULL,
                UNIQUE KEY id (id)
            ) DEFAULT CHARSET=utf8;";

        // create or update database...
        dbDelta( $sql );

        update_option( 'bswp_db_version', BSWP_DB_VERSION );
    }

    if ( ! $scheduled = wp_next_scheduled( 'bswp_every_minute_event' ) ) {
        wp_schedule_event( time(), 'bswp_every_minute', 'bswp_every_minute_event' );
    }

    if ( bswp_is_enabled() ) {
        // Sync all forms
        bswp_forms_sync_all();
    }
}

add_action( 'plugins_loaded', 'bswp_update_db_check' );
function bswp_update_db_check() {
    if ( get_option( 'bswp_db_version' ) != BSWP_DB_VERSION ) {
        bswp_install();
    }
}

function bswp_deactivation() {
    wp_clear_scheduled_hook( 'bswp_every_minute_event' );
}

// Import cron functions
require_once( BSWP_LIBS . 'cron.php' );