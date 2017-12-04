<?php
/**
 * Plugin Name: Mah Download Manager
 * Plugin URI:  http://marioaguiar.net
 * Description: A simple download manager for WordPress
 * Version:     2.0
 * Author:      Mario Aguiar
 * Author URI:  http://marioaguiar.net
 * License:     GPLv2+
 * Text Domain: mah_download
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 10up (email : info@10up.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using yo wp-make:plugin
 * Copyright (c) 2015 10up, LLC
 * https://github.com/10up/generator-wp-make
 */

// Useful global constants
define( 'MAH_DOWNLOAD_VERSION', '2.0' );
define( 'MAH_DOWNLOAD_URL',     plugin_dir_url( __FILE__ ) );
define( 'MAH_DOWNLOAD_PATH',    dirname( __FILE__ ) . '/' );
define( 'MAH_DOWNLOAD_INC',     MAH_DOWNLOAD_PATH . 'includes/' );

// Include files
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}
require_once MAH_DOWNLOAD_INC . 'functions/core.php';
require_once MAH_DOWNLOAD_INC . 'classes/class-mah-download-manager-list.php';

// Activation/Deactivation
register_activation_hook( __FILE__, '\Mah\Mah_Download_Manager\Core\activate' );
register_deactivation_hook( __FILE__, '\Mah\Mah_Download_Manager\Core\deactivate' );

// Bootstrap
Mah\Mah_Download_Manager\Core\setup();