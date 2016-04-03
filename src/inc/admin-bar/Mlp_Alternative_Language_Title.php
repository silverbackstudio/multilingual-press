<?php # -*- coding: utf-8 -*-

/**
 * Main controller for the Alternative Language Title feature.
 */
class Mlp_Alternative_Language_Title {

	/**
	 * @var Mlp_Cache
	 */
	private $cache;

	/**
	 * @var Mlp_Admin_Bar_Customizer
	 */
	private $customizer;

	/**
	 * @var Mlp_Alternative_Language_Title_Module
	 */
	private $module;

	/**
	 * Constructor. Sets up the properties.
	 *
	 * @param Mlp_Alternative_Language_Title_Module $module     Module object.
	 * @param Mlp_Admin_Bar_Customizer              $customizer Admin bar customizer.
	 * @param Mlp_Cache                             $cache      A cache object.
	 */
	public function __construct(
		Mlp_Alternative_Language_Title_Module $module,
		Mlp_Admin_Bar_Customizer $customizer,
		Mlp_Cache $cache
	) {

		$this->module = $module;

		$this->customizer = $customizer;

		$this->cache = $cache;
	}

	/**
	 * Sets up the module, and wires up all functions.
	 *
	 * @return bool
	 */
	public function setup() {

		// TODO: With MultilingualPress 3.0.0, turn update_cache() into a closure.
		$this->cache->register_callback_for_action( array( $this, 'update_cache' ), 'mlp_blogs_save_fields' );

		if ( ! $this->module->setup() ) {
			return false;
		}

		add_filter( 'admin_bar_menu', array( $this->customizer, 'replace_site_nodes' ), 11 );

		if ( ! is_network_admin() ) {
			add_filter( 'admin_bar_menu', array( $this->customizer, 'replace_site_name' ), 31 );
		}

		return true;
	}

	/**
	 * Updates the cache entry for the alternative language title of the updated site.
	 * 
	 * @wp-hook mlp_blogs_save_fields
	 *
	 * @param Mlp_Cache $cache
	 *
	 * @return void
	 */
	public function update_cache( Mlp_Cache $cache ) {

		$site_id = isset( $_REQUEST['id'] ) ? (int) $_REQUEST['id'] : get_current_blog_id();
		if ( 1 > $site_id ) {
			return;
		}

		$titles = $cache->get();
		if ( ! isset( $titles[ $site_id ] ) ) {
			return;
		}

		unset( $titles[ $site_id ] );

		$cache->set( $titles );
	}
}
