<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Relations\Post;

use Inpsyde\MultilingualPress\API\ContentRelations;
use WP_Error;
use WP_Post;

/**
 * Relationship controller.
 *
 * @package Inpsyde\MultilingualPress\Relations\Post
 * @since   3.0.0
 */
class RelationshipController {

	/**
	 * Action to be used in requests.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const ACTION_CONNECT_EXISTING = 'mlp_rc_connect_existing_post';

	/**
	 * Action to be used in requests.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const ACTION_CONNECT_NEW = 'mlp_rc_connect_new_post';

	/**
	 * Action to be used in requests.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const ACTION_DISCONNECT = 'mlp_rc_disconnect_post';

	/**
	 * @var ContentRelations
	 */
	private $content_relations;

	/**
	 * @var RelationshipContext
	 */
	private $context;

	/**
	 * @var WP_Error
	 */
	private $last_error = null;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @since 3.0.0
	 *
	 * @param ContentRelations $content_relations Content relations API object.
	 */
	public function __construct( ContentRelations $content_relations ) {

		$this->content_relations = $content_relations;

		$this->context = RelationshipContext::from_request();
	}

	/**
	 * Initializes the relationship controller.
	 *
	 * @since 3.0.0
	 *
	 * @return void
	 */
	public function initialize() {

		$callback = $this->get_callback();
		if ( $callback ) {
			add_action( "wp_ajax_{$_REQUEST['action']}", $callback );
		}
	}

	/**
	 * Connects the current post with an existing remote one.
	 *
	 * @since   3.0.0
	 * @wp-hook wp_ajax_{$action}
	 *
	 * @return void
	 */
	public function handle_connect_existing_post() {

		if ( $this->connect_existing_post() ) {
			wp_send_json_success();
		}

		wp_send_json_error( $this->last_error );
	}

	/**
	 * Connects the current post with a new remote one.
	 *
	 * @since   3.0.0
	 * @wp-hook wp_ajax_{$action}
	 *
	 * @return void
	 */
	public function handle_connect_new_post() {

		if ( $this->connect_new_post() ) {
			wp_send_json_success();
		}

		wp_send_json_error( $this->last_error );
	}

	/**
	 * Disconnects the current post and the one given in the request.
	 *
	 * @since   3.0.0
	 * @wp-hook wp_ajax_{$action}
	 *
	 * @return void
	 */
	public function handle_disconnect_post() {

		$this->disconnect_post();

		wp_send_json_success();
	}

	/**
	 * Connects the current post with a new remote one.
	 *
	 * @return bool|WP_Error Whether or not the relationship was updated successfully, or an error object.
	 */
	private function connect_new_post() {

		$source_post = $this->context->source_post();
		if ( ! $source_post ) {
			return false;
		}

		$remote_site_id = $this->context->remote_site_id();

		$save_context = [
			'source_blog'    => $this->context->source_site_id(),
			'source_post'    => $source_post,
			'real_post_type' => $this->get_real_post_type( $source_post ),
			'real_post_id'   => empty( $_POST['post_ID'] ) ? $this->context->source_post_id() : (int) $_POST['post_ID'],
		];

		/** This action is documented in inc/advanced-translator/Mlp_Advanced_Translator_Data.php */
		do_action( 'mlp_before_post_synchronization', $save_context );

		switch_to_blog( $remote_site_id );

		$new_post_id = wp_insert_post( [
			'post_type'   => $source_post->post_type,
			'post_status' => 'draft',
			'post_title'  => $this->context->new_post_title(),
		], true );

		restore_current_blog();

		$save_context['target_blog_id'] = $remote_site_id;

		/** This action is documented in inc/advanced-translator/Mlp_Advanced_Translator_Data.php */
		do_action( 'mlp_after_post_synchronization', $save_context );

		if ( is_wp_error( $new_post_id ) ) {
			$this->last_error = $new_post_id;

			return false;
		}

		$this->context = RelationshipContext::from_existing( $this->context, [
			RelationshipContext::KEY_NEW_POST_ID => $new_post_id,
		] );

		return $this->connect_existing_post();
	}

	/**
	 * Connects the current post with an existing remote one.
	 *
	 * @return bool Whether or not the relationship was updated successfully.
	 */
	private function connect_existing_post() {

		$this->disconnect_post();

		return $this->content_relations->set_relation(
			$this->context->source_site_id(),
			$this->context->remote_site_id(),
			$this->context->source_post_id(),
			$this->context->new_post_id(),
			'post'
		);
	}

	/**
	 * Disconnects the current post with the one given in the request.
	 *
	 * @return void
	 */
	private function disconnect_post() {

		$remote_site_id = $this->context->remote_site_id();

		$remote_post_id = $this->context->remote_post_id();

		$source_site_id = $this->context->source_site_id();

		$translation_ids = $this->content_relations->get_translation_ids(
			$this->context->source_site_id(),
			$remote_site_id,
			$this->context->source_post_id(),
			$remote_post_id,
			'post'
		);

		if ( $translation_ids['ml_source_blogid'] !== $source_site_id ) {
			$remote_site_id = $source_site_id;
			if ( 0 !== $remote_post_id ) {
				$remote_post_id = $this->context->source_post_id();
			}
		}

		$this->content_relations->delete_relation(
			$translation_ids['ml_source_blogid'],
			$remote_site_id,
			$translation_ids['ml_source_elementid'],
			$remote_post_id,
			'post'
		);
	}

	/**
	 * Returns the appropriate callback for the current action.
	 *
	 * @return callable Callback, of null on failure.
	 */
	private function get_callback() {

		switch ( $_REQUEST['action'] ) {
			case self::ACTION_CONNECT_EXISTING:
				return [ $this, 'handle_connect_existing_post' ];

			case self::ACTION_CONNECT_NEW:
				return [ $this, 'handle_connect_new_post' ];

			case self::ACTION_DISCONNECT:
				return [ $this, 'handle_disconnect_post' ];
		}

		return null;
	}

	/**
	 * Returns the post type of the "real" post according to the given one.
	 *
	 * This includes a workaround for auto-drafts.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return string Post type.
	 */
	private function get_real_post_type( WP_Post $post ) {

		if ( 'revision' !== $post->post_type ) {
			return $post->post_type;
		}

		if ( empty( $_POST['post_type'] ) || 'revision' === $_POST['post_type'] ) {
			return $post->post_type;
		}

		if ( is_string( $_POST['post_type'] ) ) {
			return $_POST['post_type'];
		}

		return $post->post_type;
	}
}
