<?php

// ##################  Table structure management ##################

// Initiate
$custom_table = new Wp_Custom_Tables_Structure( 'logs' );

// Creating a new table
$create_table = $custom_table->create_table( [
	'global' => true,     // Is this table for a site, or global
	'engine' => 'InnoDB', // Database engine (default to InnoDB)
	//'primary_key' => 'log_id',    // If not defined will be checked on the field that has primary_key as true on schema
	'schema' => [
		'log_id' => [
			'type'           => 'bigint',
			'length'         => '20',
			'auto_increment' => true,
			'primary_key'    => true,
			'nullable'       => true,
		],
		'title'  => [
			'type'     => 'varchar',
			'length'   => '50',
			'nullable' => true,
		],
		'status' => [
			'type'     => 'varchar',
			'length'   => '50',
			'nullable' => true,
		],
		'date'   => [
			'type'     => 'datetime',
			'nullable' => true,
		],
	],
	// Also you can define schema as string
	// 'schema' => '
	//   log_id bigint(20) NOT NULL AUTO_INCREMENT,
	//   title varchar(50) NOT NULL,
	//   status varchar(50) NOT NULL,
	//   date datetime NOT NULL,
	//   PRIMARY KEY  (log_id)
	// ',
 ] );

// Upgrading the table
$upgrade_table = $custom_table->upgrade_table( [
	'version' => 2, // your version must be higher than the one currently in the database
	'schema'  => [  // provide there the  entire schema of the table with things you want to change
		// ...
	],
	// ...
] );

// ################## Table data management ##################

// Initiate table
$my_table = new Wp_Custom_Tables_Data( 'logs' );

// Get all data
$all_data = $my_table->get_all( $orderby = 'date', $order = 'ASC' );

// Get Row Data
$row_data = $table->get_row(
	$column      = 'id',
	$value       = 102,
	$format      = '%d',
	$output_type = OBJECT,
	$offset      = 10
);

// Get By Column with clause
$get_by = $my_table->get_by(
	$columns     = [ 'id', 'slug' ],
	$field       = 'id',
	$field_value = 102,
	$operator    = '=',
	$format      = '%d',
	$orderby     = 'slug',
	$order       = 'ASC',
	$output_type = OBJECT_K
);

// Get with Where clause
$get_wheres = $my_table->get_wheres(
	$column      = '*',
	$conditions  = [
		'category' => $category,
		'id'       => $id,
	],
	$operator    = '=',
	$format      = [
		'category' => '%s',
		'id'       => '%d',
	],
	$orderby     = 'category',
	$order       = 'ASC',
	$output_type = OBJECT_K
);

// Count
$count = $my_table->count();

// Count columns
$count = $my_table->count_column( 'title' );

// Insert data
$insert_id = $my_table->insert(
	[
		'title' => 'text',
		'date'  => gmdate( 'Y-m-d H:i:s' ),
	]
);

// Update data
$update = $my_table->update(
	[
		'title' => 'textaaaa',
		'date'  => gmdate( 'Y-m-d H:i:s' ),
	],
	[ 'id' => 22 ],
);

// Delete
$update = $my_table->delete(
	[ 'id' => 22 ],
);

// ################## Common methods ##################

// Get Columns List
$columns = $my_table->get_columns();
