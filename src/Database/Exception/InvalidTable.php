<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Database\Exception;

use Exception;

/**
 * Exception to be thrown when an action is to be performed on an invalid table.
 *
 * @package Inpsyde\MultilingualPress\Database\Exception
 * @since   3.0.0
 */
class InvalidTable extends Exception {

	/**
	 * Returns a new exception object.
	 *
	 * @since 3.0.0
	 *
	 * @param string $action Optional. The action to be performed. Defaults to 'install'.
	 *
	 * @return static Exception object.
	 */
	public static function for_action( $action = 'install' ) {

		return new static( sprintf(
			'Cannot %s. Table invalid.',
			$action
		) );
	}
}
