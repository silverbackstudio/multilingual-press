<?php # -*- coding: utf-8 -*-

/**
 * Handles plugin activation.
 */
class Mlp_Activator {

	/**
	 * @var string
	 */
	public $transient = 'mlp_activation';

	/**
	 * @var array[]
	 */
	private $all_sites;

	/**
	 * @var Mlp_Content_Relations
	 */
	private $content_relations;

	/**
	 * @var string
	 */
	private $content_relations_table;

	/**
	 * @var string
	 */
	private $relationships_table;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Sets a transient that informs about the plugin's activation.
	 *
	 * @return void
	 */
	public function set_transient() {

		set_transient( $this->transient, TRUE );
	}

	/**
	 * Fires an action right after plugin activation.
	 *
	 * This allows for performing individual activation-specific actions that require specific plugin data.
	 *
	 * @param Inpsyde_Property_List_Interface $plugin_data Plugin data object.
	 * @param wpdb                            $wpdb        Database object.
	 *
	 * @return bool
	 */
	public function activate( Inpsyde_Property_List_Interface $plugin_data, wpdb $wpdb ) {

		if ( ! get_transient( $this->transient ) ) {
			return FALSE;
		}

		$this->set_properties( $plugin_data, $wpdb );

		$this->delete_invalid_content_relations();

		delete_transient( $this->transient );

		return TRUE;
	}

	/**
	 * Sets the properties required for all activation-specific actions.
	 *
	 * @param Inpsyde_Property_List_Interface $plugin_data Plugin data object.
	 * @param wpdb                            $wpdb        Database object.
	 *
	 * @return void
	 */
	private function set_properties( Inpsyde_Property_List_Interface $plugin_data, wpdb $wpdb ) {

		$this->all_sites = wp_get_sites();

		$this->content_relations = $plugin_data->get( 'content_relations' );

		/** @var Mlp_Db_Schema_Interface $schema */
		$schema = $plugin_data->get( 'content_relations_schema' );
		$this->content_relations_table = $schema->get_table_name();

		/** @var Mlp_Db_Schema_Interface $schema */
		$schema = $plugin_data->get( 'relationships_schema' );
		$this->relationships_table = $schema->get_table_name();

		$this->wpdb = $wpdb;
	}

	/**
	 * Deletes all content relations for content that doesn't exist anymore.
	 *
	 * @return void
	 */
	private function delete_invalid_content_relations() {

		// Delete all relations for deleted sites
		$query = "
SELECT DISTINCT site_id
FROM {$this->content_relations_table}
WHERE site_id NOT IN (
		SELECT blog_id
		FROM {$this->wpdb->blogs}
	)";
		foreach ( $this->wpdb->query( $query ) as $site_id ) {
			$this->content_relations->delete_all_relations_for_site( $site_id );
		}

		$select_query = "
SELECT content_id
FROM {$this->content_relations_table}
WHERE site_id = %d
	AND content_id NOT IN (%s)";

		foreach ( $this->all_sites as $site ) {
			if ( empty( $site[ 'blog_id' ] ) ) {
				continue;
			}

			$site_id = $site[ 'blog_id' ];

			switch_to_blog( $site_id );

			// Delete all entries for POSTS that don't exist anymore
			$post_ids = $this->wpdb->get_results( "SELECT ID FROM {$this->wpdb->posts}", ARRAY_N );
			if ( $post_ids ) {
				$post_ids_string = '';

				foreach ( $post_ids as $post_id ) {
					$post_ids_string .= ',' . reset( $post_id );
				}

				$post_ids_string = substr( $post_ids_string, 1 );

				$query = $this->wpdb->prepare(
					$select_query,
					array(
						$site_id,
						$post_ids_string,
					)
				);
			} else {
				$query = $this->wpdb->prepare(
					$select_query,
					array(
						$site_id,
					)
				);
			}
			$content_ids = $this->wpdb->get_results( "$query\n\tAND ml_type = 'post'" );
			if ( $content_ids ) {
				foreach ( $content_ids as $content_id ) {
					$this->content_relations->delete_relation( array( $site_id => reset( $content_id ) ), 'post' );
				}
			}

			// Delete entries for TERMS that don't exist anymore
			$term_taxonomy_ids = $this->wpdb->get_results(
				"SELECT term_taxonomy_id FROM {$this->wpdb->term_taxonomy}",
				ARRAY_N
			);
			if ( $term_taxonomy_ids ) {
				$term_taxonomy_ids_string = '';

				foreach ( $term_taxonomy_ids as $term_taxonomy_id ) {
					$term_taxonomy_ids_string .= ',' . reset( $term_taxonomy_id );
				}

				$term_taxonomy_ids_string = substr( $term_taxonomy_ids_string, 1 );

				$query = $this->wpdb->prepare(
					$select_query,
					array(
						$site_id,
						$term_taxonomy_ids_string,
					)
				);
			} else {
				$query = $this->wpdb->prepare(
					$select_query,
					array(
						$site_id,
					)
				);
			}
			$content_ids = $this->wpdb->get_results( "$query\n\tAND ml_type = 'term'" );
			if ( $content_ids ) {
				foreach ( $content_ids as $content_id ) {
					$this->content_relations->delete_relation( array( $site_id => reset( $content_id ) ), 'term' );
				}
			}
		}

		if ( ! empty( $GLOBALS[ '_wp_switched_stack' ] ) ) {
			$GLOBALS[ '_wp_switched_stack' ] = (array) reset( $GLOBALS[ '_wp_switched_stack' ] );
		}

		restore_current_blog();
	}

}
