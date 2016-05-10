<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_init', 'mlp_widget_setup' );

add_action( 'widgets_init', [ 'Mlp_Widget', 'widget_register' ] );

/**
 * @param Properties $plugin_data Plugin data.
 *
 * @return void
 */
function mlp_widget_setup( Properties $plugin_data ) {

	Mlp_Widget::insert_asset_instance( $plugin_data->get( 'assets' ) );
}
