<?php
require_once( BSWP_LIBS . 'plugin-update-checker/plugin-update-checker.php' );
try {
	$bswpUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
		'https://github.com/BirdSend/birdsend-wordpress/',
		__FILE__,
		'birdsend-email-marketing'
	);
} catch (\Exception $e) {}