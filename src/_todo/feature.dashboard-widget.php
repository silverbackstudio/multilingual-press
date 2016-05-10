<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'mlp_and_wp_loaded', 'mlp_feature_dashboard_widget' );

/**
 * @param Properties $data Plugin data.
 *
 * @return void
 */
function mlp_feature_dashboard_widget( Properties $data ) {

	$controller = new Mlp_Dashboard_Widget( $data->get( 'site_relations' ) );
	$controller->initialize();
}
