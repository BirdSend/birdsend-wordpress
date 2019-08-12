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

	$postType = get_post_type();
	
	$categories = array_map( function ( $category ) {
		return $category->slug;
	}, get_the_category() );
	
	$tags = array_map( function ( $tag ) {
		return $tag->slug;
	}, get_the_tags() );

	echo '<!-- BirdSend Pixel Start -->' . "\n";
	?>
	
	<script>
	var _bsfInfo = {
		wp: true,
		ptype: '<?php echo $postType; ?>',
		pcats: [ '<?php echo implode( "','", $categories ); ?>' ],
		ptags: [ '<?php echo implode( "','", $tags ); ?>' ],
	}
	</script>
	
	<?php
	echo bswp_pixel_code() . "\n";
	echo '<!-- BirdSend Pixel End -->' . "\n";
}
add_action('wp_head', 'bswp_inject_pixel');