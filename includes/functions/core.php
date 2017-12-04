<?php
/**
 * Core functions file.
 *
 * @package Mah_Download_Manager
 * @subpackage Core
 * @since 2.0
 */

namespace Mah\Mah_Download_Manager\Core;
use Mah\Mah_Download_Manager\Downloads;

/**
 * Default setup routine
 *
 * @uses add_action()
 * @uses do_action()
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'admin_init',                         $n( 'i18n' ) );
	add_action( 'admin_init',                         $n( 'init' ) );
	add_action( 'admin_init',                         $n( 'set_uploads_directory' ) );

	add_action( 'admin_menu',                         $n( 'register_menu_pages' ) );
	add_action( 'mah_download_display_messages',      $n( 'display_messages' ) );

	add_action( 'admin_init',                         $n( 'redirect_manager' ) );

	do_action( 'mah_download_loaded' );
}

/**
 * Registers the default textdomain.
 *
 * @uses apply_filters()
 * @uses get_locale()
 * @uses load_textdomain()
 * @uses load_plugin_textdomain()
 * @uses plugin_basename()
 *
 * @return void
 */
function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'mah_download' );
	load_textdomain( 'mah_download', WP_LANG_DIR . '/mah_download/mah_download-' . $locale . '.mo' );
	load_plugin_textdomain( 'mah_download', false, plugin_basename( MAH_DOWNLOAD_PATH ) . '/languages/' );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @uses do_action()
 *
 * @return void
 */
function init() {
	do_action( 'mah_download_init' );
}

/**
 * Activate the plugin
 *
 * @uses init()
 * @uses flush_rewrite_rules()
 *
 * @return void
 */
function activate() {
	// First load the init scripts in case any rewrite functionality is being loaded.
	init();
	flush_rewrite_rules();

	$current_version = get_option( 'mah_download_version' );

	if ( ! $current_version ) {
		install();
	} else {
		upgrade( $current_version );
	}
}

/**
 * Registers new tables in the database.
 * Updates saved version of the plugin.
 *
 * @return void
 */
function install() {
	global $wpdb;

	$table_name      = sprintf( esc_html( '%smah_download_manager' ), $wpdb->prefix );
	$charset_collate = '';

	if ( ! empty( $wpdb->charset ) ) {
		$charset_collate = sprintf( esc_html( 'DEFAULT CHARACTER SET %s' ), $wpdb->charset );
	}

	if ( ! empty( $wpdb->collate ) ) {
		$charset_collate .= sprintf( esc_html( ' COLLATE %s' ), $wpdb->collate );
	}

	$sql = "CREATE TABLE $table_name (
                        id mediumint(9) NOT NULL AUTO_INCREMENT,
                        date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                        name tinytext NOT NULL,
                        type tinytext NOT NULL,
                        size bigint NOT NULL,
                        url varchar(255) DEFAULT '' NOT NULL,
                        path varchar(255) DEFAULT '' NOT NULL,
                        UNIQUE KEY id (id)
                    ) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	\dbDelta( $sql );

	update_option( 'mah_download_version', MAH_DOWNLOAD_VERSION );
}

/**
 * Reserved for a DB upgrade if needed
 *
 * @param string $current_version Current version stored in options.
 * @return void
 */
function upgrade( $current_version ) {
	if ( MAH_DOWNLOAD_VERSION === $current_version ) {
		return;
	}

	// Upgrade stuff goes here...
	update_option( 'mah_download_version', MAH_DOWNLOAD_VERSION );
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @return void
 */
function deactivate() {

}

/**
 * Sets a directory to store files.
 *
 * @return void
 */
function set_uploads_directory() {
	$uploads_directory = wp_upload_dir( current_time( 'mysql' ) );

	\add_option( 'mah_uploads_directory', $uploads_directory );
}

/**
 * Return uploads directory from site options.
 *
 * @return array|WP_Error
 */
function get_uploads_directory() {
	$uploads_directory = get_option( 'mah_uploads_directory' );

	if ( empty( $uploads_directory ) ) {
		$uploads_directory = new \WP_Error(
			'mah_directory_error',
			esc_html__( 'Uploads directory has not been set', 'mah_download' )
		);
	}

	return $uploads_directory;
}

/**
 * Register new pages inside the dashboard.
 *
 * @return void
 */
function register_menu_pages() {
	add_menu_page(
		esc_html__( 'Mah Download manager', 'mah_download' ),
		esc_html__( 'Downloads', 'mah_download' ),
		'manage_options',
		'mah-download-manager',
		__NAMESPACE__ . '\display_menu_page',
		'dashicons-download',
		12
	);

	add_submenu_page(
		'mah-download-manager',
		esc_html__( 'Add new file', 'mah_download' ),
		esc_html__( 'Add new file', 'mah_download' ),
		'upload_files',
		'mah-download-manager/new',
		__NAMESPACE__ . '\display_add_new_page'
	);

	add_submenu_page(
		'mah-download-manager/new',
		esc_html__( 'Upload file', 'mah_download' ),
		esc_html__( 'Upload file', 'ma-download' ),
		'upload_files',
		'mah-download-manager/upload',
		__NAMESPACE__ . '\upload_file_init'
	);

	add_submenu_page(
		'mah-download-manager/new',
		esc_html__( 'Delete file', 'mah_download' ),
		esc_html__( 'Delete file', 'ma-download' ),
		'upload_files',
		'mah-download-manager/delete',
		__NAMESPACE__ . '\confirm_delete'
	);
}

/**
 * Main page / list of downloads.
 *
 * @return void
 */
function display_menu_page() {
?>
	<div class="wrap">
		<h2>
			<?php esc_html_e( 'Mah Download manager', 'mah_download' ); ?> 
			<a class="add-new-h2" href="<?php echo esc_url( admin_url( 'admin.php?page=mah-download-manager/new' ) ); ?>">
				<?php esc_html_e( 'Add new file', 'mah_download' ); ?>
			</a>
		</h2>

		<?php
			do_action( 'mah_download_display_messages' );

			$list = new Downloads\Mah_Download_Manager_List();
			$list->prepare_items();
			$list->display();
		?>
	</div>
<?php
}

/**
 * "Add new" page and form.
 *
 * @return void
 */
function display_add_new_page() {
?>

	<div class="wrap">
		<h2><?php esc_html_e( 'Add new file', 'mah_download' ); ?></h2>

		<form action="<?php echo esc_url( \add_query_arg( array( 'action' => 'upload' ), admin_url( 'admin.php?page=mah-download-manager/upload' ) ) ); ?>" method="post" class="wp-upload-form" enctype="multipart/form-data">
			<?php wp_nonce_field( 'mah-download-manager' ); ?>

			<label for="mdm-file"><?php esc_html_e( 'File', 'mah_download' ); ?></label>
			<input type="file" name="mdm-file" id="mdm-file" />

			<input type="submit" name="mdm-upload" id="mdm-file" value="<?php esc_attr_e( 'Upload', 'mah_download' ); ?>" />
		</form>
	</div>

<?php
}

/**
 * Placeholder for a page
 *
 * @return boolean
 */
function upload_file_init() {
	return;
}

/**
 * Performs checks to ensure the file is uploaded to the server and data is saved to the database.
 *
 * @param array $file File to be uploaded.
 * @return void
 */
function upload_file( $file ) {
	$file              = ( ! empty( $file ) ) ? $file : new \WP_Error( 'empty_file', esc_html__( "Seems like you didn't upload a file", 'mah_download' ) );
	$uploads_directory = get_uploads_directory();

	if ( is_wp_error( $file ) ) {
		wp_die(
			esc_html( $file->get_error_message() ),
			esc_html__( 'Error uploading the file', 'mah_download' )
		);
	}

	if ( is_wp_error( $uploads_directory ) ) {
		wp_die(
			esc_html( $uploads_directory->get_error_message() ),
			esc_html__( 'Error uploading the file', 'mah_download' )
		);
	}

	$file_temp_dir = $file['tmp_name'];
	$file_name     = trailingslashit( $uploads_directory['path'] );
	$file_name    .= sanitize_file_name( $file['name'] );

	$response      = move_file( $file_temp_dir, $file_name );

	if ( is_wp_error( $response ) ) {
		wp_die(
			esc_html( $response->get_error_message() ),
			esc_html__( 'Error uploading the file', 'mah_download' )
		);
	}

	$file_id = store_data( $file );

	if ( ! $file_id ) {
		wp_die(
			esc_html__( 'There was an error saving the data to the database', 'mah_download' )
		);
	}

	wp_safe_redirect( admin_url( 'admin.php?page=mah-download-manager&message=1' ) );
	exit;
}

/**
 * Moves the file from the temp location to it's new permanent location.
 *
 * @param string $from     Temporary location.
 * @param string $to       New location of file.
 * @return string|WP_Error New location path or error
 */
function move_file( $from, $to ) {
	global $wp_filesystem;

	if ( $wp_filesystem->move( $from, $to ) ) {
		return $to;
	}

	return \WP_Error(
		'moving_error',
		esc_html__( 'Error trying to move the file to the new location', 'mah_download' )
	);
}

/**
 * Stores file information into the database.
 *
 * @param array $file File to be stored in database.
 * @return bool|string File ID if success or false.
 */
function store_data( $file ) {
	global $wpdb;

	$table_name        = sprintf( '%smah_download_manager', $wpdb->prefix );
	$uploads_directory = get_uploads_directory();

	if ( is_wp_error( $uploads_directory ) ) {
		wp_die(
			esc_html( $uploads_directory->get_error_message() ),
			esc_html__( 'Error storing data to the database', 'mah_download' )
		);
	}

	$data = array(
		'name' => sanitize_file_name( $file['name'] ),
		'type' => sanitize_mime_type( $file['type'] ),
		'size' => intval( $file['size'] ),
		'date' => current_time( 'mysql' ),
		'url'  => esc_url( trailingslashit( $uploads_directory['url'] ) . $file['name'] ),
		'path' => trailingslashit( $uploads_directory['path'] ) . sanitize_file_name( $file['name'] )
	);

	return $wpdb->insert( $table_name, $data );
}

/**
 * Displays admin notices after an action is performed.
 *
 * @return void
 */
function display_messages() {
	$message_code = filter_input( INPUT_GET, 'message', FILTER_SANITIZE_NUMBER_INT );

	if ( empty( $message_code ) ) {
		return;
	}

	$class = 'notice is-dismissable ';
	$text  = '';

	switch ( $message_code ) {
		case 1:
			$class .= 'updated';
			$text  = esc_html__( 'File uploaded successfully', 'mah_download' );
			break;
		case 2:
			$class .= 'updated';
			$text  = esc_html__( 'The selected file has been removed', 'mah_download' );
			break;

		// No default.
	}
?>
	<div class="<?php echo esc_attr( $class ); ?>">
		<?php echo esc_html( $text ); ?>
	</div>
<?php
}

/**
 * Extra step before deleting a file.
 * Asks the user to confirm the removal of said file.
 *
 * @return void
 */
function confirm_delete() {
	$file_id = filter_input( INPUT_GET, 'file_id', FILTER_SANITIZE_NUMBER_INT );

	if ( empty( $file_id ) ) {
		wp_die( esc_html__( 'You need to select a file to work on!', 'mah_download' ) );
	}
?>
	<p><?php esc_html_e( 'Are you sure you want to delete this file? This action cannot be reversed.', 'mah_download' ); ?></p>

	<a href="<?php echo esc_url( add_query_arg( array( 'action' => 'delete', 'confirm' => 1, 'file_id' => $file_id ), admin_url( 'admin.php?page=mah-download-manager/delete' ) ) ); ?>" class="button button-primary">
		<?php esc_html_e( 'Delete anyways', 'mah_download' ); ?>
	</a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=mah-download-manager' ) ); ?>" class="button">
		<?php esc_html_e( 'Cancel', 'mah_download' ); ?>
	</a>
<?php
}

/**
 * Remove file from server and database.
 *
 * @param int $file_id File to erase.
 * @return void
 */
function delete_file( $file_id ) {
	global $wpdb, $wp_filesystem;

	$table_name = sprintf( '%smah_download_manager', $wpdb->prefix );

	$file_path  = $wpdb->get_var(
		$wpdb->prepare( "SELECT path FROM {$table_name} WHERE id = %d", intval( $file_id ) )
	);

	$errors     = new \WP_Error();

	$file_deleted = ( $wp_filesystem->delete( $file_path ) )
					? true
					: $errors->add(
						'delete_file_error',
						esc_html__( 'There was an error removing the file from the server. Check the path?', 'mah_download' )
					);

	$row_deleted  = ( $wpdb->delete( $table_name, array( 'id' => $file_id ) ) )
					? true
					: $errors->add(
						'delete_row_error',
						esc_html__( 'There was an error removing the data from the database.', 'mah_download' )
					);

	if ( is_wp_error( $errors ) && ! empty( $error->errors ) ) {
		wp_die(
			esc_html( $errors->get_error_messages() ),
			esc_html__( 'There were errors while deleting the file.', 'mah_download' )
		);
	}

	wp_safe_redirect( admin_url( 'admin.php?page=mah-download-manager&message=2' ) );
	exit;
}

/**
 * Manage actions that lead to redirects inside the plugin.
 * We need this because redirects must happen before anything is rendered in the page.
 *
 * @return void
 */
function redirect_manager() {
	$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );

	if ( empty( $action ) ) {
		return;
	}

	switch ( $action ) {
		case 'upload':
			\check_admin_referer( 'mah-download-manager' );

			$mdm_upload      = filter_input( INPUT_POST, 'mdm-upload', FILTER_SANITIZE_STRING );
			$mdm_form_fields = array( 'mdm-file', 'mdm-upload' );
			$mdm_method      = '';

			if ( ! empty( $mdm_upload ) ) {
				$url = wp_nonce_url( 'mah-download-manager/new', 'mah-download-manager' );

				if ( ! $creds = request_filesystem_credentials( $url, $mdm_method, false, false, $mdm_form_fields ) ) {
					return true;
				}

				if ( ! \WP_Filesystem( $creds ) ) {
					\request_filesystem_credentials( $url, $mdm_method, true, false, $mdm_form_fields );
					return true;
				}

				$file_temp_data = $_FILES['mdm-file'];

				upload_file( $file_temp_data );
			}
			break;
		case 'delete':
			$confirm = filter_input( INPUT_GET, 'confirm', FILTER_SANITIZE_STRING );
			$file_id = filter_input( INPUT_GET, 'file_id', FILTER_SANITIZE_NUMBER_INT );

			if ( empty( $file_id ) ) {
				wp_die( esc_html__( 'You need to select a file to work on!', 'mah_download' ) );
			}

			if ( ! empty( $confirm ) && '1' === $confirm ) {
				$mdm_method = '';
				$url        = wp_nonce_url( 'admin.php?page=mah-download-manager&action=delete', 'mah-download-manager' );

				if ( ! $creds = \request_filesystem_credentials( $url, $mdm_method, false, false ) ) {
					return true;
				}

				if ( ! \WP_Filesystem( $creds ) ) {
					\request_filesystem_credentials( $url, $mdm_method, true, false );
					return true;
				}

				delete_file( $file_id );
			}
	}

}
