<?php # -*- coding: utf-8 -*-

/**
 * Handle relationships between sites (blogs) in a network.
 */
class Mlp_Site_Relations implements Mlp_Site_Relations_Interface {

	/**
	 * @var string
	 */
	private $cache_group = 'mlp';

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @param wpdb                    $wpdb
	 * @param Mlp_Db_Schema_Interface $schema
	 */
	public function __construct( wpdb $wpdb, Mlp_Db_Schema_Interface $schema ) {

		$this->wpdb = $wpdb;

		$this->table = $schema->get_table_name();
	}

	/**
	 * Return all related sites for the given site.
	 *
	 * @param int $site_id
	 *
	 * @return int[]
	 */
	public function get_related_sites( $site_id = 0 ) {

		if ( ! $site_id ) {
			$site_id = get_current_blog_id();
		}

		$cache_key = $this->get_cache_key( $site_id );

		$cache = wp_cache_get( $cache_key, $this->cache_group );
		if ( is_array( $cache ) ) {
			return $cache;
		}

		$sql = "
SELECT DISTINCT IF (site_1 = %d, site_2, site_1) AS site_id
FROM {$this->table}
WHERE (
		site_1 = %d
		OR site_2 = %d
	)";
		$sql = $this->wpdb->prepare( $sql, $site_id, $site_id, $site_id );

		$related_sites = $this->wpdb->get_col( $sql );
		$related_sites = array_map( 'intval', $related_sites );

		wp_cache_set( $cache_key, $related_sites, $this->cache_group );

		return $related_sites;
	}

	/**
	 * Create new relation for one site with one or more others.
	 *
	 * @param int       $site_1
	 * @param int|array $sites ID or array of IDs
	 *
	 * @return int Number of affected rows.
	 */
	public function set_relation( $site_1, $sites ) {

		$values = array();

		foreach ( (array) $sites as $site_id ) {
			if ( $site_1 !== $site_id ) {
				$values[] = $this->get_value_pair( $site_1, $site_id );

				wp_cache_delete( $this->get_cache_key( $site_id ), $this->cache_group );
			}
		}

		if ( empty( $values ) ) {
			return 0;
		}

		$values = join( ', ', $values );

		wp_cache_delete( $this->get_cache_key( $site_1 ), $this->cache_group );

		return (int) $this->wpdb->query( "INSERT IGNORE INTO {$this->table} (site_1, site_2) VALUES $values" );
	}

	/**
	 * Delete relationships.
	 *
	 * @param int $site_1
	 * @param int $site_2 Optional. If left out, all relations will be deleted.
	 *
	 * @return int
	 */
	public function delete_relation( $site_1, $site_2 = 0 ) {

		$site_1 = (int) $site_1;

		$site_2 = (int) $site_2;

		$sql = "
DELETE
FROM {$this->table}
WHERE (
		site_1 = $site_1
		OR site_2 = $site_1
	)";

		wp_cache_delete( $this->get_cache_key( $site_1 ), $this->cache_group );

		if ( 0 < $site_2 ) {
			$sql .= "
	AND (
		site_1 = $site_2
		OR site_2 = $site_2
	)";

			wp_cache_delete( $this->get_cache_key( $site_2 ), $this->cache_group );
		}

		return (int) $this->wpdb->query( $sql );
	}

	/**
	 * Return the cache key for the given site ID.
	 *
	 * @param int $site_id Site ID.
	 *
	 * @return string
	 */
	private function get_cache_key( $site_id ) {

		return "mlp_site_relations_$site_id";
	}

	/**
	 * Generate (val1,val2) syntax string.
	 *
	 * @param int $site_1
	 * @param int $site_2
	 *
	 * @return string
	 */
	private function get_value_pair( $site_1, $site_2 ) {

		$site_1 = (int) $site_1;

		$site_2 = (int) $site_2;

		return $site_1 > $site_2 ? "($site_2,$site_1)" : "($site_1,$site_2)";
	}
}
