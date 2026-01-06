<?php
/**
 * Cache Manager - Multi-layer caching system
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SOURCE: Part-3 of Complete Technical Implementation Guide (ANSP_Cache_Manager logic)
 * IMPLEMENTATION: NDS_Cache_Manager with multi-layer caching and SQL hardening.
 */
class NDS_Cache_Manager {

	private $cache_prefix = 'nds_sitemap_';
	private $cache_duration;
	private $has_object_cache;
	private $cache_backend;

	public function __construct() {
		$this->cache_duration   = (int) get_option( 'nds_cache_duration', 1800 );
		$this->has_object_cache = wp_using_ext_object_cache();
		$this->cache_backend    = $this->detect_cache_backend();
	}

	private function detect_cache_backend() {
		if ( ! get_option( 'nds_enable_object_cache', false ) ) {
			return 'transient';
		}
		if ( class_exists( 'Redis' ) && $this->test_redis_connection() ) {
			return 'redis';
		}
		if ( class_exists( 'Memcached' ) && $this->test_memcached_connection() ) {
			return 'memcached';
		}
		return 'transient';
	}

	private function test_redis_connection() {
		try {
			if ( ! class_exists( 'Redis' ) ) return false;
			$redis = new Redis();
			$connected = @$redis->connect( '127.0.0.1', 6379, 0.5 );
			if ( $connected ) {
				$redis->close();
				return true;
			}
		} catch ( Exception $e ) {
			return false;
		}
		return false;
	}

	private function test_memcached_connection() {
		try {
			if ( ! class_exists( 'Memcached' ) ) return false;
			$memcached = new Memcached();
			$memcached->addServer( '127.0.0.1', 11211 );
			$stats = $memcached->getStats();
			return ! empty( $stats );
		} catch ( Exception $e ) {
			return false;
		}
		return false;
	}

	public function get( $key ) {
		$full_key = $this->cache_prefix . $key;
		switch ( $this->cache_backend ) {
			case 'redis': return $this->get_from_redis( $full_key );
			case 'memcached': return $this->get_from_memcached( $full_key );
			default: return get_transient( $full_key );
		}
	}

	public function set( $key, $value, $expiration = null ) {
		$full_key   = $this->cache_prefix . $key;
		$expiration = ( null !== $expiration ) ? (int) $expiration : $this->cache_duration;
		switch ( $this->cache_backend ) {
			case 'redis': return $this->set_to_redis( $full_key, $value, $expiration );
			case 'memcached': return $this->set_to_memcached( $full_key, $value, $expiration );
			default: return set_transient( $full_key, $value, $expiration );
		}
	}

	public function clear_all() {
		global $wpdb;
		if ( $this->cache_backend === 'redis' ) $this->clear_redis_cache();
		if ( $this->cache_backend === 'memcached' ) $this->clear_memcached_cache();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_' . $wpdb->esc_like( $this->cache_prefix ) . '%', '_transient_timeout_' . $wpdb->esc_like( $this->cache_prefix ) . '%' ) );
		wp_cache_flush();
		return true;
	}

	private function get_from_redis( $key ) {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$value = $redis->get( $key );
			$redis->close();
			return $value !== false ? maybe_unserialize( $value ) : false;
		} catch ( Exception $e ) { return false; }
	}

	private function set_to_redis( $key, $value, $expiration ) {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$result = $redis->setex( $key, $expiration, maybe_serialize( $value ) );
			$redis->close();
			return $result;
		} catch ( Exception $e ) { return false; }
	}

	private function get_from_memcached( $key ) {
		try {
			$mem = new Memcached();
			$mem->addServer( '127.0.0.1', 11211 );
			return $mem->get( $key );
		} catch ( Exception $e ) { return false; }
	}

	private function set_to_memcached( $key, $value, $expiration ) {
		try {
			$mem = new Memcached();
			$mem->addServer( '127.0.0.1', 11211 );
			return $mem->set( $key, $value, $expiration );
		} catch ( Exception $e ) { return false; }
	}

	private function clear_redis_cache() {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$keys = $redis->keys( $this->cache_prefix . '*' );
			if ( ! empty( $keys ) ) $redis->del( $keys );
			$redis->close();
		} catch ( Exception $e ) {}
	}

	private function clear_memcached_cache() {
		try {
			$mem = new Memcached();
			$mem->addServer( '127.0.0.1', 11211 );
			$mem->flush();
		} catch ( Exception $e ) {}
	}
}