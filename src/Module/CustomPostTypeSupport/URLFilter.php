<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\CustomPostTypeSupport;

use WP_Post;

/**
 * Post type link URL filter.
 *
 * @package Inpsyde\MultilingualPress\Module\CustomPostTypeSupport
 * @since   3.0.0
 */
class URLFilter {

	/**
	 * @var callable
	 */
	private $filter;

	/**
	 * @var string
	 */
	private $hook = 'post_type_link';

	/**
	 * @var PostTypeRepository
	 */
	private $post_type_repository;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param PostTypeRepository $post_type_repository Post type repository object.
	 */
	public function __construct( PostTypeRepository $post_type_repository ) {

		$this->post_type_repository = $post_type_repository;

		$this->filter = [ $this, 'unprettify_permalink' ];
	}

	/**
	 * Removes the filter.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether or not the filter was removed successfully.
	 */
	public function disable() {

		if ( has_filter( $this->hook, $this->filter ) ) {
			remove_filter( $this->hook, $this->filter );

			return true;
		};

		return false;
	}

	/**
	 * Adds the filter.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether or not the filter was added successfully.
	 */
	public function enable() {

		if ( has_filter( $this->hook, $this->filter ) ) {
			return false;
		};

		add_filter( $this->hook, $this->filter, 10, 2 );

		return true;
	}

	/**
	 * Filters the post type link URL and returns a query-based representation, if set for the according post type.
	 *
	 * @since   3.0.0
	 * @wp-hook post_type_link
	 *
	 * @param string  $post_link Post URL.
	 * @param WP_Post $post      Post object.
	 *
	 * @return string The (filtered) post type link URL.
	 */
	public function unprettify_permalink( $post_link, WP_Post $post ) {

		if ( ! $this->post_type_repository->is_post_type_active_and_query_based( $post->post_type ) ) {
			return $post_link;
		}

		$post_type = get_post_type_object( $post->post_type );

		if ( $post_type->query_var && ! $this->is_draft_or_pending( $post ) ) {
			$args = [
				$post_type->query_var => $post->post_name,
			];
		} else {
			$args = [
				'p' => $post->ID,
			];
		}

		return site_url( add_query_arg( $args, '' ) );
	}

	/**
	 * Checks if the given post is a draft or pending.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return bool Whether or not the given post is a draft or pending.
	 */
	private function is_draft_or_pending( $post ) {

		if ( empty( $post->post_status ) ) {
			return false;
		}

		return in_array( $post->post_status, [ 'draft', 'pending', 'auto-draft' ], true );
	}
}
