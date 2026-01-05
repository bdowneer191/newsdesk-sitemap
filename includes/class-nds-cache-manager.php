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

class NDS_Cache_Manager {

	/**
	 * Cache prefix to avoid conflicts
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string
	 */
	private $cache_prefix = 'nds_';

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
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->cache_duration   = get_option( 'nds_cache_duration', 1800 );  // 30 minutes default
		$this->has_object_cache = wp_using_ext_object_cache();
		$this->cache_backend    = $this->detect_cache_backend();
	}

	/**
	 * Detect available cache backend
	 *
	 * @since    1.0.0
	 * @return   string    Cache backend type
	 */
	private function detect_cache_backend() {
		// Check if object cache is explicitly enabled in settings
		if ( ! get_option( 'nds_enable_object_cache', false ) ) {
			return 'transient';
		}

		// Check for Redis
		if ( class_exists( 'Redis' ) && $this->test_redis_connection() ) {
			return 'redis';
		}

		// Check for Memcached
		if ( class_exists( 'Memcached' ) && $this->test_memcached_connection() ) {
			return 'memcached';
		}

		// Fall back to WordPress transients
		return 'transient';
	}

	/**
	 * Test Redis connection
	 *
	 * @since    1.0.0
	 * @return   bool    Connection successful
	 */
	private function test_redis_connection() {
		try {
			$redis = new Redis();
			$connected = $redis->connect( '127.0.0.1', 6379, 1 );
			if ( $connected ) {
				$redis->close();
				return true;
			}
		} catch ( Exception $e ) {
			error_log( 'NDS Redis connection failed: ' . $e->getMessage() );
		}
		return false;
	}

	/**
	 * Test Memcached connection
	 *
	 * @since    1.0.0
	 * @return   bool    Connection successful
	 */
	private function test_memcached_connection() {
		try {
			$memcached = new Memcached();
			$memcached->addServer( '127.0.0.1', 11211 );
			$stats = $memcached->getStats();
			return ! empty( $stats );
		} catch ( Exception $e ) {
			error_log( 'NDS Memcached connection failed: ' . $e->getMessage() );
		}
		return false;
	}

	/**
	 * Get cached data
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key
	 * @return   mixed             Cached data or false if not found
	 */
	public function get( $key ) {
		$key = $this->get_cache_key( $key );

		switch ( $this->cache_backend ) {
			case 'redis':
				return $this->get_from_redis( $key );

			case 'memcached':
				return $this->get_from_memcached( $key );

			case 'transient':
			default:
				return get_transient( $key );
		}
	}

	/**
	 * Set cached data
	 *
	 * @since    1.0.0
	 * @param    string    $key           Cache key
	 * @param    mixed     $value         Data to cache
	 * @param    int       $expiration    Expiration time in seconds (optional)
	 * @return   bool                     Success status
	 */
	public function set( $key, $value, $expiration = null ) {
		$key        = $this->get_cache_key( $key );
		$expiration = $expiration ?? $this->cache_duration;

		switch ( $this->cache_backend ) {
			case 'redis':
				return $this->set_to_redis( $key, $value, $expiration );

			case 'memcached':
				return $this->set_to_memcached( $key, $value, $expiration );

			case 'transient':
			default:
				return set_transient( $key, $value, $expiration );
		}
	}

	/**
	 * Delete cached data
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key
	 * @return   bool              Success status
	 */
	public function delete( $key ) {
		$key = $this->get_cache_key( $key );

		switch ( $this->cache_backend ) {
			case 'redis':
				return $this->delete_from_redis( $key );

			case 'memcached':
				return $this->delete_from_memcached( $key );

			case 'transient':
			default:
				return delete_transient( $key );
		}
	}

	/**
	 * Clear all plugin caches
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	public function clear_all() {
		global $wpdb;

		switch ( $this->cache_backend ) {
			case 'redis':
				return $this->clear_redis_cache();

			case 'memcached':
				return $this->clear_memcached_cache();

			case 'transient':
			default:
				// Delete all transients with our prefix
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->options}
						WHERE option_name LIKE %s
						OR option_name LIKE %s",
						'_transient_' . $this->cache_prefix . '%',
						'_transient_timeout_' . $this->cache_prefix . '%'
					)
				);

				// Clear WordPress object cache
				wp_cache_flush();

				return true;
		}
	}

	/**
	 * Get data from Redis
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key
	 * @return   mixed             Cached data or false
	 */
	private function get_from_redis( $key ) {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$value = $redis->get( $key );
			$redis->close();

			return $value !== false ? maybe_unserialize( $value ) : false;
		} catch ( Exception $e ) {
			error_log( 'NDS Redis get error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Set data to Redis
	 *
	 * @since    1.0.0
	 * @param    string    $key           Cache key
	 * @param    mixed     $value         Data to cache
	 * @param    int       $expiration    Expiration in seconds
	 * @return   bool                     Success status
	 */
	private function set_to_redis( $key, $value, $expiration ) {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$result = $redis->setex( $key, $expiration, maybe_serialize( $value ) );
			$redis->close();

			return $result;
		} catch ( Exception $e ) {
			error_log( 'NDS Redis set error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Delete data from Redis
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key
	 * @return   bool              Success status
	 */
	private function delete_from_redis( $key ) {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );
			$result = $redis->del( $key );
			$redis->close();

			return $result > 0;
		} catch ( Exception $e ) {
			error_log( 'NDS Redis delete error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Clear all Redis cache with our prefix
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	private function clear_redis_cache() {
		try {
			$redis = new Redis();
			$redis->connect( '127.0.0.1', 6379 );

			// Get all keys with our prefix
			$keys = $redis->keys( $this->cache_prefix . '*' );

			if ( ! empty( $keys ) ) {
				$redis->del( $keys );
			}

			$redis->close();
			return true;
		} catch ( Exception $e ) {
			error_log( 'NDS Redis clear error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Get data from Memcached
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key
	 * @return   mixed             Cached data or false
	 */
	private function get_from_memcached( $key ) {
		try {
			$memcached = new Memcached();
			$memcached->addServer( '127.0.0.1', 11211 );
			$value = $memcached->get( $key );

			return $value !== false ? $value : false;
		} catch ( Exception $e ) {
			error_log( 'NDS Memcached get error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Set data to Memcached
	 *
	 * @since    1.0.0
	 * @param    string    $key           Cache key
	 * @param    mixed     $value         Data to cache
	 * @param    int       $expiration    Expiration in seconds
	 * @return   bool                     Success status
	 */
	private function set_to_memcached( $key, $value, $expiration ) {
		try {
			$memcached = new Memcached();
			$memcached->addServer( '127.0.0.1', 11211 );

			return $memcached->set( $key, $value, $expiration );
		} catch ( Exception $e ) {
			error_log( 'NDS Memcached set error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Delete data from Memcached
	 *
	 * @since    1.0.0
	 * @param    string    $key    Cache key
	 * @return   bool              Success status
	 */
	private function delete_from_memcached( $key ) {
		try {
			$memcached = new Memcached();
			$memcached->addServer( '127.0.0.1', 11211 );

			return $memcached->delete( $key );
		} catch ( Exception $e ) {
			error_log( 'NDS Memcached delete error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Clear all Memcached cache with our prefix
	 *
	 * @since    1.0.0
	 * @return   bool    Success status
	 */
	private function clear_memcached_cache() {
		try {
			$memcached = new Memcached();
			$memcached->addServer( '127.0.0.1', 11211 );

			// Memcached doesn't support wildcard deletes
			// So we flush all (only if this is a dedicated instance)
			return $memcached->flush();
		} catch ( Exception $e ) {
			error_log( 'NDS Memcached clear error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Generate full cache key with prefix
	 *
	 * @since    1.0.0
	 * @param    string    $key    Base key
	 * @return   string            Full cache key
	 */
	private function get_cache_key( $key ) {
		return $this->cache_prefix . $key;
	}

	/**
	 * Get cache statistics
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
	 * Count total cached keys
	 *
	 * @since    1.0.0
	 * @return   int    Number of cached keys
	 */
	private function count_cached_keys() {
		global $wpdb;

		switch ( $this->cache_backend ) {
			case 'redis':
				try {
					$redis = new Redis();
					$redis->connect( '127.0.0.1', 6379 );
					$keys = $redis->keys( $this->cache_prefix . '*' );
					$redis->close();
					return count( $keys );
				} catch ( Exception $e ) {
					return 0;
				}

			case 'memcached':
				return 0;

			case 'transient':
			default:
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->options}
						WHERE option_name LIKE %s",
						'_transient_' . $this->cache_prefix . '%'
					)
				);
		}
	}
}