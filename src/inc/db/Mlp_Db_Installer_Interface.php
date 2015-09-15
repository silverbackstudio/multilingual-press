<?php # -*- coding: utf-8 -*-

/**
 * Interface for the plugin table installer.
 */
interface Mlp_Db_Installer_Interface {

	/**
	 * @param Mlp_Db_Schema_Interface $schema
	 *
	 * @return void
	 */
	public function install( Mlp_Db_Schema_Interface $schema = NULL );

	/**
	 * @param Mlp_Db_Schema_Interface $schema
	 *
	 * @return FALSE|int
	 */
	public function uninstall( Mlp_Db_Schema_Interface $schema = NULL );

}
