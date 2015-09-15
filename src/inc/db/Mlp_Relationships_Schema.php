<?php # -*- coding: utf-8 -*-

/**
 * SQL table schema for the Relationships table.
 */
class Mlp_Relationships_Schema implements Mlp_Db_Schema_Interface {

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

		return $this->wpdb->base_prefix . 'mlp_relationships';
	}

	/**
	 * Return the table schema.
	 *
	 * @return array
	 */
	public function get_schema() {

		return array(
			'id'   => 'BIGINT(20) NOT NULL AUTO_INCREMENT',
			'type' => 'VARCHAR(20) NOT NULL',
		);
	}

	/**
	 * Return the primary key.
	 *
	 * @return string
	 */
	public function get_primary_key() {

		return 'id';
	}

	/**
	 * Return the array of autofilled keys.
	 *
	 * @return array
	 */
	public function get_autofilled_keys() {

		return array(
			'id',
		);
	}

	/**
	 * Return the SQL string for any indexes and unique keys.
	 *
	 * @return string
	 */
	public function get_index_sql() {

		// Due to dbDelta: KEY (not INDEX), and space before but no spaces inside brackets!
		return 'UNIQUE KEY id_type (id,type)';
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
