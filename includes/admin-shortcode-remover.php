<div class="bswp-wrapper">
	<?php include ( BSWP_PATH.'/includes/admin-header.php' ); ?>
	<div class="bswp-container">
		<div class="row">
			<div class="col s12 l12">
				<div class="card card-wide">
					<div class="card-content">
						<span class="card-title"><strong>Shortcode Remover Settings</strong></span>
						<p class="bswp-px-3 bswp-py-2 bswp-my-4 red lighten-5 red-text text-darken-4">Do <strong>NOT</strong> change anything here unless you know what you're doing!</p>
						<form action="" method="POST">
							<div style="display: none;">
								<input type="hidden" name="submit" value="shortcode-remover" />
							</div>
							<div class="row">
								<div class="input-field col s12">
									<input id="bswp_removed_shortcodes" name="bswp_removed_shortcodes" type="text" class="validate" value="<?php echo bswp_removed_shortcodes(); ?>" data-lpignore="true" />
									<label for="bswp_removed_shortcodes">Removed Shortcodes</label>
									<span class="helper-text">Separate with comma (,).</span>
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