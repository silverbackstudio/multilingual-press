<?php # -*- coding: utf-8 -*-

namespace Inpsyde\MultilingualPress\Module\Redirect;

/**
 * Interface for all settings repository implementations.
 *
 * @package Inpsyde\MultilingualPress\Module\Redirect
 * @since   3.0.0
 */
interface SettingsRepository {

	/**
	 * Meta key.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const META_KEY_USER = 'mlp_redirect';

	/**
	 * Option name.
	 *
	 * @since 3.0.0
	 *
	 * @var string
	 */
	const OPTION_SITE = 'inpsyde_multilingual_redirect';

	/**
	 * Returns the redirect setting for the site with the given ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int $site_id Optional. Site ID. Defaults to 0.
	 *
	 * @return bool The redirect setting for the site with the given ID.
	 */
	public function get_site_setting( $site_id = 0 );

	/**
	 * Returns the redirect setting for the user with the given ID.
	 *
	 * @since 3.0.0
	 *
	 * @param int $user_id Optional. User ID. Defaults to 0.
	 *
	 * @return bool The redirect setting for the user with the given ID.
	 */
	public function get_user_setting( $user_id = 0 );
}
