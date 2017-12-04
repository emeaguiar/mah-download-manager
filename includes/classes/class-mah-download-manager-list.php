<?php
namespace Mah\Mah_Download_Manager\Downloads;

/**
 * Downloads table class.
 */
class Mah_Download_Manager_List extends \WP_List_Table {

	/**
	 * Initialize parent class
	 */
	function __construct() {
		parent::__construct( array(
			'plural' => 'Files',
		) );
	}

	/**
	 * Make consult to database.
	 *
	 * @return void
	 */
	function prepare_items() {
		global $wpdb;

		$page       = $this->get_pagenum();
		$table_name = sprintf( '%smah_download_manager', $wpdb->prefix );
		$per_page   = $this->get_items_per_page( 'posts_per_page' );

		if ( isset( $_REQUEST[ 'number' ] ) ) {
			$number = (int) $_REQUEST[ 'number' ];
		} else {
			$number = $per_page + min( 8, $per_page );
		}

		if ( isset( $_REQUEST[ 'start' ] ) ) {
			$start = (int) $_REQUEST[ 'start' ];
		} else {
			$start = ( $page - 1 ) * $per_page;
		}

		// Can't use placeholder in table name because it adds quotes.
		$items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} ORDER BY date DESC LIMIT %d, %d", $start, $number ) );

		$this->items       = array_slice( $items, 0, $number );
		$this->extra_items = array_slice( $items, $number );

		$total = count( $items );

		$this->set_pagination_args( array(
			'total_items' => $total,
			'per_page'    => $per_page,
		) );

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

	}

	/**
	 * Define table columns.
	 *
	 * @return void
	 */
	function get_columns() {
		return array(
			'file' => esc_html__( 'File', 'mah_download' ),
			'type' => esc_html__( 'Type', 'mah_download' ),
			'size' => esc_html__( 'Size', 'mah_download' ),
			'date' => esc_html__( 'Date Added', 'mah_download' ),
		);
	}

	/**
	 * Define columns html.
	 *
	 * @param [type] $item
	 * @param [type] $column_name
	 * @return void
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'file':
?>
				<a href="<?php esc_url( $item->url ); ?>"><?php echo esc_html( $item->name ); ?></a>
<?php
				$actions = array(
					'delete' => '<a href="' . add_query_arg( array( 'action' => 'delete', 'file_id' => $item->id ) ) . '">' . esc_html__( 'Delete' ) . '</a>',
				);
				echo $this->row_actions( $actions );
				break;
			default:
				echo esc_html( $item->$column_name );
				break;
		}
	}

	/**
	 * Set download date in a format readable by humans.
	 *
	 * @param [type] $item
	 * @return void
	 */
	function column_date( $item ) {
		$m_time = $item->date;
		$time   = strtotime( $m_time );
		$time_diff = time() - $time;

		if ( 0 < $time_diff && ( 24 * 60 * 60 ) > $time_diff ) { // 24 hours, 60 minutes, 60 seconds.
			$human_time = sprintf( esc_html__( '%s ago', 'mah_download' ), human_time_diff( $time ) );
		} else {
			$human_time = mysql2date( esc_html__( 'Y/m/d', 'mah_download' ), $m_time );
		}
?>
		<abbr title="<?php echo esc_attr( $m_time ); ?>"><?php echo esc_html( $human_time ); ?></abbr>
<?php
	}
}
