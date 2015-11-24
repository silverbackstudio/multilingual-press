<?php # -*- coding: utf-8 -*-

/**
 * MultilingualPress front controller.
 */
class Multilingual_Press {

	/**
	 * Overloaded instance for plugin data.
	 *
	 * @var Inpsyde_Property_List_Interface
	 */
	private $plugin_data;

	/**
	 * Local path to plugin file.
	 *
	 * @var string
	 */
	private $plugin_file_path;

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Constructor
	 *
	 * @param Inpsyde_Property_List_Interface $data
	 * @param wpdb                            $wpdb
	 */
	public function __construct( Inpsyde_Property_List_Interface $data, wpdb $wpdb = NULL ) {

		if ( NULL === $wpdb ) {
			// Someone has an old Free version active and activates the new Pro on top of that.
			// The old Free version tries now to create an instance of this new version of the class, and the second
			// parameter is missing. This is where we stop.
			return;
		}

		$this->plugin_data = $data;

		$this->wpdb = $wpdb;
	}

	/**
	 * Initial setup handler.
	 *
	 * @return void
	 */
	public function setup() {

		$this->prepare_plugin_data();

		$this->load_assets();

		$this->prepare_helpers();

		// No changes allowed anymore
		$this->plugin_data->freeze();

		require dirname( __FILE__ ) . '/functions.php';

		if ( ! $this->is_active_site() ) {
			return;
		}

		// Hooks and filters
		add_action( 'inpsyde_mlp_loaded', array( $this, 'load_plugin_textdomain' ), 1 );

		// Load modules
		$this->load_features();

		/**
		 * Runs before internal actions are registered.
		 *
		 * @param Inpsyde_Property_List_Interface $plugin_data Plugin data object.
		 * @param wpdb                            $wpdb        Database object.
		 */
		do_action( 'inpsyde_mlp_init', $this->plugin_data, $this->wpdb );

		// Cleanup upon site deletion
		add_filter( 'delete_blog', array( $this, 'delete_site' ), 10, 2 );

		// Check for errors
		add_filter( 'all_admin_notices', array( $this, 'check_for_user_errors_admin_notice' ) );

		add_action( 'wp_loaded', array( $this, 'late_load' ), 0 );

		/**
		 * Runs after internal actions have been registered.
		 *
		 * @param Inpsyde_Property_List_Interface $plugin_data Plugin data object.
		 * @param wpdb                            $wpdb        Database object.
		 */
		do_action( 'inpsyde_mlp_loaded', $this->plugin_data, $this->wpdb );

		if ( is_admin() ) {
			$this->run_admin_actions();
		} else {
			$this->run_frontend_actions();
		}
	}

	/**
	 * @return void
	 */
	private function prepare_plugin_data() {

		$this->plugin_file_path = $this->plugin_data->get( 'plugin_file_path' );

		$this->plugin_data->set( 'assets', new Mlp_Assets( $this->plugin_data->get( 'locations' ) ) );

		$this->plugin_data->set(
			'language_api',
			new Mlp_Language_Api(
				$this->plugin_data,
				'mlp_languages',
				$this->plugin_data->get( 'site_relations' ),
				$this->plugin_data->get( 'content_relations' ),
				$this->wpdb
			)
		);

		$this->plugin_data->set( 'module_manager', new Mlp_Module_Manager( 'state_modules' ) );

		$this->plugin_data->set( 'site_manager', new Mlp_Module_Manager( 'inpsyde_multilingual' ) );

		$this->plugin_data->set( 'table_list', new Mlp_Db_Table_List( $this->wpdb ) );
	}

	/**
	 * Register assets internally.
	 *
	 * @return void
	 */
	public function load_assets() {

		/** @var Mlp_Assets $assets */
		$assets = $this->plugin_data->get( 'assets' );

		$l10n = array(
			'mlpRelationshipControlL10n' => array(
				'unsavedPostRelationships' => __(
					'You have unsaved changes in your post relationships. The changes you made will be lost if you navigate away from this page.',
					'multilingualpress'
				),
				'noPostSelected'           => __( 'Please select a post.', 'multilingualpress' ),
			),
		);
		$assets->add( 'mlp_admin_js', 'admin.js', array( 'jquery' ), $l10n );

		$assets->add( 'mlp_admin_css', 'admin.css' );

		$assets->add( 'mlp_frontend_js', 'frontend.js', array( 'jquery' ) );

		$assets->add( 'mlp_frontend_css', 'frontend.css' );

		add_action( 'init', array( $assets, 'register' ), 0 );
	}

	/**
	 * Define properties and dependencies of the Helpers class.
	 *
	 * @return void
	 */
	private function prepare_helpers() {

		Mlp_Helpers::$content_relations_table = $this->plugin_data->get( 'content_relations_table' );
		Mlp_Helpers::$link_table = $this->plugin_data->get( 'content_relations_table' ); // Backwards compatibility

		Mlp_Helpers::insert_dependency( 'language_api', $this->plugin_data->get( 'language_api' ) );

		Mlp_Helpers::insert_dependency( 'plugin_data', $this->plugin_data );

		Mlp_Helpers::insert_dependency( 'site_relations', $this->plugin_data->get( 'site_relations' ) );
	}

	/**
	 * Check if the current context needs more MultilingualPress actions.
	 *
	 * @return bool
	 */
	private function is_active_site() {

		global $pagenow;

		if ( in_array( $pagenow, array( 'admin-post.php', 'admin-ajax.php' ) ) ) {
			return TRUE;
		}

		if ( is_network_admin() ) {
			return TRUE;
		}

		$site_id = get_current_blog_id();
		$relations = get_site_option( 'inpsyde_multilingual', array() );
		if ( array_key_exists( $site_id, $relations ) ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Find and load plugin features.
	 *
	 * @return string[]
	 */
	protected function load_features() {

		$path = $this->plugin_data->get( 'plugin_dir_path' ) . '/inc';
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$files = glob( "$path/feature.*.php" );
		if ( empty( $files ) ) {
			return array();
		}

		$found = array();

		foreach ( $files as $file ) {
			$found[] = $file;

			require $file;
		}

		return $found;
	}

	/**
	 * @return void
	 */
	private function run_admin_actions() {

		/** @var Mlp_Module_Manager $module_manager */
		$module_manager = $this->plugin_data->get( 'module_manager' );
		if ( $module_manager->has_modules() ) {
			$this->load_module_settings_page();
		}

		/** @var Mlp_Module_Manager $module_manager */
		$site_manager = $this->plugin_data->get( 'site_manager' );
		if ( $site_manager->has_modules() ) {
			$this->load_site_settings_page();
		}

		new Mlp_Network_Site_Settings_Controller( $this->plugin_data );

		new Mlp_Network_New_Site_Controller(
			$this->plugin_data->get( 'language_api' ),
			$this->plugin_data->get( 'site_relations' )
		);
	}

	/**
	 * Create network settings page.
	 *
	 * @return void
	 */
	private function load_module_settings_page() {

		$settings = new Mlp_General_Settingspage(
			$this->plugin_data->get( 'module_manager' ),
			$this->plugin_data->get( 'assets' )
		);
		add_action( 'plugins_loaded', array( $settings, 'setup' ), 8 );
	}

	/**
	 * Create site settings page.
	 *
	 * @return void
	 */
	private function load_site_settings_page() {

		$settings = new Mlp_General_Settingspage(
			$this->plugin_data->get( 'site_manager' ),
			$this->plugin_data->get( 'assets' )
		);
		$settings->setup();
		add_action( 'plugins_loaded', array( $settings, 'setup' ), 8 );
	}

	/**
	 * @return void
	 */
	private function run_frontend_actions() {

		// Use correct language for html element
		add_filter( 'language_attributes', array( $this, 'language_attributes' ) );

		$hreflang = new Mlp_Hreflang_Header_Output( $this->plugin_data->get( 'language_api' ) );
		add_action( 'template_redirect', array( $hreflang, 'http_header', ) );
		add_action( 'wp_head', array( $hreflang, 'wp_head', ) );
	}

	/**
	 * @return void
	 */
	public function late_load() {

		/**
		 * Late loading event for MultilingualPress.
		 *
		 * @param Inpsyde_Property_List_Interface $plugin_data Plugin data object.
		 * @param wpdb                            $wpdb        Database object.
		 */
		do_action( 'mlp_and_wp_loaded', $this->plugin_data, $this->wpdb );

		$activator = new Mlp_Activator();
		$activator->activate( $this->plugin_data, $this->wpdb );
	}

	/**
	 * Load the localization
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {

		$path = plugin_basename( $this->plugin_file_path );
		$path = dirname( $path ) . $this->plugin_data->get( 'text_domain_path' );

		load_plugin_textdomain( 'multilingualpress', FALSE, $path );
	}

	/**
	 * Clean up the according tables and remove the deleted site from the 'inpsyde_multilingual' site option.
	 *
	 * @wp-hook delete_blog
	 *
	 * @param int $site_id ID of the deleted site.
	 *
	 * @return void
	 */
	public function delete_site( $site_id ) {

		/** @var Mlp_Site_Relations $site_relations */
		$site_relations = $this->plugin_data->get( 'site_relations' );
		$site_relations->delete_relation( $site_id );

		/** @var Mlp_Content_Relations $content_relations */
		$content_relations = $this->plugin_data->get( 'content_relations' );
		$content_relations->delete_all_relations_for_site( $site_id );

		$sites = (array) get_site_option( 'inpsyde_multilingual', array() );
		if ( isset( $sites[ $site_id ] ) ) {
			unset( $sites[ $site_id ] );

			update_site_option( 'inpsyde_multilingual', $sites );
		}
	}

	/**
	 * Use the current blog's language for the html tag.
	 *
	 * @wp-hook language_attributes
	 *
	 * @param string $output Language attributes HTML.
	 *
	 * @return string
	 */
	public function language_attributes( $output ) {

		$site_language = Mlp_Helpers::get_current_blog_language();
		if ( ! $site_language ) {
			return $output;
		}

		$language = get_bloginfo( 'language' );

		$site_language = str_replace( '_', '-', $site_language );

		return str_replace( $language, $site_language, $output );
	}

	/**
	 * Check for errors.
	 *
	 * @return bool
	 */
	public function check_for_user_errors() {

		return $this->check_for_errors();
	}

	/**
	 * Check for errors.
	 *
	 * @return bool
	 */
	public function check_for_errors() {

		if ( defined( 'DOING_AJAX' ) ) {
			return FALSE;
		}

		if ( is_network_admin() ) {
			return FALSE;
		}

		// Get blogs related to the current blog
		$all_blogs = get_site_option( 'inpsyde_multilingual', array() );
		if ( 1 > count( $all_blogs ) && is_super_admin() ) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * In case of errors, render error message.
	 *
	 * @return void
	 */
	public function check_for_user_errors_admin_notice() {

		if ( ! $this->check_for_errors() ) {
			return;
		}
		?>
		<div class="error">
			<p>
				<?php
				_e(
					'You didn\'t setup any site relationships. You have to setup these first to use MultilingualPress. Please go to Network Admin &raquo; Sites &raquo; and choose a site to edit. Then go to the tab MultilingualPress and set up the relationships.',
					'multilingualpress'
				);
				?>
			</p>
		</div>
		<?php
	}

}
