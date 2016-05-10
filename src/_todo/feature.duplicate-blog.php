<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Common\Properties;

add_action( 'inpsyde_mlp_loaded', 'mlp_feature_duplicate_blog' );

/**
 * @param Properties $data Plugin data.
 *
 * @return void
 */
function mlp_feature_duplicate_blog( Properties $data ) {

	global $wpdb;

	$duplicator = new Mlp_Duplicate_Blogs(
		$data->get( 'link_table' ),
		$wpdb,
		new Mlp_Table_Duplicator( $wpdb ),
		$data->get( 'table_list' )
	);
	$duplicator->setup();
}
