<?php # -*- coding: utf-8 -*-

/**
 * Interface for the plugin table installer.
 */
interface Mlp_Db_Installer_Interface {

	/**
	 * Create the table according to the given data.
	 *
	 * @param Mlp_Db_Schema_Interface $schema Table information.
	 *
	 * @return int Number of table operations run during installation.
	 */
	public function install( Mlp_Db_Schema_Interface $schema = null );

	/**
	 * Delete the table.
	 *
	 * @param Mlp_Db_Schema_Interface $schema Table information.
	 *
	 * @return int|bool Number of rows affected/selected or FALSE on error.
	 */
	public function uninstall( Mlp_Db_Schema_Interface $schema = null );
}
