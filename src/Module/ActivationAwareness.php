<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module;

/**
 * Trait for all module service provider implementations that need to be aware of their individual module's activation.
 *
 * @package Inpsyde\MultilingualPress\Module
 * @since   3.0.0
 */
trait ActivationAwareness {

	/**
	 * @var callable
	 */
	private $on_activation_callback;

	/**
	 * Executes the callback to be used in case this service provider's module is active.
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether or not the callback was executed.
	 */
	public function activate() {

		if ( ! is_callable( $this->on_activation_callback ) ) {
			return false;
		}

		call_user_func( $this->on_activation_callback );

		return true;
	}

	/**
	 * Registers the given callback to be executed on the activation of this service provider's module.
	 *
	 * @param callable $callback Callback to be executed on module activation.
	 *
	 * @return void
	 */
	private function on_activation( callable $callback ) {

		$this->on_activation_callback = $callback;
	}
}
