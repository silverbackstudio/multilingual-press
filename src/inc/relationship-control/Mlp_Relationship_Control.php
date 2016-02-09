<?php # -*- coding: utf-8 -*-

/**
 * Controller for the relationship management above the Advanced Translator.
 */
class Mlp_Relationship_Control implements Mlp_Updatable {

	/**
	 * @var Mlp_Relationship_Control_Data
	 */
	private $data = null;

	/**
	 * @var Inpsyde_Property_List_Interface
	 */
	private $plugin_data;

	/**
	 * Unique prefix to detect our registered actions and form names.
	 *
	 * @var string
	 */
	private $prefix = 'mlp_rc';

	/**
	 * @var Mlp_Relationship_Changer
	 */
	private $relationship_changer = null;

	/**
	 * @param Inpsyde_Property_List_Interface $plugin_data
	 */
	public function __construct( Inpsyde_Property_List_Interface $plugin_data ) {

		$this->plugin_data = $plugin_data;
	}

	/**
	 * Callback for AJAX reconnect.
	 *
	 * @return void
	 */
	public function ajax_reconnect_callback() {

		if ( is_null( $this->relationship_changer ) ) {
			$this->initialize();
		}

		$start = strlen( $this->prefix ) + 1;

		$method = substr( $_REQUEST['action'], $start ) . '_post';

		if ( method_exists( $this->relationship_changer, $method ) ) {
			$this->relationship_changer->$method();
		}

		status_header( 200 );

		die();
	}

	public function initialize() {

		$this->data = new Mlp_Relationship_Control_Data();

		$this->relationship_changer = new Mlp_Relationship_Changer( $this->plugin_data );

		add_action( 'deleted_post', array( $this->relationship_changer, 'delete_relation' ) );

		if ( $this->is_ajax() ) {
			$this->set_up_ajax();
		} else {
			add_action( 'mlp_translation_meta_box_bottom', array( $this, 'set_up_meta_box_handlers' ), 200, 3 );
		}
	}

	/**
	 * Register AJAX callbacks.
	 *
	 * @return void
	 */
	public function set_up_ajax() {

		$callback_type = "{$this->prefix}_remote_post_search" === $_REQUEST['action'] ? 'search' : 'reconnect';

		add_action( "wp_ajax_{$_REQUEST['action']}", array( $this, "ajax_{$callback_type}_callback" ) );
	}

	/**
	 * Create the UI above the Advanced Translator metabox.
	 *
	 * @wp-hook mlp_translation_meta_box_bottom
	 *
	 * @param WP_Post $post
	 * @param int     $remote_site_id
	 * @param WP_Post $remote_post
	 *
	 * @return void
	 */
	public function set_up_meta_box_handlers( WP_Post $post, $remote_site_id, WP_Post $remote_post ) {

		global $pagenow;

		if ( 'post-new.php' === $pagenow ) {
			// Maybe later, for now, we work on existing posts only
			return;
		}

		if ( is_null( $this->data ) ) {
			$this->initialize();
		}

		$this->data->set_ids(
			array(
				'source_post_id' => $post->ID,
				'source_site_id' => get_current_blog_id(),
				'remote_site_id' => $remote_site_id,
				'remote_post_id' => $remote_post->ID,
			)
		);
		$view = new Mlp_Relationship_Control_Meta_Box_View( $this->data, $this );
		$view->render();
	}

	/**
	 * @param string $name
	 *
	 * @return void
	 */
	public function update( $name ) {

		if ( 'default.remote.posts' === $name ) {
			$search = new Mlp_Relationship_Control_Ajax_Search(
				$this->data,
				$this->plugin_data->get( 'content_relations' )
			);
			$search->render();
		}
	}

	/**
	 * Callback for AJAX search.
	 *
	 * @return void
	 */
	public function ajax_search_callback() {

		if ( is_null( $this->data ) ) {
			$this->initialize();
		}

		$search = new Mlp_Relationship_Control_Ajax_Search(
			$this->data,
			$this->plugin_data->get( 'content_relations' )
		);
		$search->send_response();
	}

	/**
	 * Check if this is our AJAX request.
	 *
	 * @return bool
	 */
	private function is_ajax() {

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return false;
		}

		if ( empty( $_REQUEST['action'] ) ) {
			return false;
		}

		return 0 === strpos( $_REQUEST['action'], $this->prefix );
	}
}
