<?php

/**
 * Inject pixel into all posts/pages/customs
 *
 * @return void
 */
function bswp_inject_pixel() {
	if ( ! bswp_is_enabled() ) {
		return;
	}
	echo '<!-- BirdSend Pixel Start -->' . "\n";
	?>
	<script>
	<?php echo bswp_pixel_code(); ?>
	</script>
	<?php
	echo '<!-- BirdSend Pixel End -->' . "\n";
}
add_action('wp_head', 'bswp_inject_pixel');