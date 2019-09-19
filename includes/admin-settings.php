<div class="bswp-wrapper">
	<?php include ( BSWP_PATH.'/includes/admin-header.php' ); ?>
	<div class="bswp-container">
		<div class="row">
			<div class="col s12 l12">
				<div class="card card-wide">
					<div class="card-content">
						<?php if ( $bswp_token = bswp_token() ) { ?>
							<span class="card-title"><strong>Connected to BirdSend Account</strong></span>
						<?php } else { ?>
							<span class="card-title"><strong>Connect to BirdSend Account</strong></span>
						<?php } ?>

						<p class="bswp-my-2">Email Marketing Tool Exclusively For Content Creators</p>
						
						<?php if ( $bswp_token) { ?>
							<div class="bswp-my-4 center-align">
								<a href="<?php echo bswp_app_url( 'forms/new' ); ?>" class="btn-large yellow darken-1 blue-grey-text text-darken-4" target="_blank"><i class="material-icons left bswp-mr-2">add_circle</i>Create New Form</a>
							</div>
							
							<form action="<?php echo bswp_app_url( 'sites/authorize/wordpress' ); ?>" method="GET">
								<div style="display: none;">
									<input type="hidden" name="disconnect" value="1" />
									<input type="hidden" name="site_url" value="<?php echo get_site_url(); ?>" />
									<input type="hidden" name="redirect" value="<?php echo admin_url( 'admin.php?page=bswp-settings&action=disconnect-site' ); ?>" />
									<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'birdsend-disconnect-site' ); ?>" />
								</div>
								<div class="bswp-my-2 right-align">
									<button type="submit" id="bswp-disconnect-btn" class="btn-flat transparent btn-small red-text darken-2"><i class="material-icons left bswp-mr-1">close</i>Disconnect My BirdSend Account</button>
								</div>
							</form>

							<div class="bswp-my-4">
								<em>Note: For WooCommerce integration, simply go back to the <a href="<?php echo bswp_app_url('user/rules') ?>" target="_blank">Rules page</a> inside your BirdSend account to set it up.</em>
							</div>
							
						<?php } else { ?>
						
							<p class="bswp-my-2">Please <a href="https://birdsend.co/pricing" target="_blank">create a BirdSend account</a> or connect to an existing account.</p>
							<form action="<?php echo bswp_app_url( 'sites/authorize/wordpress' ); ?>" method="GET">
								<div style="display: none;">
									<input type="hidden" name="site_url" value="<?php echo get_site_url(); ?>" />
									<input type="hidden" name="ajax_url" value="<?php echo admin_url( 'admin-ajax.php' ); ?>" />
									<input type="hidden" name="redirect" value="<?php echo admin_url( 'admin.php?page=bswp-settings&action=auth-site' ); ?>" />
									<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'birdsend-auth-site' ); ?>" />
								</div>
								<div class="bswp-my-4 center-align">
									<button type="submit" id="bswp-connect-btn" class="btn-large yellow darken-1 blue-grey-text text-darken-4"><i class="material-icons left bswp-mr-2">check</i>Connect To My BirdSend Account</button>
								</div>
							</form>
						
						<?php } ?>
					
					</div>
				</div><!-- ./card -->
			</div><!-- ./col -->
		</div><!-- ./row -->
		<div class="row">
			<div class="col s12 l12">
				<p class="center-align"><a href="https://help.birdsend.co/article/show/62850" target="_blank">Get help connecting to your BirdSend account</a></p>
			</div>
		</div>
	</div><!-- ./bswp-container -->
</div>