<?php

/**
 *
 * The class for managing data in Wordpress custom table
 *
 * @since      1.0.0
 * @package    Wp_Custom_Tables
 * @subpackage Wp_Custom_Tables/inc
 * @author     Capgemini MACS PL
 */

namespace WpCustomTables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
class Wp_Custom_Tables_Data extends Wp_Custom_Tables {

	/**
	 * Get all from the selected table
	 *
	 * @since  1.0.0
	 * @param  string    $orderby    The column for ordering base
	 * @param  string    $order      Order key eq. ASC or DESC
	 * @return array     $results    The query results
	 */
	public function get_all( $orderby = NULL, $order = NULL, $output_type = OBJECT, $limit = NULL, $offset = NULL ) {

		$cache_key = 'custom_table_' . $this->table_name . '_get_all';
		$cache = wp_cache_get( $cache_key, 'custom_tables' );
		if ( false !== $cache ) {
			return $cache;
		}

		$sql = "SELECT * FROM $this->table_name";

		if ( null !== $orderby ) {

			$orderby = $this->check_column( $orderby );
			$order   = null !== $order ? $this->check_order( $order ) : null;

			if ( $orderby ) {
				$sql .= " ORDER BY $orderby";
				if ( $order ) {
					$sql .= " $order";
				}
			}
		}

		if ( null !== $limit ) {
			$sql .= " LIMIT $limit";
		}

		if ( null !== $offset ) {
			$sql .= " OFFSET $offset";
		}

		$results = $this->wpdb->get_results( $sql, $output_type );

		wp_cache_set( $cache_key, $results, 'custom_tables', 3600 );

		return $results;

	}

	/**
	 * Get all data of single row from a column that contain specific data
	 *
	 * @since 1.0.0
	 * @param string           $column         The column name
	 * @param string           $value          the value to check for
	 * @param string           $format         $wpdb->prepare() string format
	 * @param constant         $output_type    One of three pre-defined constants. Defaults to OBJECT.
	 * @param int              $row_offset     The desired row (0 being the first). Defaults to 0.
	 * @return object|array    $results        Query results
	 *
	 */
	public function get_row( $column, $value, $format, $output_type = OBJECT, $row_offset = 0 ) {
		$format  = $this->check_format( $format );
		$column  = $this->check_column( $column );

		if ( ! $column || ! $format ) {
			return;
		}

		$sql     = $this->wpdb->prepare( "SELECT * FROM $this->table_name WHERE `$column` = $format", $value );
		$results = $this->wpdb->get_row( $sql, $output_type, $row_offset );

		return $results;
	}

	/**
	 * Get a value by a single condition
	 *
	 * @since  1.0.0
	 * @param  string|array    $column         List of column to be returned.
	 * @param  string          $field          The column name used in WHERE clause as the query condition
	 * @param  string|array    $value          The column value used for the condition expression in WHERE clause.
	 *                                         If WHERE clause operator used IN, BETWEEN, etc. this require multiple values specified in array.
	 * @param  string          $operator       The condition expression operator in WHERE clause
	 * @param  string          $format         The data format for $value
	 * @param  string          $orderby        The column for ordering base
	 * @param  string          $order          Order key eq. ASC or DESC
	 * @param  const           $output_type    Type constant OBJECT|ARRAY_A|ARRAY_N
	 * @return array           $result
	 */
	public function get_by( $column, $field, $value, $operator = '=', $format = '%s', $orderby = NULL, $order = 'ASC', $output_type = OBJECT ) {

		$operator = $this->check_operator( $operator );
		$format   = $this->check_format( $format );
		$column   = $this->check_column( $column );

		$sql = "SELECT $column FROM $this->table_name WHERE";

		$method = 'sql_' . strtolower( str_replace( ' ', '_', $operator ) );

		if ( method_exists( $this, $method ) ) {
			$sql .= call_user_func( array( $this, $method ), $field, $value, $format, false );
		} else {
			$sql .= $this->sql_default( $field, $value, $operator, $format, false );
		}

		if ( null !== $orderby ) {
			$orderby = $this->check_column( $orderby );
			$order   = null !== $order ? $this->check_order( $order ) : null;

			if ( $orderby ) {
				$sql .= " ORDER BY $orderby";
				if ( $order ) {
					$sql .= " $order";
				}
			}
		}

		$result = $this->wpdb->get_results( $sql, $output_type );

		return $result;
	}

	/**
	 * Get a value by multiple conditions, operators and formats
	 *
	 * @since  1.0.0
	 * @param  string|array    $column         List of column to be returned.
	 * @param  array           $conditions     Set of conditions to used in WHERE clause
	 * @param  string          $field          The column name used in WHERE clause as the query condition
	 * @param  string          $value          The column value used for the condition expression in WHERE clause
	 * @param  string|array    $operator       The condition expression operator in WHERE clause.
	 *                                         If it's string it will be used to all condition fields.
	 *                                         Used array key value pair for defining different operator for each of the $conditions key.
	 * @param  string|array    $format         The data format for $value
	 *                                         If it's string it will be used to all condition fields value.
	 *                                         Used array key value pair for defining different format for each of the $conditions value.
	 * @param  string          $orderby        The column for ordering base
	 * @param  string          $order          Order key eq. ASC or DESC
	 * @param  const           $output_type    Type constant OBJECT|ARRAY_A|ARRAY_N
	 * @return array           $result
	 */
	public function get_wheres( $column = '', Array $conditions, $operator = '=', $format = '%s', $orderby = NULL, $order = 'ASC', $output_type = OBJECT ) {

		$operator = $this->check_operator( $operator );
		$format   = $this->check_format( $format );
		$column   = $this->check_column( $column );

		$sql = "SELECT $column FROM $this->table_name WHERE 1=1";

		$i = 0;

		foreach ( $conditions as $field => $value ) {

			if ( !$value ) {
				$i++;
				continue;
			}

			if ( is_array( $operator ) ) {
				if ( isset( $operator[$field] ) ) {
					$op = $operator[$field];
				} else if ( isset( $operator[$i] ) ) {
					$op = $operator[$i];
				} else {
					$op = '=';
				}
			} else {
				$op = $operator;
			}

			if ( is_array( $format ) ) {
				if ( isset( $format[$field] ) ) {
					$f = $format[$field];
				} else if ( isset( $format[$i] ) ) {
					$f = $format[$i];
				} else {
					$f = '%s';
				}
			} else {
				$f = $format;
			}

			$method = 'sql_' . strtolower( str_replace( ' ', '_', $op ) );

			if ( method_exists( $this, $method ) ) {
				$sql .= call_user_func( array( $this, $method ), $field, $value, $f, true );
			} else {
				$sql .= $this->sql_default( $field, $value, $op, $f, true );
			}

			$i++;
		}

		if ( null !== $orderby ) {
			$orderby = $this->check_column( $orderby );
			$order   = null !== $order ? $this->check_order( $order ) : null;

			if ( $orderby ) {
				$sql .= " ORDER BY $orderby";
				if ( $order ) {
					$sql .= " $order";
				}
			}
		}

		$result = $this->wpdb->get_results( $sql, $output_type );
		return $result;
	}

	/**
	 * Count a table record in the table
	 *
	 * @since  1.0.0
	 * @param  int $column_offset
	 * @param  int $row_offset
	 * @return int number of the count
	 */
	public function count( $column_offset = 0, $row_offset = 0 ) {

		$cache_key = 'custom_table_' . $this->table_name . '_count';
		$cache = wp_cache_get( $cache_key, 'custom_tables' );
		if ( false !== $cache ) {
			return $cache;
		}

		$sql = "SELECT COUNT(*) FROM $this->table_name";

		$result = $this->wpdb->get_var( $sql, $column_offset, $row_offset );

		wp_cache_set( $cache_key, $result, 'custom_tables', 3600 );

		return $result;
	}

	/**
	 * count a record in the column
	 *
	 * @since  1.0.0
	 * @param  string $column         Column name in table
	 * @param  const  $output_type    Type constant OBJECT|ARRAY_A|ARRAY_N
	 *
	 * @return array  $returns  Array set of counts per column
	 */
	public function count_column( $column, $output_type = ARRAY_A ) {

		$column = $this->check_column( $column );

		$sql    = "SELECT $column, COUNT(*) AS count FROM $this->table_name GROUP BY $column";

		$totals = $this->wpdb->get_results( $sql, $output_type );

		$returns = array();
		$all = 0;

		foreach ( $totals as $row ) {
			$all = $all + $row['count'];
			$returns[$row[$column]] = $row['count'];
		}

		$returns['all'] = $all;

		return $returns;
	}

	/**
	 * Insert data into the current table
	 *
	 * @since  1.0.0
	 * @param  array  $data array( 'column' => 'values' ) - Data to enter into the database table
	 * @return int    The row ID
	 *
	 */
	public function insert( Array $data ) {
			if ( empty( $data ) ) {
					return false;
			}

			$this->wpdb->insert( $this->table_name, $data );

			$this->clear_cache();

			return $this->wpdb->insert_id;
	}

	/**
	 * Update a table record in the database
	 *
	 * @since  1.0.0
	 * @param  array       $data       A named array of WHERE clauses (in column => value pairs).
	 *                                 Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @param  array       $condition  Key value pair for the where clause of the query.
	 *                                 Multiple clauses will be joined with ANDs. Both $where columns and $where values should be "raw".
	 * @return int|boolean $updated    This method returns the number of rows updated, or false if there is an error.
	 */
	public function update( Array $data, Array $condition ) {
			if ( empty( $data ) ) {
					return false;
			}

			$updated = $this->wpdb->update( $this->table_name, $data, $condition );

			$this->clear_cache();

			return $updated;
	}

	/**
	 * Delete row on the database table
	 *
	 * @since  1.0.0
	 * @param  array  $conditionValue - Key value pair for the where clause of the query
	 * @return int Num rows deleted
	 */
	public function delete( Array $condition ) {
		$deleted = $this->wpdb->delete( $this->table_name, $condition );

		$this->clear_cache();

		return $deleted;
	}

	/**
	 * Delete rows on the database table
	 *
	 * @since  1.0.0
	 * @param  string   $field            The table column name
	 * @param  array    $conditionvalue   The value to be deleted
	 * @param  string   $format           $wpdb->prepare() Format String
	 * @return $deleted
	 *
	 */
	public function bulk_delete( $field, array $conditionvalue, $format = '%s' ) {

		$format = $this->check_format( $format );

		// how many entries will we select?
		$how_many = count( $conditionvalue );

		// prepare the right amount of placeholders
		// if you're looing for strings, use '%s' instead
		$placeholders = array_fill( 0, $how_many, $format );

		// glue together all the placeholders...
		// $format = '%s, %s, %s, %s, %s, [...]'
		$format = implode( ', ', $placeholders );

		$sql = "DELETE FROM $this->table_name WHERE $field IN ($format)";
		$sql = $this->wpdb->prepare( $sql, $conditionvalue );

		$deleted = $this->wpdb->query( $sql );

		$this->clear_cache();

		return $deleted;
	}

		/**
	 * Get supported operands
	 *
	 * @since  1.0.0
	 * @return array    List of all supported operands
	 *
	 */
	protected function clear_cache() {
		wp_cache_delete( 'custom_table_' . $this->table_name . '_get_all', 'custom_tables' );
		wp_cache_delete( 'custom_table_' . $this->table_name . '_count', 'custom_tables' );
	}

	/**
	 * Get supported operands
	 *
	 * @since  1.0.0
	 * @return array    List of all supported operands
	 *
	 */
	protected function get_operands() {
		return apply_filters( __METHOD__,
			array(
							'=',
							'!=',
							'>',
							'<',
							'>=',
							'<=',
							'<=>',
							'like',
							'not like',
							'in',
							'not in',
							'between',
							'not between',
					)
			);
	}

	/**
	 * check/sanitize column parameter to make sure the column is available in $this->table_name.
	 *
	 * @return array|string    Return the Array of sanitized columns or string of commas separated column name.
	 */
	protected function check_column( $columns, $return = 'string' ) {
		if ( is_array( $columns ) ) {
			foreach( $columns as $key => $value ) {
				if ( !in_array( $value, $this->get_columns() ) ) {
					unset( $columns[$key] );
				}
			}

			if ( !empty( $columns ) ) {
				if ( $return == 'string' ) {
					return implode( ',', $columns );
				} else {
					return $columns;
				}
			} else {
				return '*';
			}

		} else {
			if ( $columns === '*' ) {
				return $columns;
			}
			if ( in_array( $columns, $this->get_columns() ) ) {
				return $columns;
			} else {
				return '*';
			}
		}
	}

	/**
	 * check/sanitize ORDER string
	 *
	 * @since  1.0.0
	 * @return string    order string ASC|DESC
	 *
	 */
	protected function check_order( $order = 'ASC' ) {
		if ( is_null( $order ) ) {
			return 'ASC';
		} else {
			$order = in_array( $order, array( 'ASC', 'DESC' ) ) ? $order : 'ASC';
			return $order;
		}
	}

	/**
	 * check/sanitize operator string.
	 *
	 * @since  1.0.0
	 * @param  string|array    Array of operators or single operator string to be check.
	 * @return string|array    The operator that pass the check.
	 * @uses   Bolts\Core\Libraries\Db::get_operands()
	 *
	 */
	protected function check_operator( $operator ) {

		$operators = array();

		if ( is_array( $operator ) ) {
			foreach( $operator as $k => $op ) {
					$operators[$k] = $this->check_operator( $op );
			}
			return $operators;
		} else {
			$operator = ( in_array( $operator, $this->get_operands() ) ? strtoupper( $operator ) : '=' );
			return $operator;
		}
	}

	/**
	 * check/sanitize format string
	 *
	 * @since  1.0.0
	 * @param  string|array    The array of formats or single format string need to be check.
	 * @return string|array    The Array of checked formats or single checked format string.
	 *
	 */
	protected function check_format( $format ) {
		$formats = array();
		if ( is_array( $format ) ) {
			foreach( $format as $k => $f ) {
					$formats[$k] = $this->check_format( $f );
			}
			return $formats;
		} else {
			$format = ( in_array( $format, array( '%s', '%d', '%f' ) ) ? $format : '%s' );
			return $format;
		}
	}

	/**
	 * Append IN clause for sql query via $wpdb->prepare
	 *
	 * @since  1.0.0
	 * @param  string    $column    The Column Name
	 * @param  array     $value     The array values for the WHERE clause
	 * @param  string    $format    Single format string for prepare.
	 * @param  boolean   $and       before the statement prepend AND if true, prepend OR if $and === 'OR', prepend nothing if false
	 * @return string    $sql       The prepared sql statement
	 *
	 */
	protected function sql_in( $column, Array $value, $format = '%s', $and = true ) {

		$how_many     = count( $value );
		$placeholders = array_fill( 0, $how_many, $format );
		$new_format   = implode( ', ', $placeholders );

		$sql  = $this->sql_and( $and );
		$sql .= " `$column` IN ($new_format)";
		$sql  = $this->wpdb->prepare( $sql, $value );

		return $sql;
	}

	/**
	 * Append NOT IN clause for sql query via $wpdb->prepare
	 *
	 * @since  1.0.0
	 * @param  string    $column    The Column Name
	 * @param  array     $value     The array values for the WHERE clause
	 * @param  string    $format    Single format string for prepare.
	 * @param  boolean   $and       before the statement prepend AND if true, prepend OR if $and === 'OR', prepend nothing if false
	 * @return string    $sql       The prepared sql statement
	 *
	 */
	protected function sql_not_in( $column, Array $value, $format = '%s', $and = true ) {

		$how_many     = count( $value );
		$placeholders = array_fill( 0, $how_many, $format );
		$new_format   = implode( ', ', $placeholders );

		$sql  = $this->sql_and( $and );
		$sql .= " `$column` NOT IN ($new_format)";
		$sql  = $this->wpdb->prepare( $sql, $value );

		return $sql;
	}

	/**
	 * Append BETWEEN clause for sql query via $wpdb->prepare
	 *
	 * @since  1.0.0
	 * @param  string    $column    The Column Name
	 * @param  array     $value     The array values for the WHERE clause
	 * @param  string    $format    Single format string for prepare.
	 * @param  boolean   $and       before the statement prepend AND if true, prepend OR if $and === 'OR', prepend nothing if false
	 * @return string    $sql       The prepared sql statement
	 *
	 */
	protected function sql_between( $column, Array $value, $format = '%s', $and = true ) {
		if ( count( $value ) < 2 ) {
			throw new \Exception( 'Values for BETWEEN query must be more than one.', 1 );
		}

		$sql  = $this->sql_and( $and );
		$sql .= $this->wpdb->prepare( " `$column` BETWEEN $format AND $format", $value[0], $value[1] );

		return $sql;
	}

	/**
	 * Append NOT BETWEEN clause for sql query via $wpdb->prepare
	 *
	 * @since  1.0.0
	 * @param  string    $column    The Column Name
	 * @param  array     $value     The array values for the WHERE clause
	 * @param  string    $format    Single format string for prepare.
	 * @param  boolean   $and       before the statement prepend AND if true, prepend OR if $and === 'OR', prepend nothing if false
	 * @return string    $sql       The prepared sql statement
	 *
	 */
	protected function sql_not_between( $column, Array $value, $format = '%s', $and = true ) {
		if ( count( $value ) < 2 ) {
			throw new \Exception( 'Values for NOT BETWEEN query must be more than one.', 1 );
		}

		$sql = $this->sql_and( $and );
		$sql .= $this->wpdb->prepare( " `$column` NOT BETWEEN $format AND $format", $value[0], $value[1] );

		return $sql;
	}

	/**
	 * Append LIKE clause for sql query via $wpdb->prepare
	 *
	 * @since  1.0.0
	 * @param  string    $column    The Column Name
	 * @param  string    $value     The LIKE string values for the WHERE clause
	 * @param  string    $format    Single format string for prepare.
	 * @param  boolean   $and       before the statement prepend AND if true, prepend OR if $and === 'OR', prepend nothing if false
	 * @return string    $sql       The prepared sql statement
	 *
	 */
	protected function sql_like( $column, $value, $format = '%s', $and = true ) {
		$sql = $this->sql_and( $and );
		$sql .= $this->wpdb->prepare( " `$column` LIKE $format", $value );
		return $sql;
	}

	/**
	 * Append NOT LIKE clause for sql query via $wpdb->prepare
	 *
	 * @since  1.0.0
	 * @param  string    $column    The Column Name
	 * @param  string    $value     The LIKE string values for the WHERE clause
	 * @param  string    $format    Single format string for prepare.
	 * @param  boolean   $and       before the statement prepend AND if true, prepend OR if $and === 'OR', prepend nothing if false
	 * @return string    $sql       The prepared sql statement
	 *
	 */
	protected function sql_not_like( $column, $value, $format = '%s', $and = true ) {
		$sql = $this->sql_and( $and );
		$sql .= $this->wpdb->prepare( " `$column` NOT LIKE $format", $value );
		return $sql;
	}

	/**
	 * Append based on operator expression in WHERE clause for sql query via $wpdb->prepare
	 *
	 * @since  1.0.0
	 * @param  string    $column    The Column Name
	 * @param  string    $value     The string values for the WHERE clause
	 * @param  string    $op        The string operator for the WHERE clause. eq:=, !=, etc
	 * @param  string    $format    Single format string for prepare.
	 * @param  boolean   $and       before the statement prepend AND if true, prepend OR if $and === 'OR', prepend nothing if false
	 * @return string    $sql       The prepared sql statement
	 *
	 */
	protected function sql_default( $column, $value, $op, $format = '%s', $and = true ) {
		$sql = $this->sql_and( $and );
		$sql .= $this->wpdb->prepare( " `$column` $op $format", $value );
		return $sql;
	}


	/**
	 * get AND|OR|(empty) based on parameter
	 *
	 * @since  1.0.0
	 * @param  boolean   $and   AND if true, prepend OR if $and === 'OR', prepend nothing if false
	 * @return string
	 *
	 */
	protected function sql_and( $and = true ) {

		if ( $and === true ) {
			return " AND";
		} else if ( strtoupper( $and ) === 'OR' ) {
			return " OR";
		}

		return '';
	}

	/**
	 * Get sql
	 *
	 * @since 1.0.0
	 * @param string           	$sql         		The prepared sql statement
	 * @param array           	$placeholders   Placeholders for wpdb prepare
	 * @param constant         	$output_type    One of three pre-defined constants. Defaults to OBJECT.
	 * @param string           	$result_type    Result type: 'row' or 'results'
	 * @return object|array    	$results        Query results
	 *
	 */
	public function get_sql( $sql, $placeholders, $output_type = OBJECT, $result_type = 'row' ) {

		$sql = $this->wpdb->prepare( $sql, $placeholders );

		if ( 'row' === $result_type ) {
			$results = $this->wpdb->get_row( $sql, $output_type );
		} else {
			$results = $this->wpdb->get_results( $sql, $output_type );
		}

		return $results;
	}

}
