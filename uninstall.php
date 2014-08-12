<?php
    if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
        exit();

    $option_name = 'mdm_db_version';

    delete_option( $option_name );

    // For site options in multisite
    delete_site_option( $option_name );

    global $wpdb;

    $table_name = $wpdb->prefix . "mah_download_manager";

    $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
