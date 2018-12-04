<?php
/*
 * Plugin Name: BirdSend for WordPress
 * Version: 1.0.0
 * Plugin URI: https://birdsend.co/
 * Description: Official plugin to integrate BirdSend with WordPress.
 * Author: XooGuu Team
 * Author URI: https://birdsend.co/
 * License: GPL2
 * Domain Path: /languages/
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

define( 'BSWP_VERSION', '1.0.0' );
define( 'BSWP_DB_VERSION', '0.1' );

define( 'BSWP_URL', plugin_dir_url(__FILE__) );
define( 'BSWP_PATH', plugin_dir_path(__FILE__) );
define( 'BSWP_BASENAME', plugin_basename( __FILE__ ) );

define( 'BSWP_INC', BSWP_PATH . 'includes/');

define( 'BSWP_JS', BSWP_URL . 'assets/js/' );
define( 'BSWP_CSS', BSWP_URL . 'assets/css/' );
define( 'BSWP_IMG', BSWP_URL . 'assets/img/' );

if (file_exists( BSWP_PATH . 'config.php' )) {
	require_once( BSWP_PATH . 'config.php' );
}

if (! defined('BSWP_API_URL')) {
	define( 'BSWP_API_URL', 'https://api.birdsend.co/' );
}

if (! defined('BSWP_OAUTH_URL')) {
	define( 'BSWP_OAUTH_URL', 'https://api.birdsend.co/' );
}

if (! defined('BSWP_CLIENT_ID')) {
	// Set default password grant client ID
	define( 'BSWP_CLIENT_ID', '1' );
}

if (! defined('BSWP_CLIENT_SECRET')) {
	// Set default password grant client secret
	define( 'BSWP_CLIENT_SECRET', 'B6Ug1gEVmS6p3ZnTDWfJ1daa6I6hOb2SXBq68Ocj' );
}

// load composer packages
require_once( BSWP_PATH . 'vendor/autoload.php');

// load files
require_once( BSWP_INC . 'admin.php' );
require_once( BSWP_INC . 'admin-functions.php' );