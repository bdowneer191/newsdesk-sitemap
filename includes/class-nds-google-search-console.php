<?php
/**
 * Google Search Console API Client
 *
 * Handles official sitemap submission and indexing status monitoring.
 * Requires user to upload a Google Service Account JSON key.
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NDS_Google_Search_Console {

	private $client;
	private $service;
	private $is_configured = false;

	/**
	 * Initialize the Google API Client
	 * Logic requires the google/apiclient library.
	 */
	public function __construct() {
		if ( ! class_exists( 'Google_Client' ) ) {
			return;
		}

		$credentials_json = get_option( 'nds_gsc_credentials_json', '' );
		if ( empty( $credentials_json ) ) {
			return;
		}

		try {
			$this->client = new Google_Client();
			$this->client->setApplicationName( 'NewsDesk Sitemap' );
			$this->client->setAuthConfig( json_decode( $credentials_json, true ) );
			$this->client->setScopes( array( 'https://www.googleapis.com/auth/webmasters' ) );
			$this->client->setAccessType( 'offline' );

			$this->service = new Google_Service_Webmasters( $this->client );
			$this->is_configured = true;
		} catch ( Exception $e ) {
			NDS_Logger::error( 'GSC Initialization Failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Submit the news sitemap URL to GSC
	 *
	 * @param    string    $sitemap_url    The full URL of the XML sitemap.
	 * @return   bool|WP_Error             True on success.
	 */
	public function submit_sitemap( $sitemap_url ) {
		if ( ! $this->is_configured ) {
			return new WP_Error( 'not_configured', __( 'Google API credentials are not configured.', 'newsdesk-sitemap' ) );
		}

		try {
			$site_url = home_url( '/' );
			$this->service->sitemaps->submit( $site_url, $sitemap_url );
			return true;
		} catch ( Exception $e ) {
			return new WP_Error( 'api_error', $e->getMessage() );
		}
	}

	/**
	 * Fetch real-time crawl status from GSC
	 */
	public function get_sitemap_status( $sitemap_url ) {
		if ( ! $this->is_configured ) {
			return false;
		}

		try {
			$site_url = home_url( '/' );
			$sitemap = $this->service->sitemaps->get( $site_url, $sitemap_url );

			return array(
				'last_submitted'  => $sitemap->getLastSubmitted(),
				'last_downloaded' => $sitemap->getLastDownloaded(),
				'is_pending'      => $sitemap->getIsPending(),
				'errors'          => $sitemap->getErrors(),
				'warnings'        => $sitemap->getWarnings()
			);
		} catch ( Exception $e ) {
			return false;
		}
	}

	public function is_configured() {
		return $this->is_configured;
	}
}