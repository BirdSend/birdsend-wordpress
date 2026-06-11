<?php

$log_params = array(
	'page' => isset( $_GET['p'] ) ? $_GET['p'] : 1,
	'per_page' => isset( $_GET['pp'] ) ? $_GET['pp'] : 20,
	'name' => isset( $_GET['name'] ) ? $_GET['name'] : '',
);

$log_pagination = bswp_paginate_logs( $log_params );
$token_refreshed_at = get_option( 'bswp_token_refreshed_at' );
$token_refresh_failed_at = get_option( 'bswp_token_refresh_failed_at' );
$developer_url = admin_url( 'admin.php?page=bswp-settings&action=developer' );
$qa_nonce = wp_create_nonce( 'bswp-qa-tools' );
$support_nonce = wp_create_nonce( 'bswp-support-tools' );
$next_token_refresh_at = bswp_get_next_token_refresh_datetime();
$forms_sync_status = bswp_get_forms_sync_all_status_label();
$forms_sync_status_code = (int) get_option( 'bswp_forms_sync_all', 0 );
$cron_run_at = get_option( 'bswp_cron_run_at' );
$cron_run_finished_at = get_option( 'bswp_cron_run_finished_at' );
$cron_stale = false;

if ( $cron_run_at ) {
	$cron_timestamp = strtotime( $cron_run_at );
	$cron_stale = $cron_timestamp && ( time() - $cron_timestamp ) > ( 5 * MINUTE_IN_SECONDS );
}

?>
<div class="bswp-wrapper">
	<?php include ( BSWP_PATH.'/includes/admin-header.php' ); ?>
	<div class="bswp-container bswp-container-wide">
		<div class="row">
			<div class="col s12 l12">
				<div class="card card-wide">
					<div class="card-content">
						<span class="card-title"><strong>Developer Settings</strong></span>
						<p class="bswp-px-3 bswp-py-2 bswp-my-4 red lighten-5 red-text text-darken-4">Do <strong>NOT</strong> change anything here unless you know what you're doing!</p>
						<form action="" method="POST">
							<div style="display: none;">
								<input type="hidden" name="submit" value="developer" />
							</div>
							<div class="row">
								<div class="input-field col s12">
									<input id="bswp_app_url" name="bswp_app_url" type="text" class="validate" value="<?php echo bswp_app_url(); ?>" data-lpignore="true" />
									<label for="bswp_app_url">App URL</label>
									<span class="helper-text">Set empty to use default settings</span>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12">
									<input id="bswp_api_url" name="bswp_api_url" type="text" class="validate" value="<?php echo bswp_api_url(); ?>" data-lpignore="true" />
									<label for="bswp_api_url">API URL</label>
									<span class="helper-text">Set empty to use default settings</span>
								</div>
							</div>
							<div class="bswp-my-4">
								<button type="submit" id="bswp-connect-btn" class="waves-effect waves-light btn orange lighten-2"><i class="material-icons left bswp-mr-2">save</i>Save Changes</button>
							</div>
						</form>
					</div>
				</div><!-- ./card -->

				<div class="card card-wide">
					<div class="card-content">
						<span class="card-title"><strong>QA Tools</strong></span>
						<p class="bswp-my-2 grey-text">Helpers for testing connection renewal and warning notices. For troubleshooting only — these actions do not change your BirdSend account settings.</p>

						<div class="bswp-my-4 grey lighten-4 bswp-px-3 bswp-py-2">
							<p class="bswp-my-1"><strong>Connected:</strong> <?php echo bswp_is_connected() ? 'Yes' : 'No'; ?></p>
							<p class="bswp-my-1"><strong>Last successful renewal:</strong>
								<?php if ( $token_refreshed_at ) { ?>
									<?php echo esc_html( bswp_format_datetime( $token_refreshed_at ) ); ?>
								<?php } else { ?>
									<span class="grey-text">None yet</span>
								<?php } ?>
							</p>
							<p class="bswp-my-1"><strong>Last failed renewal:</strong>
								<?php if ( $token_refresh_failed_at ) { ?>
									<?php echo esc_html( bswp_format_datetime( $token_refresh_failed_at ) ); ?>
								<?php } else { ?>
									<span class="grey-text">None</span>
								<?php } ?>
							</p>
							<p class="bswp-my-1"><strong>Next scheduled renewal:</strong>
								<?php if ( $next_token_refresh_at ) { ?>
									<?php echo esc_html( $next_token_refresh_at ); ?>
								<?php } else { ?>
									<span class="grey-text">Not scheduled</span>
								<?php } ?>
							</p>
							<p class="bswp-my-1"><strong>Warning notice active:</strong> <?php echo $token_refresh_failed_at ? 'Yes' : 'No'; ?></p>
						</div>

						<div class="bswp-my-4">
							<form action="<?php echo esc_url( $developer_url ); ?>" method="POST" class="bswp-mr-2" style="display: inline-block;">
								<input type="hidden" name="submit" value="qa-run-renewal" />
								<input type="hidden" name="bswp_qa_nonce" value="<?php echo esc_attr( $qa_nonce ); ?>" />
								<button type="submit" class="btn-small yellow darken-1 blue-grey-text text-darken-4">Run connection renewal now</button>
							</form>

							<form action="<?php echo esc_url( $developer_url ); ?>" method="POST" class="bswp-mr-2" style="display: inline-block;">
								<input type="hidden" name="submit" value="qa-preview-notice" />
								<input type="hidden" name="bswp_qa_nonce" value="<?php echo esc_attr( $qa_nonce ); ?>" />
								<button type="submit" class="btn-small grey lighten-4 blue-grey-text text-darken-4" onclick="return confirm('This simulates the warning for testing only. Your BirdSend connection is NOT broken. Continue?');">Simulate warning (test UI only)</button>
							</form>

							<form action="<?php echo esc_url( $developer_url ); ?>" method="POST" style="display: inline-block;">
								<input type="hidden" name="submit" value="qa-clear-notice" />
								<input type="hidden" name="bswp_qa_nonce" value="<?php echo esc_attr( $qa_nonce ); ?>" />
								<button type="submit" class="btn-small grey lighten-4 blue-grey-text text-darken-4">Clear warning notice</button>
							</form>
						</div>

						<p class="bswp-my-2 grey-text"><em><strong>Run connection renewal now</strong> uses the same process as the automatic biweekly renewal.<br><strong>Simulate warning (test UI only)</strong> shows the admin warning without breaking your connection — use it to preview the message, not to diagnose a real problem.<br><strong>Clear warning notice</strong> removes a simulated or real warning from wp-admin.</em></p>
					</div>
				</div><!-- ./card -->

				<div class="card card-wide">
					<div class="card-content">
						<span class="card-title"><strong>Support Tools</strong></span>
						<p class="bswp-my-2 grey-text">Use these when a customer reports <strong>Sync all forms</strong> is stuck on &ldquo;Waiting for sync&rdquo; or &ldquo;Syncing&rdquo; on the Forms page.</p>

						<div class="bswp-my-4 grey lighten-4 bswp-px-3 bswp-py-2">
							<p class="bswp-my-1"><strong>Sync queue status:</strong> <?php echo esc_html( $forms_sync_status ); ?> <?php if ( $forms_sync_status_code ) { ?><span class="grey-text">(code <?php echo esc_html( $forms_sync_status_code ); ?>)</span><?php } ?></p>
							<p class="bswp-my-1"><strong>BirdSend connected:</strong> <?php echo bswp_is_connected() ? 'Yes' : 'No'; ?></p>
							<p class="bswp-my-1"><strong>Last plugin cron run:</strong>
								<?php if ( $cron_run_at ) { ?>
									<?php echo esc_html( $cron_run_at ); ?>
									<?php if ( $cron_stale ) { ?>
										<span class="red-text text-darken-2">(over 5 minutes ago — WP Cron may not be running)</span>
									<?php } ?>
								<?php } else { ?>
									<span class="grey-text">None recorded yet</span>
								<?php } ?>
							</p>
							<p class="bswp-my-1"><strong>Last plugin cron finished:</strong>
								<?php if ( $cron_run_finished_at ) { ?>
									<?php echo esc_html( $cron_run_finished_at ); ?>
								<?php } else { ?>
									<span class="grey-text">None recorded yet</span>
								<?php } ?>
							</p>
						</div>

						<div class="bswp-my-4">
							<form action="<?php echo esc_url( $developer_url ); ?>" method="POST" class="bswp-mr-2" style="display: inline-block;">
								<input type="hidden" name="submit" value="support-run-sync" />
								<input type="hidden" name="bswp_support_nonce" value="<?php echo esc_attr( $support_nonce ); ?>" />
								<button type="submit" class="btn-small yellow darken-1 blue-grey-text text-darken-4">Run sync all forms now</button>
							</form>

							<form action="<?php echo esc_url( $developer_url ); ?>" method="POST" style="display: inline-block;">
								<input type="hidden" name="submit" value="support-reset-sync" />
								<input type="hidden" name="bswp_support_nonce" value="<?php echo esc_attr( $support_nonce ); ?>" />
								<button type="submit" class="btn-small grey lighten-4 blue-grey-text text-darken-4" onclick="return confirm('This clears a stuck sync status so the Sync all forms button works again. It does not sync forms by itself. Continue?');">Reset sync status</button>
							</form>
						</div>

						<p class="bswp-my-2 grey-text"><em><strong>Run sync all forms now</strong> performs the sync immediately instead of waiting for WP Cron.<br><strong>Reset sync status</strong> only unlocks the Forms page button — use it when the status is stuck, then run sync or ask the customer to click Sync all forms again.<br>If sync still fails, check BirdSend connection (QA Tools above) before escalating to the tech team.</em></p>
					</div>
				</div><!-- ./card -->

				<div class="card card-wide">
					<div class="card-content">
						<span class="card-title"><strong>Activity Log</strong></span>
						<p class="bswp-my-2 grey-text">Read-only log of plugin events (form webhooks, token refresh, etc.).</p>

						<?php if ( $token_refreshed_at || $token_refresh_failed_at ) { ?>
						<div class="bswp-my-4 grey lighten-4 bswp-px-3 bswp-py-2">
							<?php if ( $token_refreshed_at ) { ?>
							<p class="bswp-my-1"><strong>Last token refresh:</strong> <?php echo esc_html( bswp_format_datetime( $token_refreshed_at ) ); ?></p>
							<?php } ?>
							<?php if ( $token_refresh_failed_at ) { ?>
							<p class="bswp-my-1 red-text text-darken-2"><strong>Last token refresh failure:</strong> <?php echo esc_html( bswp_format_datetime( $token_refresh_failed_at ) ); ?></p>
							<?php } ?>
						</div>
						<?php } ?>

						<div class="bswp-mt-4">
							<form action="" method="GET">
								<div style="display: none;">
									<input type="hidden" name="page" value="bswp-settings" />
									<input type="hidden" name="action" value="developer" />
								</div>
								<div class="row" style="margin-bottom: 0;">
									<div class="col">
										<div class="input-field inline">
											<input id="input_log_name" type="text" name="name" placeholder="e.g. token_refresh" value="<?php echo esc_attr( $log_params['name'] ); ?>">
											<label for="input_log_name">Event name</label>
										</div>
										<button type="submit" class="btn-small yellow darken-1 blue-grey-text text-darken-4">Filter</button>
										<?php if ( $log_params['name'] ) { ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=bswp-settings&action=developer' ) ); ?>" class="btn-small grey lighten-4 blue-grey-text text-darken-4">Clear</a>
										<?php } ?>
									</div>
								</div>
							</form>
						</div>

						<table class="striped bswp-mt-4">
							<thead>
								<tr>
									<th>No</th>
									<th>ID</th>
									<th>Event</th>
									<th>Description</th>
									<th>Subject</th>
									<th>Details</th>
									<th>Created At</th>
								</tr>
							</thead>
							<tbody>
								<?php if ( $log_pagination['data'] ) { ?>
									<?php foreach ( $log_pagination['data'] as $index => $row ) { ?>
									<tr>
										<td><?php echo esc_html( $log_pagination['meta']['from'] + $index ); ?>.</td>
										<td><?php echo esc_html( $row->id ); ?></td>
										<td><code><?php echo esc_html( $row->name ); ?></code></td>
										<td><?php echo esc_html( $row->description ); ?></td>
										<td>
											<?php
											if ( $row->subject_type || $row->subject_id ) {
												echo esc_html( trim( $row->subject_type . ( $row->subject_id ? ' #' . $row->subject_id : '' ) ) );
											}
											?>
										</td>
										<td>
											<?php
											$log_details = bswp_get_activity_log_properties_text( $row->properties );
											if ( $log_details ) {
												?>
												<textarea class="bswp-log-details" readonly onclick="this.select();"><?php echo esc_textarea( $log_details ); ?></textarea>
												<?php
											}
											?>
										</td>
										<td><?php echo esc_html( bswp_format_datetime( $row->created_at ) ); ?></td>
									</tr>
									<?php } ?>
								<?php } else { ?>
									<tr>
										<td colspan="7" class="center-align grey-text">No activity log entries found.</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>

						<?php if ( $log_pagination['meta']['total'] ) { ?>
						<p class="bswp-my-2 grey-text">
							Showing <?php echo esc_html( $log_pagination['meta']['from'] ); ?>–<?php echo esc_html( $log_pagination['meta']['to'] ); ?>
							of <?php echo esc_html( $log_pagination['meta']['total'] ); ?> entries
						</p>
						<?php } ?>

						<?php if ( $log_pagination['meta']['last_page'] > 1 ) { ?>
						<div class="bswp-mt-4">
							<?php echo bswp_pagination_html( $log_pagination ); ?>
						</div>
						<?php } ?>
					</div>
				</div><!-- ./card -->
			</div><!-- ./col -->
		</div><!-- ./row -->
	</div><!-- ./bswp-container -->
</div>
