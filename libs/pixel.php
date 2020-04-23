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
	$tags = '';
	$categories = [];
	$pobj = get_queried_object();
	$postType = get_post_type();
	
	if (empty($postType)) {
		$postType = "";
	}

	if (isset($pobj->ID)) {
		$categories = array_map( function ( $category ) {
			return $category->slug;
		}, get_the_category( $pobj->ID ) );
		
		$allTags = get_the_tags( $pobj->ID );
		if ( is_array($allTags) ) {
			$tags = array_map( function ( $tag ) {
				return $tag->slug;
			}, $allTags );
			$tags = implode( "','", $tags );
		}
	}

	echo '<!-- BirdSend Pixel Start -->' . "\n";
	?>
	
	<script>
	var _bsfInfo = {
		wp: true,
		ptype: '<?php echo $postType; ?>',
		pcats: [ '<?php echo implode( "','", $categories ); ?>' ],
		ptags: [ '<?php echo $tags ?>' ],
	};
	<?php echo bswp_pixel_code(); ?>
	</script>
	
	<?php
	echo '<!-- BirdSend Pixel End -->' . "\n";
}
add_action('wp_head', 'bswp_inject_pixel');