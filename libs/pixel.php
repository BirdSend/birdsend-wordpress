<?php

/**
 * Inject pixel into all posts/pages/customs.
 *
 * @return void
 */
function bswp_inject_pixel() {
	if ( ! bswp_is_enabled() ) {
		return;
	}

	$pobj = get_queried_object();
	$post_type = get_post_type();

	$categories = array_map(
		function ( $category ) {
			return $category->slug;
		},
		get_the_category( $pobj->ID )
	);

	$tags     = '';
	$all_tags = get_the_tags( $pobj->ID );
	if ( is_array( $all_tags ) ) {
		$tags = array_map(
			function ( $tag ) {
				return $tag->slug;
			},
			$all_tags
		);
		$tags = implode( "','", $tags );
	}

	echo '<!-- BirdSend Pixel Start -->' . "\n";
	?>
	<script>
	var _bsfInfo = {
		wp: true,
		ptype: '<?php echo esc_attr( $post_type ); ?>',
		pcats: [ '<?php echo implode( "','", $categories ); ?>' ],
		ptags: [ '<?php echo $tags; ?>' ],
	};
	<?php echo bswp_pixel_code(); ?>
	</script>
	<?php
	echo '<!-- BirdSend Pixel End -->' . "\n";
}
add_action( 'wp_head', 'bswp_inject_pixel' );
