<?php

/**
 *
 * The class for managing Wordpress custom table structure
 *
 * @since      1.0.0
 * @package    Wp_Custom_Tables
 * @subpackage Wp_Custom_Tables/inc
 * @author     Capgemini MACS PL
 */

namespace WpCustomTables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
class Wp_Custom_Tables_Structure extends Wp_Custom_Tables {

	/**
	 * Table primary key
	 *
	 * @var string
	 */
	public $primary_key = '';

	 /**
	 * Database version
	 *
	 * @var int
	 */
	protected $version = 0;

	 /**
	 * Is this table for a site, or global
	 *
	 * @var boolean
	 */
	public $global = false;

	/**
	 * Table schema
	 *
	 * @var CT_DataBase_Schema
	 */
	public $schema = '';

	/**
	 * Database engine for table (default InnoDB)
	 *
	 * @var string
	 */
	public $engine = '';

	 /**
	 * Current database version
	 *
	 * @var string
	 */
	protected $db_version = 0;

  /**
	 * Create the table
	 *
	 * @since 1.0.0
	 *
	 * @param Array     $args         Array of arguments for the table
	 */
	public function create_table( $args ) {

		if ( $this->table_exists() ) {
			return false;
		}

		$this->version = 1;

		$this->global = ( isset( $args['global'] ) && $args['global'] === true ) ? true : false;

		$result = $this->create_or_upgrade( 'create', $args );

		return $result;
	}

	/**
	 * Upgrade the table
	 *
	 * @since 1.0.0
	 *
	 * @param Array     $args         Array of arguments for the table
	 */
	public function upgrade_table( $args ) {

		if ( ! $this->table_exists() ) {
			return false;
		}

		$this->version = ( isset( $args['version'] ) ) ? $args['version'] : 1;

		$this->global = ( isset( $args['global'] ) && $args['global'] === true ) ? true : false;

		// Bail if no upgrade needed
		$this->get_db_version( );
		if ( version_compare( (int) $this->db_version, (int) $this->version, '>=' ) ) {
			return false;
		}

		$result = $this->create_or_upgrade( 'upgrade', $args );

		return $result;
	}

	/**
	 * Check if the specified table exists in database
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	private function table_exists() {
		$table_exist = $this->wpdb->get_var( $this->wpdb->prepare( "SHOW TABLES like %s", $this->table_name ) );
		return ! empty( $table_exist );
	}

	/**
	 * Get the table version from the database.
	 *
	 * Gets global table version from "wp_sitemeta" to the main network
	 *
	 * @since 1.0.0
	 */
	private function get_db_version() {
		$this->db_version = ( true === $this->global ) ? get_network_option( null, $this->db_version_key, false ) : get_option( $this->db_version_key, false );
	}

	/**
	 * Set the database version to the table version.
	 *
	 * Saves global table version to "wp_sitemeta" to the main network
	 *
	 * @since 1.0.0
	 */
	private function set_db_version() {

		// Set the class version
		$this->db_version = $this->version;

		// Update the DB version
		( true === $this->global ) ? update_network_option( null, $this->db_version_key, $this->version ) : update_option( $this->db_version_key, $this->version );
	}

  /**
	 * Create or upgrade the table
	 *
	 * @since 1.0.0
	 *
	 * @param String    $action       'create' or 'upgrade'
	 * @param Array     $args         Array of arguments for the table
	 */
	private function create_or_upgrade( $action, $args ) {

		$this->engine = ( isset( $args['engine'] ) ) ? $args['engine'] : 'InnoDB';

		$this->primary_key = ( isset( $args['primary_key'] ) ) ? $args['primary_key'] : '';

		$this->schema = ( isset( $args['schema'] ) ) ? new Wp_Custom_Tables_Schema( $args['schema'] ) : '';

		// If not primary key given, then look at out schema
		if( $this->schema && ! $this->primary_key ) {
			foreach ( $this->schema->fields as $field_id => $field_args ) {
				if( $field_args['primary_key'] === true ) {
					$this->primary_key = $field_id;
					break;
				}
			}
		}

		// Include file with dbDelta() for create/upgrade usages
		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		// Bail if global and upgrading global tables is not allowed
		if ( ( true === $this->global ) && ! wp_should_upgrade_global_tables() ) {
			return;
		}

		// Create or upgrade
		$result = false;
		if ( 'create' === $action ) {
			$result = $this->create();

		} else if ( 'upgrade' === $action ) {
			$result = $this->upgrade();
		}

		if ( $result ) {
			// Set the database version
			if ( $this->table_exists() ) {
				$this->set_db_version();
			}
		}

		return $result;
	}

	/**
	 * Create the table
	 *
	 * @since  1.0.0
	 *
	 * @return boolean
	 */
	private function create() {

		// Run CREATE TABLE query
		$created = dbDelta( "CREATE TABLE {$this->table_name} ( {$this->schema} ) ENGINE={$this->engine} {$this->charset};" );

		return ! empty( $created );
	}

	/**
	 * Upgrade this database table
	 *
	 * @since 1.0.0
	 */
	protected function upgrade() {
		$schema_updater = new Wp_Custom_Tables_Schema_Updater( $this );
		$result = $schema_updater->run();

		return $result;
	}

}
