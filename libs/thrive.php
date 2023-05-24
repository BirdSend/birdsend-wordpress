<?php

add_action( 'thrive_dashboard_loaded', function () {
    \BSWP\Thrive\Main::init();
} );