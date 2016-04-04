<?php # -*- coding: utf-8 -*-

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_alternative_language_title' );

/**
 * Sets up the feature.
 *
 * @param Inpsyde_Property_List_Interface $data Plugin data.
 *
 * @return void
 */
function mlp_feature_alternative_language_title( Inpsyde_Property_List_Interface $data ) {

	// The name of the network option that, amongst other things, holds the alternative language titles.
	$option_name = 'inpsyde_multilingual';

	$cache = new Mlp_WP_Cache( 'alternative_language_titles' );

	// Invalidate the cache when the according network option was deleted.
	$cache->register_deletion_action( "delete_site_option_$option_name" );

	// Update the cache when the according network option (and thus maybe the alternative language title) was updated.
	$cache->register_callback_for_action( 'mlp_update_language_titles_cache', "update_site_option_$option_name" );

	$controller = new Mlp_Alternative_Language_Title(
		new Mlp_Alternative_Language_Title_Module( $data->get( 'module_manager' ) ),
		new Mlp_Admin_Bar_Customizer( $cache ),
		$cache
	);
	$controller->setup();
}

/**
 * Updates the cache entry for the alternative language title of the updated site.
 *
 * @wp-hook mlp_blogs_save_fields
 *
 * @param Mlp_Cache $cache The cache object.
 * @param array     $args  The original arguments passed by the action.
 *
 * @return bool
 */
function mlp_update_language_titles_cache( Mlp_Cache $cache, array $args ) {

	// Check the option value.
	if ( empty( $args[1] ) ) {
		return $cache->set( null );
	}

	$titles = array();

	foreach ( (array) $args[1] as $site_id => $data ) {
		if ( empty( $data['text'] ) ) {
			continue;
		}

		$titles[ $site_id ] = $data['text'];
	}

	return $cache->set( $titles );
}
