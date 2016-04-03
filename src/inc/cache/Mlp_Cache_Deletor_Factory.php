<?php # -*- coding: utf-8 -*-

/**
 * Factory for cache deletor objects.
 */
class Mlp_Cache_Deletor_Factory {

	/**
	 * Creates a new cache deletor object accotding to the given arguments, and returns it.
	 *
	 * @param Mlp_Cache $cache Cache object.
	 * @param string    $key   The cache key.
	 *
	 * @return Mlp_Cache_Deletor
	 */
	public static function create( Mlp_Cache $cache, $key ) {

		return new Mlp_Cache_Deletor( $cache, $key );
	}
}
