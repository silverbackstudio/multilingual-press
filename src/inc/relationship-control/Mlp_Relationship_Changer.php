<?php # -*- coding: utf-8 -*-

/**
 * Change post relationships on AJAX calls.
 */
class Mlp_Relationship_Changer {

	/**
	 * @var Mlp_Content_Relations_Interface
	 */
	private $content_relations;

	/**
	 * @var string
	 */
	private $content_type = 'post';

	/**
	 * @var int
	 */
	private $new_post_id = 0;

	/**
	 * @var string
	 */
	private $new_post_title = '';

	/**
	 * @var int
	 */
	private $remote_site_id = 0;

	/**
	 * @var int
	 */
	private $remote_post_id = 0;

	/**
	 * @var int
	 */
	private $source_site_id = 0;

	/**
	 * @var int
	 */
	private $source_post_id = 0;

	/**
	 * @param Inpsyde_Property_List_Interface $data
	 */
	public function __construct( Inpsyde_Property_List_Interface $data ) {

		$this->content_relations = $data->get( 'content_relations' );

		$this->prepare_fields();
	}

	/**
	 * @return int|string
	 */
	public function new_relation() {

		switch_to_blog( $this->source_site_id );

		$source_post = get_post( $this->source_post_id );

		restore_current_blog();

		if ( ! $source_post ) {
			return 'source not found';
		}

		$save_context = array(
			'source_site'    => $this->source_site_id,
			'source_blog'    => $this->source_site_id, // Backwards compatibility
			'source_post'    => $source_post,
			'real_post_type' => $this->get_real_post_type( $source_post ),
			'real_post_id'   => $this->get_real_post_id( $this->source_post_id ),
		);

		/** This action is documented in inc/advanced-translator/Mlp_Advanced_Translator_Data.php */
		do_action( 'mlp_before_post_synchronization', $save_context );

		switch_to_blog( $this->remote_site_id );

		$post_id = wp_insert_post(
			array(
				'post_type'   => $source_post->post_type,
				'post_status' => 'draft',
				'post_title'  => $this->new_post_title,
			),
			true
		);

		restore_current_blog();

		$save_context['target_site_id'] = $this->remote_site_id;
		$save_context['target_blog_id'] = $this->remote_site_id; // Backwards compatibility

		/** This action is documented in inc/advanced-translator/Mlp_Advanced_Translator_Data.php */
		do_action( 'mlp_after_post_synchronization', $save_context );

		if ( is_wp_error( $post_id ) ) {
			return $post_id->get_error_messages();
		}

		$this->new_post_id = $post_id;

		$this->connect_existing();

		return $this->new_post_id;
	}

	/**
	 * Target callback for the AJAX request for connecting a new post.
	 *
	 * @return int|string
	 */
	public function new_post() {

		return $this->new_relation();
	}

	/**
	 * Get the real current post type.
	 *
	 * Includes workaround for auto-drafts.
	 *
	 * @param WP_Post $post
	 *
	 * @return string
	 */
	public function get_real_post_type( WP_Post $post ) {

		if ( 'revision' !== $post->post_type ) {
			return $post->post_type;
		}

		if ( empty( $_POST['post_type'] ) ) {
			return $post->post_type;
		}

		if ( 'revision' === $_POST['post_type'] ) {
			return $post->post_type;
		}

		// auto-draft
		if ( is_string( $_POST['post_type'] ) ) {
			return $_POST['post_type'];
		}

		return $post->post_type;
	}

	/**
	 * Figure out the post ID.
	 *
	 * Inspect POST request data, too, because we get two IDs for auto-drafts.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return int
	 */
	public function get_real_post_id( $post_id ) {

		if ( ! empty( $_POST['post_ID'] ) ) {
			return absint( $_POST['post_ID'] );
		}

		return $post_id;
	}

	/**
	 * @return bool
	 */
	public function connect_existing() {

		$this->disconnect();

		return $this->create_new_relation();
	}

	/**
	 * Target callback for the AJAX request for connecting an existing post.
	 *
	 * @return false|int
	 */
	public function search_post() {

		return $this->connect_existing();
	}

	/**
	 * @return int
	 */
	public function disconnect() {

		return $this->delete_relation( $this->remote_post_id, $this->remote_site_id );
	}

	/**
	 * Target callback for the AJAX request for disconnecting the currently connected post.
	 *
	 * @return int
	 */
	public function disconnect_post() {

		return $this->disconnect();
	}

	/**
	 * Delete the post relation for the given arguments.
	 *
	 * @wp-hook deleted_post
	 *
	 * @param int $post_id Post ID.
	 * @param int $site_id Optional. Site ID. Defaults to 0.
	 *
	 * @return int
	 */
	public function delete_relation( $post_id, $site_id = 0 ) {

		if ( ! $site_id ) {
			$site_id = get_current_blog_id();
		}

		return $this->content_relations->delete_relation(
			array( $site_id => $post_id ),
			$this->content_type
		);
	}

	/**
	 * Prepare class properties.
	 *
	 * @return void
	 */
	private function prepare_fields() {

		$keys = array(
			'source_post_id',
			'source_site_id',
			'remote_post_id',
			'remote_site_id',
			'new_post_id',
			'new_post_title',
		);
		foreach ( $keys as $key ) {
			if ( empty( $_REQUEST[ $key ] ) ) {
				continue;
			}

			$this->$key = ( 'new_post_title' === $key )
				? (string) $_REQUEST[ $key ]
				: (int) $_REQUEST[ $key ];
		}
	}

	/**
	 * @return bool
	 */
	private function create_new_relation() {

		$content_ids = array(
			$this->source_site_id => $this->source_post_id,
			$this->remote_site_id => $this->new_post_id,
		);

		$relationship_id = $this->content_relations->get_relationship_id( $content_ids, $this->content_type, true );
		if ( ! $relationship_id ) {
			return false;
		}

		$success = true;

		foreach ( $content_ids as $site_id => $post_id ) {
			$success &= $this->content_relations->set_relation( $relationship_id, $site_id, $post_id );
		}

		return $success;
	}
}
