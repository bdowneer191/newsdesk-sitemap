<?php
/**
 * Cache Manager - Multi-layer caching system
 *
 * This class handles sitemap data persistence using WordPress Transients
 * with optional fallbacks to Redis or Memcached if available.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

/**
 * SOURCE: Part-3 of Complete Technical Implementation Guide (ANSP_Cache_Manager logic)
 * IMPLEMENTATION: NDS_Cache_Manager with multi-layer caching and SQL hardening.
 */

// Exit if accessed directly - Security measure
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Cache_Manager {

	/**
	 * Cache prefix to avoid conflicts
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $cache_prefix = 'nds_sitemap_';

	/**
	 * Cache duration in seconds
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      int
	 */
	private $cache_duration;

	/**
	 * Whether object cache is available
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      bool
	 */
	private $has_object_cache;

	/**
	 * Cache backend type (redis, memcached, or transient)
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $cache_backend;

	/**
	 * Initialize the cache manager
	 * [cite: 4975-4985]
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->cache_duration   = (int) get_option( 'nds_cache_duration', 1800 );
		$this->has_object_cache = wp_using_ext_object_cache();
		$this->cache_backend    = $this->detect_cache_backend();
	}

	/**
	 * Detect available cache backend
	 * [cite: 4991-5015]
	 *
	 * @since    1.0.0
	 * @return   string    Cache backend type
	 */
	private function detect_cache_backend() {
		// If object cache is disabled in settings, always use transients
		if ( ! get_option( 'nds_enable_object_cache', false ) ) {
			return 'transient';
		}

		// Check for Redis extension and connection
		if ( class_exists( 'Redis' ) && $this->test_redis_connection() ) {
			return 'redis';
		}

		// Check for Memcached extension and connection
		if ( class_exists( 'Memcached' ) && $this->test_memcached_connection() ) {
			return 'memcached';
		}

		return 'transient';
	}

	/**
	 * Test Redis connection
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	private function test_redis_connection() {
		try {
			$redis = new Redis();
			// Default local connection check
			$connected = @$redis->connect( '127.0.0.1', 6379, 0.5 );
			if ( $connected ) {
				$redis->close();
				return true;
			}
		} catch ( Exception $e ) {
			// Fail silently to backend fallback
		}
		return false;
	}

	/**
	 * Test Memcached connection
	 *
	 * @since    1.0.0
	 * @return   bool
	 */
	private function test_memcached_connection() {
		try {
			$memcached = new Memcached();
			$memcached->addServer( '127.0.0.1', 11211 );
			$stats = $memcached->getStats();
			return ! empty( $stats );
		} catch ( Exception $e ) {
			// Fail silently to backend fallback
		}
		return false;
	}

	/**
	 * Get cached data
	 * [cite: 5045-5065]
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key
	 * @return   mixed             Cached data or false if not found
	 */
	public function get( $key ) {
		$full_key = $this->get_cache_key( $key );

		switch ( $this->cache_backend ) {
			case 'redis':
				return $this->get_from_redis( $full_key );
			case 'memcached':
				return $this->get_from_memcached( $full_key );
			case 'transient':
			default:
				return get_transient( $full_key );
		}
	}

	/**
	 * Set cached data
	 * [cite: 5071-5095]
	 *
	 * @since    1.0.0
	 * @param    string    $key           Cache key
	 * @param    mixed     $value         Data to cache
	 * @param    int       $expiration    Expiration in seconds
	 * @return   bool                     Success status
	 */
	public function set( $key, $value, $expiration = null ) {
		$full_key   = $this->get_cache_key( $key );
		$expiration = ( null !== $expiration ) ? (int) $expiration : $this->cache_duration;

		switch ( $this->cache_backend ) {
			case 'redis':
				return $this->set_to_redis( $full_key, $value, $expiration );
			case 'memcached':
				return $this->set_to_memcached( $full_key, $value, $expiration );
			case 'transient':
			default:
				return set_transient( $full_key, $value, $expiration );
		}
	}

	/**
	 * Delete cached data
	 * [cite: 5101-5115]
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key
	 * @return   bool              Success status
	 */
	public function delete( $key ) {
		$full_key = $this->get_cache_key( $key );

		switch ( $this->cache_backend ) {
			case 'redis':
				return $this->delete_from_redis( $full_key );
			case 'memcached':
				return $this->delete_from_memcached( $full_key );
			case 'transient':
			default:
				return delete_transient( $full_key );
		}
	}

	/**
	 * Clear all plugin caches
	 * [cite: 5121-5145]
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function clear_all() {
		global $wpdb;

		switch ( $this->cache_backend ) {
			case 'redis':
				$this->clear_redis_cache();
				break;
			case 'memcached':
				$this->clear_memcached_cache();
				break;
		}

		// Always clear transients as fallback/primary storage
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				'_transient_' . $wpdb->esc_like( $this->cache_prefix ) . '%',
				'_transient_timeout_' . $wpdb->esc_like( $this->cache_prefix ) . '%'
			)
		);

		wp_cache_flush();

		return true;
	}

	/**
	 * Redis implementation methods
	 */
	private function get_from_redis( $key ) {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$value = $redis->get( $key );
			$redis->close();
			return $value !== false ? maybe_unserialize( $value ) : false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	private function set_to_redis( $key, $value, $expiration ) {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$result = $redis->setex( $key, $expiration, maybe_serialize( $value ) );
			$redis->close();
			return $result;
		} catch ( Exception $e ) {
			return false;
		}
	}

	private function delete_from_redis( $key ) {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$result = $redis->del( $key );
			$redis->close();
			return $result > 0;
		} catch ( Exception $e ) {
			return false;
		}
	}

	private function clear_redis_cache() {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$keys = $redis->keys( $this->cache_prefix . '*' );
			if ( ! empty( $keys ) ) {
				$redis->del( $keys );
			}
			$redis->close();
		} catch ( Exception $e ) {
			// Silent fail
		}
	}

	/**
	 * Memcached implementation methods
	 */
	private function get_from_memcached( $key ) {
		try {
			$mem = new Memcached();
			$mem->addServer( '127.0.0.1', 11211 );
			return $mem->get( $key );
		} catch ( Exception $e ) {
			return false;
		}
	}

	private function set_to_memcached( $key, $value, $expiration ) {
		try {
			$mem = new Memcached();
			$mem->addServer( '127.0.0.1', 11211 );
			return $mem->set( $key, $value, $expiration );
		} catch ( Exception $e ) {
			return false;
		}
	}

	private function delete_from_memcached( $key ) {
		try {
			$mem = new Memcached();
			$mem->addServer( '127.0.0.1', 11211 );
			return $mem->delete( $key );
		} catch ( Exception $e ) {
			return false;
		}
	}

	private function clear_memcached_cache() {
		try {
			$mem = new Memcached();
			$mem->addServer( '127.0.0.1', 11211 );
			$mem->flush();
		} catch ( Exception $e ) {
			// Silent fail
		}
	}

	/**
	 * Utility function to generate cache key
	 *
	 * @since    1.0.0
	 * @param    string    $key    Base key
	 * @return   string            Full key
	 */
	private function get_cache_key( $key ) {
		return $this->cache_prefix . $key;
	}

	/**
	 * Get cache statistics
	 * [cite: 5315-5330]
	 *
	 * @since    1.0.0
	 * @return   array    Cache stats
	 */
	public function get_stats() {
		return array(
			'backend'          => $this->cache_backend,
			'duration'         => $this->cache_duration,
			'has_object_cache' => $this->has_object_cache,
			'total_keys'       => $this->count_cached_keys(),
		);
	}

	/**
	 * Count total cached keys for this plugin
	 *
	 * @since    1.0.0
	 * @return   int
	 */
	private function count_cached_keys() {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} 
				WHERE option_name LIKE %s",
				'_transient_' . $wpdb->esc_like( $this->cache_prefix ) . '%'
			)
		);
	}
}