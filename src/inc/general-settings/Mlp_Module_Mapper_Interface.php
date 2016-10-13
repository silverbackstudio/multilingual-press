<?php # -*- coding: utf-8 -*-

use Inpsyde\MultilingualPress\Module\Module;
use Inpsyde\MultilingualPress\Module\ModuleManager;

/**
 * Interface Mlp_Module_Mapper_Interface
 *
 * @version 2014.07.17
 * @author  Inpsyde Gmbh, toscho
 * @license GPL
 */
interface Mlp_Module_Mapper_Interface {

	/**
	 * Constructor
	 *
	 * @param ModuleManager $modules
	 */
	public function __construct( ModuleManager $modules );

	/**
	 * Save module options.
	 *
	 * @return	void
	 */
	public function update_modules();

	/**
	 * Wrapper for the same method of $modules.
	 *
	 * @param int $state
	 * @return Module[]
	 */
	public function get_modules( $state = ModuleManager::MODULE_STATE_ALL );

	/**
	 * Get name for nonce action parameter.
	 *
	 * @return string
	 */
	public function get_nonce_action();
}