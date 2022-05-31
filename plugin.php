<?php
/**
 * Plugin Name: Capgemini WP Custom Tables
 * Description: It's a plugin that facilitates the management of custom tables in wordpress
 * Plugin URI: https://capgemini.com
 * Author: Capgemini MACS PL
 * Author URI: https://capgemini.com
 * License: GPLv2
 */

namespace WpCustomTables;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Abstract class with common methods
 */
require plugin_dir_path( __FILE__ ) . 'inc/abstract-class-wp-custom-tables.php';

/**
 * Class for creating and changing the structure of custom tables
 */
require plugin_dir_path( __FILE__ ) . 'inc/class-wp-custom-tables-structure.php';

/**
 * Class for creating and changing the structure of custom tables
 */
require plugin_dir_path( __FILE__ ) . 'inc/class-wp-custom-tables-data.php';

/**
 * Library classes
 */
require plugin_dir_path( __FILE__ ) . 'inc/class-wp-custom-tables-schema.php';
require plugin_dir_path( __FILE__ ) . 'inc/class-wp-custom-tables-schema-updater.php';
