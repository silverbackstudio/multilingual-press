<?php # -*- coding: utf-8 -*-

/**
 * Controller for the relationship management above the Advanced Translator.
 */
class Mlp_Relationship_Control implements Mlp_Updatable {

	/**
	 * @var Inpsyde_Property_List_Interface
	 */
	private $plugin_data;

	/**
	 * Unique prefix to detect our registered actions and form names.
	 *
	 * @var string
	 */
	private $prefix = 'mlp_rsc';

	/**
	 * @var Mlp_Relationship_Control_Data
	 */
	private $data;

	/**
	 * @param Inpsyde_Property_List_Interface $plugin_data
	 */
	public function __construct( Inpsyde_Property_List_Interface $plugin_data ) {

		$this->plugin_data = $plugin_data;
	}

	public function initialize() {

		$this->data = new Mlp_Relationship_Control_Data();

		if ( $this->is_ajax() ) {
			$this->set_up_ajax();
		} else {
			add_action( 'mlp_translation_meta_box_bottom', array( $this, 'set_up_meta_box_handlers' ), 200, 3 );
		}
	}

	/**
	 * Check if this is our AJAX request.
	 *
	 * @return bool
	 */
	private function is_ajax() {

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			// No AJAX request
			return FALSE;
		}

		if ( empty( $_REQUEST[ 'action' ] ) ) {
			// Broken AJAX request
			return FALSE;
		}

		// Our AJAX actions start with $this->prefix
		return 0 === strpos( $_REQUEST[ 'action' ], $this->prefix );
	}

	/**
	 * Register AJAX callbacks.
	 *
	 * @return void
	 */
	public function set_up_ajax() {

		$action = $_REQUEST[ 'action' ];

		$callback_type = $action === "{$this->prefix}_search" ? 'search' : 'reconnect';

		add_action( "wp_ajax_$action", array( $this, "ajax_{$callback_type}_callback" ) );
	}

	/**
	 * Callback for AJAX reconnect.
	 *
	 * @return void
	 */
	public function ajax_reconnect_callback() {

		$start = strlen( $this->prefix ) + 1;

		$func = substr( $_REQUEST[ 'action' ], $start );

		$reconnect = new Mlp_Relationship_Changer( $this->plugin_data );
		$result = $reconnect->$func();

		status_header( 200 );

		// Never visible for the user, for debugging only.
		if ( is_scalar( $result ) ) {
			echo $result;
		} else {
			echo '<pre>' . print_r( $result, TRUE ) . '</pre>';
		}

		die;
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
			$this->ajax_search_callback();
		}
	}

	/**
	 * Callback for AJAX search.
	 *
	 * @return void
	 */
	public function ajax_search_callback() {

		$view = new Mlp_Relationship_Control_Ajax_Search( $this->data, $this->plugin_data->get( 'content_relations' ) );
		$view->render();
	}

}
