<?php

add_action( 'bswp_every_minute_event', 'bswp_every_minute_cronjob' );
function bswp_every_minute_cronjob() {
	update_option( 'bswp_cron_run_at', date( 'Y-m-d H:i:s', time() ) );

	bswp_forms_sync_all_exec();

	update_option( 'bswp_cron_run_finished_at', date( 'Y-m-d H:i:s', time() ) );
}

add_action( 'bswp_biweekly_token_refresh', 'bswp_cron_refresh_token' );
function bswp_cron_refresh_token() {
	if ( ! get_option( 'bswp_refresh_token' ) ) {
		return;
	}

	bswp_refresh_token( false );
}