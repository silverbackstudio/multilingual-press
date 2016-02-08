<?php # -*- coding: utf-8 -*-

/**
 * SQL table schema for the Content Relations table.
 */
class Mlp_Content_Relations_Schema implements Mlp_Db_Schema_Interface {

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor. Set up the properties.
	 *
	 * @param wpdb $wpdb Database object.
	 */
	public function __construct( wpdb $wpdb ) {

		$this->wpdb = $wpdb;
	}

	/**
	 * Return the table name.
	 *
	 * @return string
	 */
	public function get_table_name() {

		return $this->wpdb->base_prefix . 'mlp_content_relations';
	}

	/**
	 * Return the table schema.
	 *
	 * @return array
	 */
	public function get_schema() {

		return array(
			'relationship_id' => 'mediumint UNSIGNED NOT NULL',
			'site_id'         => 'bigint(20) UNSIGNED NOT NULL',
			'content_id'      => 'bigint(20) UNSIGNED NOT NULL',
		);
	}

	/**
	 * Return the primary key.
	 *
	 * @return string
	 */
	public function get_primary_key() {

		return 'relationship_id,site_id,content_id';
	}

	/**
	 * Return the array of autofilled keys.
	 *
	 * @return array
	 */
	public function get_autofilled_keys() {

		return array();
	}

	/**
	 * Return the SQL string for any indexes and unique keys.
	 *
	 * @return string
	 */
	public function get_index_sql() {

		// Due to dbDelta: KEY (not INDEX), and space before but no spaces inside brackets!
		return 'KEY site_content (site_id,content_id)';
	}

	/**
	 * Return the SQL string for any default content.
	 *
	 * @return string
	 */
	public function get_default_content() {

		return '';
	}
}
