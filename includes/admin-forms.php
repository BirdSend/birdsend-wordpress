<?php

$sync_all_status = get_option( 'bswp_forms_sync_all' );

$params = array(
	'page' => isset( $_GET['p'] ) ? $_GET['p'] : 1,
	'per_page' => isset( $_GET['pp'] ) ? $_GET['pp'] : 15,
	'search' => isset( $_GET['search'] ) ? $_GET['search'] : ''
);

$pagination = bswp_paginate_forms( $params );

?>
<div class="bswp-wrapper">
	<?php include ( BSWP_PATH.'/includes/admin-header.php' ); ?>
	<div class="bswp-container bswp-container-wide">
		<div class="row">
			<div class="col s12 l12">
				<div class="card card-wide">
					<div class="card-content">
						<span class="card-title">Forms</span>
						<p class="bswp-my-2">Your BirdSend forms that are displayable on this WordPress site.</p>

						<div class="bswp-mt-4">
							<form action="" method="GET">
								<div style="display: none;">
									<input type="hidden" name="page" value="bswp-settings" />
									<input type="hidden" name="action" value="forms" />
								</div>
								<div class="row" style="margin-bottom: 0;">
									<div class="col">
										Search:
										<div class="input-field inline">
											<input id="input_search" type="text" name="search" value="<?php echo $params['search']; ?>">
											<label for="input_search">Name</label>
										</div>
										<button type="submit" class="btn-small yellow darken-1 blue-grey-text text-darken-4">Go</button>
									</div>
								</div>
							</form>
						</div>

						<table class="striped">
							<thead>
								<tr>
									<th>No</th>
									<th>Name</th>
									<th>Updated At (UTC)</th>
									<th>Last Sync At (UTC)</th>
									<th class="center-align">Display</th>
									<th class="center-align">Submission</th>
									<th class="center-align">Submission Rate</th>
									<th class="center-align">Actions</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $pagination['data'] as $index => $row ) { ?>
								<tr>
									<td><?php echo $pagination['meta']['from'] + $index; ?>.</td>
									<td><?php echo $row->name; ?></td>
									<td><?php echo $row->updated_at; ?></td>
									<td><?php echo $row->last_sync_at; ?></td>
									<td class="center-align"><?php echo $row->stats_displays_original + $row->stats_displays; ?></td>
									<td class="center-align"><?php echo $row->stats_submissions_original + $row->stats_submissions; ?></td>
									<td class="center-align"><?php echo round(($row->stats_submissions_original + $row->stats_submissions) / max(1, $row->stats_displays_original + $row->stats_displays) * 100, 2); ?>%</td>
									<td class="center-align">
										<form action="" method="POST">
											<input type="hidden" name="submit" value="sync-form">
											<input type="hidden" name="form_id" value="<?php echo $row->id; ?>">
											<a class="btn-small grey lighten-4 blue-grey-text text-darken-4" href="<?php echo bswp_app_url('user/forms/' . $row->id); ?>" target="_blank"><i class="material-icons">edit</i></a>
											<button type="submit" class="btn-small yellow darken-1 blue-grey-text text-darken-4"><i class="material-icons">refresh</i></button>
										</form>
									</td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
						<p><span class="grey-text"><em>* Submission and submission rate are updated every hour.</em></span></p>
						<div class="bswp-mt-8">
							<?php echo bswp_pagination_html( $pagination ); ?>
						</div>
					</div>
					<div class="card-action">
						<form action="" method="POST">
							<div style="display: none;">
								<input type="hidden" name="submit" value="sync-all" />
							</div>
							<?php if ( $sync_all_status ) { ?>
							<button type="submit" class="btn-small yellow darken-1 blue-grey-text text-darken-4 disabled" disabled><i class="material-icons left">refresh</i> <?php echo $sync_all_status == 1 ? 'Waiting for sync' : 'Syncing'; ?>...</button>
							<?php } else { ?>
							<button type="submit" class="btn-small yellow darken-1 blue-grey-text text-darken-4"><i class="material-icons left">refresh</i> Sync all forms</button>
							<?php } ?>
						</form>
					</div>
				</div><!-- ./card -->
			</div><!-- ./col -->
		</div><!-- ./row -->
	</div><!-- ./bswp-container -->
</div>
