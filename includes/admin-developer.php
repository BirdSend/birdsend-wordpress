<div class="bswp-wrapper">
	<?php require BSWP_PATH . '/includes/admin-header.php'; ?>
	<div class="bswp-container">
		<div class="row">
			<div class="col s12 l12">
				<div class="card card-wide">
					<div class="card-content">
						<span class="card-title"><strong>Developer Settings</strong></span>
						<p class="bswp-px-3 bswp-py-2 bswp-my-4 red lighten-5 red-text text-darken-4">Do <strong>NOT</strong> change anything here unless you know what you're doing!</p>
						<form action="" method="POST">
							<?php wp_nonce_field( 'birdsend-adm-form', 'nonce' ); ?>
							<div style="display: none;">
								<input type="hidden" name="submit" value="developer" />
							</div>
							<div class="row">
								<div class="input-field col s12">
									<input id="bswp_app_url" name="bswp_app_url" type="text" class="validate" value="<?php echo esc_url( bswp_app_url() ); ?>" data-lpignore="true" />
									<label for="bswp_app_url">App URL</label>
									<span class="helper-text">Set empty to use default settings</span>
								</div>
							</div>
							<div class="row">
								<div class="input-field col s12">
									<input id="bswp_api_url" name="bswp_api_url" type="text" class="validate" value="<?php echo esc_url( bswp_api_url() ); ?>" data-lpignore="true" />
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
			</div><!-- ./col -->
		</div><!-- ./row -->
	</div><!-- ./bswp-container -->
</div>
