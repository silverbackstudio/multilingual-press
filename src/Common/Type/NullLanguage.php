<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Common\Type;

/**
 * Null language implementation.
 *
 * @package Inpsyde\MultilingualPress\Common\Type
 * @since   3.0.0
 */
final class NullLanguage implements Language {

	/**
	 * Checks if the language is written right-to-left (RTL).
	 *
	 * @since 3.0.0
	 *
	 * @return bool Whether or not the language is written right-to-left (RTL).
	 */
	public function is_rtl() {

		return false;
	}

	/**
	 * Returns the language name (or code) according to the given argument.
	 *
	 * @since 3.0.0
	 *
	 * @param string $output Optional. Output type. Defaults to 'native'.
	 *
	 * @return string Language name (or code) according to the given argument.
	 */
	public function name( $output = 'native' ) {

		return '';
	}

	/**
	 * Returns the language priority.
	 *
	 * @since 3.0.0
	 *
	 * @return int Language priority.
	 */
	public function priority() {

		return 0;
	}
}
