<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\API;

use Inpsyde\MultilingualPress\Database\Table;
use wpdb;

/**
 * Languages API implementation using the WordPress database object.
 *
 * @package Inpsyde\MultilingualPress\API
 * @since   3.0.0
 */
final class WPDBLanguages implements Languages {

	/**
	 * @var string[]
	 */
	private $comparison_operators = [
		'=',
		'<=>',
		'>',
		'>=',
		'<',
		'<=',
		'LIKE',
		'!=',
		'<>',
		'NOT LIKE',
	];

	/**
	 * @var wpdb
	 */
	private $db;

	/**
	 * @var string[]
	 */
	private $fields;

	/**
	 * @var string
	 */
	private $table;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param Table $table Site relations table object.
	 */
	public function __construct( Table $table ) {

		$this->table = $table->name();

		$this->db = $GLOBALS['wpdb'];

		$this->fields = $this->extract_field_specifications_from_table( $table );
	}

	/**
	 * Returns an array with objects of all available languages.
	 *
	 * @since 3.0.0
	 *
	 * @return object[] The array with objects of all available languages.
	 */
	public function get_all_languages() {

		$query = "SELECT * FROM {$this->table} ORDER BY priority DESC, english_name ASC";

		$result = $this->db->get_results( $query );

		return is_array( $result ) ? $result : [];
	}

	/**
	 * Returns the desired field value of the language with the given HTTP code.
	 *
	 * @since 3.0.0
	 *
	 * @param string          $http_code Language HTTP code.
	 * @param string          $field     Optional. The field which should be queried. Defaults to 'native_name'.
	 * @param string|string[] $fallbacks Optional. Falback language fields. Defaults to native and English name.
	 *
	 * @return string|string[] The desired field value, an empty string on failure, or an array for field 'all'.
	 */
	public function get_language_by_http_code(
		$http_code,
		$field = 'native_name',
		$fallbacks = [
			'native_name',
			'english_name',
		]
	) {

		$query = $this->db->prepare( "SELECT * FROM {$this->table} WHERE http_name = %s LIMIT 1", $http_code );

		$results = $this->db->get_row( $query, ARRAY_A );

		if ( 'all' === $field ) {
			return is_array( $results ) ? $results : [];
		}

		foreach ( array_unique( array_merge( (array) $field, (array) $fallbacks ) ) as $key ) {
			if ( ! empty( $results[ $key ] ) ) {
				return (string) $results[ $key ];
			}
		}

		return '';
	}

	/**
	 * Returns all languages according to the given arguments.
	 *
	 * @since 3.0.0
	 *
	 * @param array $args Arguments.
	 *
	 * @return object[] The array with objects of all languages according to the given arguments.
	 */
	public function get_languages( array $args = [] ) {

		$args = array_merge( [
			'conditions' => [],
			'fields'     => [],
			'number'     => 0,
			'order_by'   => [
				[
					'field' => 'priority',
					'order' => 'DESC',
				],
				[
					'field' => 'english_name',
					'order' => 'ASC',
				],
			],
			'page'       => 1,
		], $args );

		$fields = $this->get_fields( $args );

		$where = $this->get_where( $args );

		$order_by = $this->get_order_by( $args );

		$limit = $this->get_limit( $args );

		$query = "SELECT $fields FROM {$this->table} $where $order_by $limit";

		$results = $this->db->get_results( $query );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Updates the given languages.
	 *
	 * @since 3.0.0
	 *
	 * @param array $languages An array with language IDs as keys and one or more fields as values.
	 *
	 * @return int The number of updated languages.
	 */
	public function update_languages_by_id( array $languages ) {

		$updated = 0;

		foreach ( $languages as $id => $language ) {
			$updated += (int) $this->db->update(
				$this->table,
				(array) $language,
				[ 'ID' => $id ],
				$this->get_field_specifications( $language ),
				'%d'
			);
		}

		return $updated;
	}

	/**
	 * Returns an array with column names as keys and the individual printf conversion specification as value.
	 *
	 * There are a lot more conversion specifications, but we don't need more than telling a string from an int.
	 *
	 * @param Table $table Table object.
	 *
	 * @return string[] The array with column names as keys and the individual printf conversion specification as value.
	 */
	private function extract_field_specifications_from_table( Table $table ) {

		$numeric_types = implode( '|', [
			'BIT',
			'DECIMAL',
			'DOUBLE',
			'FLOAT',
			'INT',
			'NUMERIC',
			'REAL',
		] );

		$schema = $table->schema();

		return array_combine( array_keys( $schema ), array_map( function ( $definition ) use ( $numeric_types ) {

			return preg_match( '/^\s*[A-Z]*(' . $numeric_types . ')/', $definition ) ? '%d' : '%s';
		}, $schema ) );
	}

	/**
	 * Returns an array with the according specifications for all fields included in the given language.
	 *
	 * @param array $language Language data.
	 *
	 * @return array The array with the according specifications for all fields included in the given language.
	 */
	private function get_field_specifications( array $language ) {

		return array_map( function ( $field ) {

			return isset( $this->fields[ $field ] ) ? $this->fields[ $field ] : '%s';
		}, array_keys( $language ) );
	}

	/**
	 * Returns the according string with all valid fields included in the given arguments, or '*' if none.
	 *
	 * @param array $args Arguments.
	 *
	 * @return string The according string with all valid fields included in the given arguments, or '*' if none.
	 */
	private function get_fields( array $args ) {

		if ( ! empty( $args['fields'] ) ) {
			$allowed_fields = array_intersect( (array) $args['fields'], array_keys( $this->fields ) );
			if ( $allowed_fields ) {
				return implode( ', ', esc_sql( $allowed_fields ) );
			}
		}

		return '*';
	}

	/**
	 * Returns the according LIMIT string for the number and page values included in the given arguments.
	 *
	 * @param array $args Arguments.
	 *
	 * @return string The according LIMIT string for the number and page values included in the given arguments.
	 */
	private function get_limit( array $args ) {

		if ( ! empty( $args['number'] ) && 0 < $args['number'] ) {
			$number = (int) $args['number'];

			$start = ( empty( $args['page'] ) && 2 > $args['page'] )
				? 0
				: ( $args['page'] - 1 ) * $number;

			$end = $start + $number;

			return "LIMIT $start, $end";
		}

		return '';
	}

	/**
	 * Returns the according ORDER BY string for all valid fields included in the given arguments.
	 *
	 * @param array $args Arguments.
	 *
	 * @return string The according ORDER BY string for all valid fields included in the given arguments.
	 */
	private function get_order_by( array $args ) {

		if ( ! empty( $args['order_by'] ) ) {
			$order_by = array_filter( (array) $args['order_by'], [ $this, 'is_array_with_valid_field' ] );
			if ( $order_by ) {
				$order_by = array_map( function ( array $order_by ) {

					$order = empty( $order_by['order'] ) || 'DESC' !== strtoupper( $order_by['order'] )
						? 'ASC'
						: 'DESC';

					return "{$order_by['field']} $order";
				}, $order_by );

				return 'ORDER BY ' . implode( ', ', $order_by );
			}
		}

		return '';
	}

	/**
	 * Returns the according WHERE string for all valid conditions included in the given arguments.
	 *
	 * @param array $args Arguments.
	 *
	 * @return string The according WHERE string for all valid conditions included in the given arguments.
	 */
	private function get_where( array $args ) {

		if ( ! empty( $args['conditions'] ) ) {
			$conditions = array_filter( (array) $args['conditions'], function ( $condition ) {

				return
					$this->is_array_with_valid_field( $condition )
					&& (
						empty( $condition['compare'] )
						|| in_array( $condition['compare'], $this->comparison_operators, true )
					)
					&& ! empty( $condition['value'] );
			} );
			if ( $conditions ) {
				$conditions = array_map( function ( array $condition ) {

					return $this->db->prepare(
						"{$condition['field']} {$condition['compare']} {$this->fields[ $condition['field'] ]}",
						$condition['value']
					);
				}, $conditions );

				return 'WHERE ' . implode( ' AND ', $conditions );
			}
		}

		return '';
	}

	/**
	 * Checks if the given element is an array that has a valid field element.
	 *
	 * @param mixed $maybe_array Maybe an array
	 *
	 * @return bool Whether or not the given element is an array that has a valid field element.
	 */
	private function is_array_with_valid_field( $maybe_array ) {

		return
			is_array( $maybe_array )
			&& ! empty( $maybe_array['field'] )
			&& in_array( $maybe_array['field'], array_keys( $this->fields ) );
	}
}
