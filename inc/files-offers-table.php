<?php

namespace WpCustomTables;

/**
 * Files Offers relation
 */
function create_files_offers_table() {

	$files_offers_table = new Wp_Custom_Tables_Structure( 'files_offers' );
	$files_offers_table->create_table( [
		'global' => false,
		'engine' => 'InnoDB',
		'schema' => [
			'id'       => [
				'type'           => 'int',
				'length'         => '11',
				'auto_increment' => true,
				'primary_key'    => true,
				'nullable'       => false,
			],
			'offer_id' => [
				'type'     => 'int',
				'length'   => '11',
				'nullable' => false,
			],
			'file_id'  => [
				'type'     => 'int',
				'length'   => '11',
				'nullable' => false,
			],
		],
	] );
}
