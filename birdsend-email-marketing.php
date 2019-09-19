<?php
/*
 * Plugin Name: BirdSend Email Marketing
 * Version: 1.0.1
 * Plugin URI: https://birdsend.co/
 * Description: Official BirdSend plugin to integrate with WordPress.
 * Author: BirdSend
 * License: GPLv2 or later
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2018 XooGuu, LLC.
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BSWP_VERSION', '1.0.1' );
define( 'BSWP_DB_VERSION', '0.1' );

define( 'BSWP_URL', plugin_dir_url(__FILE__) );
define( 'BSWP_PATH', plugin_dir_path(__FILE__) );
define( 'BSWP_BASENAME', plugin_basename( __FILE__ ) );
define( 'BSWP_LIBS', BSWP_PATH . 'libs/' );

define( 'BSWP_INC', BSWP_PATH . 'includes/' );

define( 'BSWP_JS', BSWP_URL . 'assets/js/' );
define( 'BSWP_CSS', BSWP_URL . 'assets/css/' );
define( 'BSWP_IMG', BSWP_URL . 'assets/img/' );

define( 'BSWP_APP_URL', 'https://app.birdsend.co/' );
define( 'BSWP_API_URL', 'https://api.birdsend.co/' );


// load composer packages.
require_once( BSWP_PATH . 'vendor/autoload.php');


// load libraries
require_once( BSWP_LIBS . 'update.php' );
require_once( BSWP_LIBS . 'helpers.php' );
require_once( BSWP_LIBS . 'functions.php' );
require_once( BSWP_LIBS . 'pixel.php' );
require_once( BSWP_LIBS . 'shortcodes.php' );
require_once( BSWP_LIBS . 'webhook.php' );
require_once( BSWP_LIBS . 'woocommerce.php' );

// load admin files.
if ( is_admin() ) {
    require_once( BSWP_INC . 'admin.php' );
    require_once( BSWP_INC . 'admin-functions.php' );
}