<?php
    /**
     * Class to manage List Tables
     */
    class Mah_Download_Manager_List extends WP_List_Table {

        function __construct() {
            parent::__construct( array(
              'plural' => 'Files'
            ) );
        }

        function prepare_items() {
            global $wpdb;
            $page = $this->get_pagenum();
            $table_name = $wpdb->prefix . "mah_download_manager";
            $per_page = $this->get_items_per_page( 'posts_per_page' );

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

            $items = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY date DESC LIMIT $start, $number" );

            $this->items = array_slice( $items, 0, $number );
            $this->extra_items = array_slice( $items, $number );

            $total = count( $items );

            $this->set_pagination_args( array(
                'total_items' => $total,
                'per_page' => $per_page
            ) );

            $this->_column_headers = array(
                $this->get_columns(),
                array(),
                $this->get_sortable_columns()
            );

        }

        function get_columns() {
            return array(
                'file' => __( 'File', 'mah_download_manager' ),
                'type' => __( 'Type', 'mah_download_manager' ),
                'size' => __( 'Size', 'mah_download_manager' ),
                'date' => __( 'Date Added', 'mah_download_manager' )
            );
        }

        function column_default( $item, $column_name ) {
            switch ( $column_name ) {
                case 'file':
                    echo '<a href="' . $item->url . '">' . $item->name . '</a>';
                    break;
                default:
                    echo $item->$column_name;
                    break;
            }
        }

        function column_date( $item ) {
            $m_time = $item->date;
            $time = strtotime( $m_time );
            $time_diff = time() - $time;
            if ( $time_diff > 0 && $time_diff < 24*60*60 ) {
                $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
            } else {
                $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
            }
            echo '<abbr title="' . $m_time . '">' . $h_time . '</abbr>';
        }
    }
