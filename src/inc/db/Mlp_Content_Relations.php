<?php # -*- coding: utf-8 -*-

/**
 * Relationships between content elements.
 */
class Mlp_Content_Relations implements Mlp_Content_Relations_Interface {

	/**
	 * @var string
	 */
	private $cache_group = 'mlp';

	/**
	 * @var string
	 */
	private $relationships_table;

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor. Set up the properties.
	 *
	 * @param wpdb                    $wpdb                Database object.
	 * @param Mlp_Db_Schema_Interface $table               Contente Relations schema object.
	 * @param Mlp_Db_Schema_Interface $relationships_table Relationships schema object.
	 */
	public function __construct(
		wpdb $wpdb,
		Mlp_Db_Schema_Interface $table,
		Mlp_Db_Schema_Interface $relationships_table
	) {

		$this->wpdb = $wpdb;

		$this->table = $table->get_table_name();

		$this->relationships_table = $relationships_table->get_table_name();
	}

	/**
	 * Set a relation according to the given parameters.
	 *
	 * @param int $relationship_id Relationship ID.
	 * @param int $site_id         Site ID.
	 * @param int $content_id      Content ID.
	 *
	 * @return bool
	 */
	public function set_relation( $relationship_id, $site_id, $content_id ) {

		if ( $content_id === 0 ) {
			return (bool) $this->delete_relation_for_site( $relationship_id, $site_id );
		}

		$current_content_id = $this->get_content_id( $relationship_id, $site_id );
		if ( $current_content_id && $current_content_id !== $content_id ) {
			// Delete different relation of the given site
			$this->delete_relation_for_site( $relationship_id, $site_id, FALSE );
		}

		$type = $this->get_relationship_type( $relationship_id );
		if ( $type ) {
			$current_relationship_id = $this->get_relationship_id_single( $site_id, $content_id, $type );
			if ( $current_relationship_id && $current_relationship_id !== $relationship_id ) {
				// Delete different relation of the given content element
				$this->delete_relation_for_site( $current_relationship_id, $site_id, FALSE );
			}
		}

		$result = (bool) $this->insert_relation( $relationship_id, $site_id, $content_id );

		return $result;
	}

	/**
	 * Delete the relation for the given arguments.
	 *
	 * @param int  $relationship_id Relationship ID.
	 * @param int  $site_id         Site ID.
	 * @param bool $delete          Optional. Delete relationship if less than three content elements. Defaults to TRUE.
	 *
	 * @return int
	 */
	private function delete_relation_for_site( $relationship_id, $site_id, $delete = TRUE ) {

		$content_ids = $this->get_content_ids( $relationship_id );
		if (
			count( $content_ids ) < 3
			&& $delete
			&& ! empty( $content_ids[ $site_id ] )
		) {
			return $this->delete_relationship( $relationship_id );
		}

		$type = $this->get_relationship_type( $relationship_id );

		$where = compact(
			'relationship_id',
			'site_id'
		);

		$deleted_rows = (int) $this->wpdb->delete( $this->table, $where, '%d' );

		if ( isset( $content_ids[ $site_id ] ) ) {
			if ( $type ) {
				wp_cache_delete(
					$this->get_relationship_id_cache_key( $site_id, $content_ids[ $site_id ], $type ),
					$this->cache_group
				);
			}

			unset( $content_ids[ $site_id ] );

			wp_cache_set( $this->get_content_ids_cache_key( $relationship_id ), $content_ids, $this->cache_group );
		}

		return $deleted_rows;
	}

	/**
	 * Return the content IDs for the given relationship ID.
	 *
	 * @param int $relationship_id Relationship ID.
	 *
	 * @return int[]
	 */
	private function get_content_ids( $relationship_id ) {

		$cache_key = $this->get_content_ids_cache_key( $relationship_id );

		$cache = wp_cache_get( $cache_key, $this->cache_group );
		if ( is_array( $cache ) ) {
			return $cache;
		}

		$sql = "
SELECT site_id, content_id
FROM {$this->table}
WHERE relationship_id = %d";
		$query = $this->wpdb->prepare( $sql, $relationship_id );

		$rows = $this->wpdb->get_results( $query, ARRAY_A );
		if ( ! $rows ) {
			return array();
		}

		$content_ids = array();

		foreach ( $rows as $row ) {
			$content_ids[ (int) $row[ 'site_id' ] ] = (int) $row[ 'content_id' ];
		}

		wp_cache_set( $cache_key, $content_ids, $this->cache_group );

		return $content_ids;
	}

	/**
	 * Return the content IDs cache key for the given relationship ID.
	 *
	 * @param int $relationship_id Relationship ID.
	 *
	 * @return string
	 */
	private function get_content_ids_cache_key( $relationship_id ) {

		return "mlp_content_ids_{$relationship_id}";
	}

	/**
	 * Remove the relationship as well as all relations with the given relationship ID.
	 *
	 * @param int $relationship_id Relationship ID.
	 *
	 * @return int
	 */
	private function delete_relationship( $relationship_id ) {

		$content_ids = $this->get_content_ids( $relationship_id );

		$deleted_rows = (int) $this->wpdb->delete( $this->table, compact( 'relationship_id' ), '%d' );

		wp_cache_delete( $this->get_content_ids_cache_key( $relationship_id ), $this->cache_group );

		$type = $this->get_relationship_type( $relationship_id );

		foreach ( $content_ids as $site_id => $content_id ) {
			wp_cache_delete( $this->get_relationship_id_cache_key( $site_id, $content_id, $type ), $this->cache_group );
		}

		$this->wpdb->delete( $this->relationships_table, array( 'id' => $relationship_id ), '%d' );

		wp_cache_delete( $this->get_relationship_type_cache_key( $relationship_id ), $this->cache_group );

		return $deleted_rows;
	}

	/**
	 * Return the content type for the relationship with the given ID.
	 *
	 * @param int $relationship_id Relationship ID.
	 *
	 * @return string
	 */
	private function get_relationship_type( $relationship_id ) {

		$cache_key = $this->get_relationship_type_cache_key( $relationship_id );

		$cache = wp_cache_get( $cache_key, $this->cache_group );
		if ( ! empty( $cache ) ) {
			return $cache;
		}

		$sql = "
SELECT type
FROM {$this->relationships_table}
WHERE id = %d
LIMIT 1";
		$sql = $this->wpdb->prepare( $sql, $relationship_id );

		$type = (string) $this->wpdb->get_var( $sql );
		if ( ! $type ) {
			return '';
		}

		wp_cache_set( $cache_key, $type, $this->cache_group );

		return $type;
	}

	/**
	 * Return the relationship type for the given relationship ID.
	 *
	 * @param int $relationship_id Relationship ID.
	 *
	 * @return string
	 */
	private function get_relationship_type_cache_key( $relationship_id ) {

		return "mlp_relationship_type_{$relationship_id}";
	}

	/**
	 * Return the relationship id cache key for the given arguments.
	 *
	 * @param int    $site_id    Site ID.
	 * @param int    $content_id Content ID.
	 * @param string $type       Content type.
	 *
	 * @return string
	 */
	private function get_relationship_id_cache_key( $site_id, $content_id, $type ) {

		return "mlp_{$type}_relationship_id_{$site_id}_{$content_id}";
	}

	/**
	 * Return the content ID for the given arguments.
	 *
	 * @param int $relationship_id Relationship ID.
	 * @param int $site_id         Site ID.
	 *
	 * @return int
	 */
	public function get_content_id( $relationship_id, $site_id ) {

		$content_ids = $this->get_content_ids( $relationship_id );
		if ( empty( $content_ids[ $site_id ] ) ) {
			return 0;
		}

		return $content_ids[ $site_id ];
	}

	/**
	 * Return the relationship ID for the given arguments.
	 *
	 * @param int    $site_id    Site ID.
	 * @param int    $content_id Content ID.
	 * @param string $type       Content type.
	 *
	 * @return int
	 */
	private function get_relationship_id_single( $site_id, $content_id, $type = 'post' ) {

		$cache_key = $this->get_relationship_id_cache_key( $site_id, $content_id, $type );

		$cache = wp_cache_get( $cache_key, $this->cache_group );
		if ( is_int( $cache ) && $cache > 0 ) {
			return $cache;
		}

		$sql = "
SELECT r.id
FROM {$this->relationships_table} r
INNER JOIN {$this->table} t ON r.id = t.relationship_id
WHERE t.site_id = %d
	AND t.content_id = %d
	AND r.type = %s
LIMIT 1";
		$query = $this->wpdb->prepare( $sql, $site_id, $content_id, $type );

		$relationship_id = (int) $this->wpdb->get_var( $query );
		if ( ! $relationship_id ) {
			return 0;
		}

		wp_cache_set( $cache_key, $relationship_id, $this->cache_group );

		return $relationship_id;
	}

	/**
	 * Insert a new relation with the given values.
	 *
	 * @param int $relationship_id Relationship ID.
	 * @param int $site_id         Site ID.
	 * @param int $content_id      Content ID.
	 *
	 * @return int
	 */
	private function insert_relation( $relationship_id, $site_id, $content_id ) {

		$data = compact(
			'relationship_id',
			'site_id',
			'content_id'
		);

		$inserted_rows = (int) $this->wpdb->insert( $this->table, $data, '%d' );
		if ( $inserted_rows ) {
			$content_ids = $this->get_content_ids( $relationship_id );
			$content_ids[ $site_id ] = $content_id;

			wp_cache_set(
				$this->get_content_ids_cache_key( $relationship_id ),
				$content_ids,
				$this->cache_group
			);

			$type = $this->get_relationship_type( $relationship_id );
			if ( $type ) {
				wp_cache_set(
					$this->get_relationship_id_cache_key( $site_id, $content_id, $type ),
					$relationship_id,
					$this->cache_group
				);

			}
		}

		return $inserted_rows;
	}

	/**
	 * Delete all relations for the given site ID.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return int
	 */
	public function delete_all_relations_for_site( $site_id ) {

		$deleted_rows = 0;

		foreach ( $this->get_relationship_ids_for_site( $site_id ) as $relationship_id ) {
			$deleted_rows += $this->delete_relation_for_site( $relationship_id, $site_id );
		};

		return $deleted_rows;
	}

	/**
	 * Return the relationship IDs for the given site ID.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return int[]
	 */
	private function get_relationship_ids_for_site( $site_id ) {

		$cache_key = $this->get_relationship_ids_cache_key( $site_id );
		$cache = wp_cache_get( $cache_key, $this->cache_group );
		if ( is_array( $cache ) ) {
			return $cache;
		}

		$sql = "
SELECT DISTINCT relationhip_id
FROM {$this->table}
WHERE site_id = %d;";
		$sql = $this->wpdb->prepare( $sql, $site_id );
		$relationship_ids = $this->wpdb->get_results( $sql, ARRAY_N );
		if ( empty( $relationship_ids ) ) {
			return array();
		}

		wp_cache_set( $cache_key, $relationship_ids, $this->cache_group );

		return $relationship_ids;
	}

	/**
	 * Return the relationship IDs cache key for the given site ID.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return string
	 */
	private function get_relationship_ids_cache_key( $site_id ) {

		return "mlp_relationship_ids_$site_id";
	}

	/**
	 * Delete the relation for the given arguments.
	 *
	 * @param int[]  $content_ids Array with site IDs as keys and content IDs as value.
	 * @param string $type        Content type.
	 *
	 * @return int
	 */
	public function delete_relation( array $content_ids, $type ) {

		$relationship_id = $this->get_relationship_id( $content_ids, $type );
		if ( ! $relationship_id ) {
			return 0;
		}

		$deleted_rows = 0;

		foreach ( array_keys( $content_ids ) as $site_id ) {
			$deleted_rows += $this->delete_relation_for_site( $relationship_id, $site_id );
		}

		return $deleted_rows;
	}

	/**
	 * Return the relationship ID for the given arguments.
	 *
	 * @param int[]  $content_ids Array with site IDs as keys and content IDs as values.
	 * @param string $type        Content type.
	 * @param bool   $create      Optional. Create a new relationship if not exists? Defaults to FALSE.
	 *
	 * @return int
	 */
	public function get_relationship_id( array $content_ids, $type, $create = FALSE ) {

		switch ( count( $content_ids ) ) {
			case 0:
				// Error: No contents given!
				return 0;

			case 1:
				$site_id = key( $content_ids );
				$content_id = current( $content_ids );

				$relationship_id = $this->get_relationship_id_single( $site_id, $content_id, $type );
				break;

			default:
				$relationship_id = $this->get_relationship_id_multiple( $content_ids, $type );
		}

		if ( ! $relationship_id && $create ) {
			return $this->insert_relationship( $type );
		}

		return $relationship_id;
	}

	/**
	 * Return the relationship ID for the given arguments.
	 *
	 * @param int[]  $content_ids Array with site IDs as keys and content IDs as value.
	 * @param string $type        Content type.
	 *
	 * @return int
	 */
	private function get_relationship_id_multiple( array $content_ids, $type ) {

		$relationship_id = 0;

		foreach ( $content_ids as $site_id => $content_id ) {
			$new_relationship_id = $this->get_relationship_id_single( $site_id, $content_id, $type );
			if ( ! $new_relationship_id ) {
				continue;
			}

			if ( $relationship_id === 0 ) {
				$relationship_id = $new_relationship_id;
			} elseif ( $relationship_id !== $new_relationship_id ) {
				// Error: Different relationship IDs!
				return 0;
			}
		}

		return $relationship_id;
	}

	/**
	 * Insert a new relationship entry for the given type, and return the relationship ID.
	 *
	 * @param string $type Content type.
	 *
	 * @return int
	 */
	private function insert_relationship( $type ) {

		if ( ! $this->wpdb->insert( $this->relationships_table, compact( 'type' ), '%s' ) ) {
			return 0;
		}

		$relationship_id = (int) $this->wpdb->insert_id;

		wp_cache_set( $this->get_relationship_type_cache_key( $relationship_id ), $type, $this->cache_group );

		return $relationship_id;
	}

	/**
	 * Return the content ID in the given target site for the given source content element.
	 *
	 * @param int    $site_id        Source site ID.
	 * @param int    $content_id     Source post ID or term taxonomy ID.
	 * @param string $type           Content type.
	 * @param int    $target_site_id Target site ID.
	 *
	 * @return int
	 */
	public function get_content_id_for_site(
		$site_id,
		$content_id,
		$type,
		$target_site_id
	) {

		$relations = $this->get_relations( $site_id, $content_id, $type );

		return empty( $relations[ $target_site_id ] ) ? 0 : $relations[ $target_site_id ];
	}

	/**
	 * Return an array with site IDs as keys and content IDs as values.
	 *
	 * @param int    $site_id    Source site ID.
	 * @param int    $content_id Source post ID or term taxonomy ID.
	 * @param string $type       Content type.
	 *
	 * @return array
	 */
	public function get_relations( $site_id, $content_id, $type ) {

		$relationship_id = $this->get_relationship_id_single( $site_id, $content_id, $type );
		if ( ! $relationship_id ) {
			return array();
		}

		return $this->get_content_ids( $relationship_id );
	}

}
