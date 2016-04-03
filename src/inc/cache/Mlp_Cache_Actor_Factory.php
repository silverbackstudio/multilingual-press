<?php # -*- coding: utf-8 -*-

/**
 * Factory for cache actor objects.
 */
class Mlp_Cache_Actor_Factory {

	/**
	 * Creates a new cache actor object accotding to the given arguments, and returns it.
	 *
	 * @param Mlp_Cache $cache    Cache object.
	 * @param callable  $callback The callback.
	 *
	 * @return Mlp_Cache_Actor
	 */
	public static function create( Mlp_Cache $cache, callable $callback ) {

		return new Mlp_Cache_Actor( $cache, $callback );
	}
}
