<?php # -*- coding: utf-8 -*-

/**
 * Term translation controller.
 */
class Mlp_Term_Translation_Controller implements Mlp_Updatable {

	/**
	 * @var Mlp_Term_Translation_Selector
	 */
	private $view = null;

	/**
	 * @var Inpsyde_Nonce_Validator
	 */
	private $nonce;

	/**
	 * @var Mlp_Term_Translation_Presenter
	 */
	private $presenter;

	/**
	 * @var Mlp_Content_Relations_Interface
	 */
	private $content_relations;

	/**
	 * @var string
	 */
	private $key_base = 'mlp[term_translation]';

	/**
	 * @param Mlp_Content_Relations_Interface $content_relations
	 */
	public function __construct( Mlp_Content_Relations_Interface $content_relations ) {

		$this->content_relations = $content_relations;

		$this->nonce = Mlp_Nonce_Validator_Factory::create( $this->get_nonce_action(), get_current_blog_id() );
	}

	/**
	 * Returns the nonce action for the current request wrt. the current taxonomy and term, if any.
	 *
	 * @return string
	 */
	private function get_nonce_action() {

		$taxonomy = empty( $_REQUEST['taxonomy'] ) ? '' : (string) $_REQUEST['taxonomy'];

		$term_taxonomy_id = empty( $_REQUEST['tag_ID'] ) ? 0 : (int) $_REQUEST['tag_ID'];

		return "save_{$taxonomy}_translations_$term_taxonomy_id";
	}

	/**
	 * @return bool
	 */
	public function setup() {

		$taxonomies = $this->get_valid_taxonomies();
		if ( ! $taxonomies ) {
			return false;
		}

		$this->activate_term_connector( $taxonomies );

		add_action( 'load-edit-tags.php', array( new Mlp_Term_Fields( $taxonomies, $this ), 'setup' ) );

		return true;
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|void Either a value, or void for actions.
	 */
	public function update( $name ) {

		$view = $this->get_view();

		if ( Mlp_Term_Field_View::ADD_TERM_FIELDSET_ID === $name ) {
			echo $view->get_fieldset_id();

			return true;
		}

		$table_positions = array(
			Mlp_Term_Field_View::ADD_TERM_FIELDS,
			Mlp_Term_Field_View::EDIT_TERM_FIELDS,
		);
		if ( in_array( $name, $table_positions ) ) {
			return $view->print_table();
		}

		$title_positions = array(
			Mlp_Term_Field_View::ADD_TERM_TITLE,
			Mlp_Term_Field_View::EDIT_TERM_TITLE,
		);
		if ( in_array( $name, $title_positions ) ) {
			return $view->print_title();
		}

		return false;
	}

	/**
	 * @return array
	 */
	private function get_valid_taxonomies() {

		/** This filter is documented in inc/post-translator/Mlp_Translation_Metabox.php */
		$post_types = (array) apply_filters( 'mlp_allowed_post_types', array( 'post', 'page' ) );
		if ( ! $post_types ) {
			return array();
		}

		return get_object_taxonomies( $post_types );
	}

	/**
	 * Wire up all necessary term connector methods.
	 *
	 * @param string[] $taxonomies Taxonomy names.
	 *
	 * @return void
	 */
	private function activate_term_connector( array $taxonomies ) {

		$term_connector = new Mlp_Term_Connector( $this->content_relations, $this->nonce, $taxonomies );

		$callback = array( $term_connector, 'change_term_relationships', );

		add_action( 'delete_term', $callback, 10, 3 );

		$post_data = $this->get_post_data();
		if ( $post_data ) {
			$this->activate_switcher();

			$term_connector->set_post_data( $post_data );

			foreach ( array( 'create', 'edit' ) as $action ) {
				add_action( "{$action}_term", $callback, 10, 3 );
			}
		}
	}

	/**
	 * @return array
	 */
	private function get_post_data() {

		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return array();
		}

		if ( empty( $_POST['mlp']['term_translation'] ) ) {
			return array();
		}

		return (array) $_POST['mlp']['term_translation'];
	}

	/**
	 * @return void
	 */
	private function activate_switcher() {

		$switcher = new Mlp_Global_Switcher( Mlp_Global_Switcher::TYPE_POST );

		add_action( 'mlp_before_term_synchronization', array( $switcher, 'strip' ) );

		add_action( 'mlp_after_term_synchronization', array( $switcher, 'fill' ) );
	}

	/**
	 * @return Mlp_Term_Translation_Selector
	 */
	private function get_view() {

		if ( ! is_null( $this->view ) ) {
			return $this->view;
		}

		$this->presenter = new Mlp_Term_Translation_Presenter(
			$this->content_relations,
			$this->nonce,
			$this->key_base
		);

		$this->view = new Mlp_Term_Translation_Selector( $this->presenter );

		return $this->view;
	}
}
