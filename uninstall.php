<?php
namespace Mah\Mah_Download_Manager\Uninstall;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$option_name = 'mdm_db_version';

delete_option( $option_name );
// For site options in multisite.
delete_site_option( $option_name );

global $wpdb;
$table_name = sprintf( '%smah_download_manager', $wpdb->prefix );
$wpdb->prepare( $wpdb->query( 'DROP TABLE IF EXISTS %s' ), esc_html( $table_name ) );
