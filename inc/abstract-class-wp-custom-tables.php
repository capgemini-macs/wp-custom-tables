<?php

/**
 *
 * The class for managing WordPress custom tables
 *
 * @since      1.0.0
 * @package    Wp_Custom_Tables
 * @subpackage Wp_Custom_Tables/inc
 * @author     Capgemini MACS PL
 */

namespace WpCustomTables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

abstract class Wp_Custom_Tables {

	/**
	 * The current table name
	 *
	 * @var string
	 */
	public $table_name = null;

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @var string
	 */
	protected $db_version_key = '';

	/**
	 * @var object
	 */
	public $wpdb = null;

	/**
	 * Prefix for the table
	 *
	 * @var string
	 */
	protected $prefix = null;

	/**
	 * Charset of the table
	 *
	 * @var string
	 */
	protected $charset = null;

	/**
	 * Constructor for the class
	 *
	 * @since 1.0.0
	 *
	 * @param String    $table_name   The table name
	 */
	public function __construct( $table_name ) {

		if ( empty( $table_name ) ) {
			return;
		}

		global $wpdb;
		$this->wpdb    = $wpdb;
		$this->prefix  = $wpdb->prefix;
		$this->charset = $wpdb->get_charset_collate();

		$this->set_table_name( $table_name );
		$this->set_db_version_key();
	}

	/**
	 * Get table name with prefix
	 *
	 * @since 1.0.0
	 *
	 * @param String    $table_name   The table name
	 */
	protected function set_table_name( $table_name ) {

		$prefix_len = strlen( $this->prefix );
		if ( $prefix_len > 0 ) {
			if ( substr( $table_name, 0, $prefix_len ) === $this->prefix ) {
				$this->table_name = $table_name;
			} else {
				$this->table_name = $this->prefix . $table_name;
			}
		} else {
			$this->table_name = $table_name;
		}
	}

	/**
	 * Set db version key
	 *
	 * @since 1.0.0
	 */
	private function set_db_version_key() {
		$this->db_version_key = "{$this->table_name}_version";
	}

	/**
	 * Get list of columns available in $this->table_name;
	 * @since  1.0.0
	 * @return array of column names
	 */
	public function get_columns() {
		return $this->wpdb->get_col( "DESCRIBE $this->table_name", 0 );
	}

}
