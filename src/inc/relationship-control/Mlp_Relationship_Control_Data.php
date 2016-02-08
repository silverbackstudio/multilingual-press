<?php # -*- coding: utf-8 -*-

/**
 * Post relations data provider.
 */
class Mlp_Relationship_Control_Data {

	/**
	 * @var int[]
	 */
	private $ids = array(
		'source_site_id' => 0,
		'source_post_id' => 0,
		'remote_site_id' => 0,
		'remote_post_id' => 0,
	);

	/**
	 * @var string
	 */
	private $search = '';

	/**
	 * @param array $ids
	 */
	public function __construct( array $ids = array() ) {

		if ( ! empty( $ids ) ) {
			$this->ids = $ids;

			return;
		}

		foreach ( $this->ids as $key => $value ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				$this->ids[ $key ] = (int) $_REQUEST[ $key ];
			}
		}

		if ( isset( $_REQUEST['s'] ) ) {
			$this->search = $_REQUEST['s'];
		}
	}

	/**
	 * Set values lately.
	 *
	 * @param int[] $ids
	 *
	 * @return int[]
	 */
	public function set_ids( array $ids ) {

		$this->ids = $ids;

		return $this->ids;
	}

	/**
	 * @return int
	 */
	public function get_remote_post_id() {

		return $this->ids['remote_post_id'];
	}

	/**
	 * @return int
	 */
	public function get_remote_site_id() {

		return $this->ids['remote_site_id'];
	}

	/**
	 * @return int
	 */
	public function get_source_post_id() {

		return $this->ids['source_post_id'];
	}

	/**
	 * @return array
	 */
	public function get_search_results() {

		if (
			$this->ids['remote_site_id'] === 0
			|| $this->ids['source_site_id'] === 0
		) {
			return array();
		}

		$source_post = $this->get_source_post();
		if ( ! $source_post ) {
			return array();
		}

		$args = array(
			'numberposts' => 10,
			'post_type'   => $source_post->post_type,
			'post_status' => array( 'draft', 'publish', 'private' ),
		);

		if ( ! empty( $this->ids['remote_post_id'] ) ) {
			$args['exclude'] = $this->ids['remote_post_id'];
		}

		if ( ! empty( $this->search ) ) {
			$args['s'] = $this->search;
		}

		switch_to_blog( $this->ids['remote_site_id'] );

		/**
		 * Filter the get_posts arguments used by the Relationship Control AJAX Search.
		 *
		 * @param array $args AJAX search arguments.
		 *
		 * @return array
		 */
		$args = apply_filters( 'mlp_relationship_control_ajax_search_arguments', $args );

		$posts = get_posts( $args );

		restore_current_blog();

		if ( empty( $posts ) ) {
			return array();
		}

		return $posts;
	}

	/**
	 * @return WP_Post|NULL
	 */
	public function get_source_post() {

		switch_to_blog( $this->ids['source_site_id'] );

		$post = get_post( $this->ids['source_post_id'] );

		restore_current_blog();

		return $post;
	}
}
