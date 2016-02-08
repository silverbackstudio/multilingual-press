<?php # -*- coding: utf-8 -*-

/**
 * Handle term relations.
 */
class Mlp_Term_Connector {

	/**
	 * @var Mlp_Content_Relations_Interface
	 */
	private $content_relations;

	/**
	 * @var string
	 */
	private $content_type = 'term';

	/**
	 * @var int
	 */
	private $current_site_id;

	/**
	 * @var Inpsyde_Nonce_Validator_Interface
	 */
	private $nonce;

	/**
	 * @var int[]
	 */
	private $post_data = array();

	/**
	 * @var string[]
	 */
	private $taxonomies;

	/**
	 * Constructor. Set up the properties.
	 *
	 * @param Mlp_Content_Relations_Interface   $content_relations Content relations object.
	 * @param Inpsyde_Nonce_Validator_Interface $nonce             Nonce validator object.
	 * @param string[]                          $taxonomies        Taxonomy names.
	 */
	public function __construct(
		Mlp_Content_Relations_Interface $content_relations,
		Inpsyde_Nonce_Validator_Interface $nonce,
		array $taxonomies
	) {

		$this->content_relations = $content_relations;

		$this->nonce = $nonce;

		$this->taxonomies = $taxonomies;

		$this->current_site_id = get_current_blog_id();
	}

	/**
	 * Set the post data array.
	 *
	 * @param string[] $post_data Post data.
	 */
	public function set_post_data( array $post_data ) {

		$this->post_data = array_map( 'intval', $post_data );
	}

	/**
	 * Handle term changes.
	 *
	 * @wp-hook create_term
	 * @wp-hook delete_term
	 * @wp-hook edit_term
	 *
	 * @param int    $term_id          Unused. Term ID.
	 * @param int    $term_taxonomy_id Term taxonomy ID.
	 * @param string $taxonomy         Taxonomy slug.
	 *
	 * @return bool
	 */
	public function change_term_relationships(
		/** @noinspection PhpUnusedParameterInspection */
		$term_id,
		$term_taxonomy_id,
		$taxonomy
	) {

		if ( ! $this->nonce->is_valid() ) {
			return false;
		}

		if ( ! in_array( $taxonomy, $this->taxonomies ) ) {
			return false;
		}

		/**
		 * This is a core bug!
		 *
		 * @see https://core.trac.wordpress.org/ticket/32876
		 */
		$term_taxonomy_id = (int) $term_taxonomy_id;

		$success = false;

		$current_filter = current_filter();

		if ( is_callable( array( $this, $current_filter ) ) ) {
			/**
			 * Runs before the terms are changed.
			 *
			 * @param int    $term_taxonomy_id Term taxonomy ID.
			 * @param string $taxonomy         Taxonomy name.
			 * @param string $current_filter   Current filter.
			 */
			do_action( 'mlp_before_term_synchronization', $term_taxonomy_id, $taxonomy, $current_filter );

			$success = call_user_func( array( $this, $current_filter ), $term_taxonomy_id );

			/**
			 * Runs after the terms have been changed.
			 *
			 * @param int    $term_taxonomy_id Term taxonomy ID.
			 * @param string $taxonomy         Taxonomy name.
			 * @param string $current_filter   Current filter.
			 * @param bool   $success          Denotes whether or not the database was changed.
			 */
			do_action( 'mlp_after_term_synchronization', $term_taxonomy_id, $taxonomy, $current_filter, $success );
		}

		return $success;
	}

	/**
	 * Handle term edits.
	 *
	 * @param int $term_taxonomy_id Term taxonomy ID.
	 *
	 * @return bool
	 */
	public function edit_term( $term_taxonomy_id ) {

		if ( ! $this->post_data ) {
			return false;
		}

		if ( ! array_diff( $this->post_data, array( 0 ) ) ) {
			// All remote terms have been unselected, so delete the currently edited term rather than the remote ones
			return $this->delete_term( $term_taxonomy_id );
		}

		return $this->create_term( $term_taxonomy_id );
	}

	/**
	 * Handle term deletion.
	 *
	 * @param int $term_taxonomy_id Term taxonomy ID.
	 *
	 * @return bool
	 */
	public function delete_term( $term_taxonomy_id ) {

		return (bool) $this->content_relations->delete_relation(
			array( $this->current_site_id => $term_taxonomy_id ),
			$this->content_type
		);
	}

	/**
	 * Handle term creation.
	 *
	 * @param int $term_taxonomy_id Term taxonomy ID.
	 *
	 * @return bool
	 */
	public function create_term( $term_taxonomy_id ) {

		if ( ! $this->post_data ) {
			return false;
		}

		return $this->set_relations( $this->post_data + array( $this->current_site_id => $term_taxonomy_id ) );
	}

	/**
	 * Set the relations for the given term taxonomy IDs.
	 *
	 * @param int[] $term_taxonomy_ids Term taxonomy IDs.
	 *
	 * @return bool
	 */
	private function set_relations( array $term_taxonomy_ids ) {

		$relationship_id = $this->content_relations->get_relationship_id(
			$term_taxonomy_ids,
			$this->content_type,
			true
		);
		if ( ! $relationship_id ) {
			return false;
		}

		$success = true;

		foreach ( $term_taxonomy_ids as $site_id => $term_taxonomy_id ) {
			$success &= $this->content_relations->set_relation( $relationship_id, $site_id, $term_taxonomy_id );
		}

		return $success;
	}
}
