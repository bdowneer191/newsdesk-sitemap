<?php
class NDS_Bing_API {
    
    private $api_key;
    private $site_url;
    
    public function __construct() {
        $this->api_key = get_option('nds_bing_api_key', '');
        $this->site_url = home_url();
    }
    
    /**
     * Submit URLs using Bing URL Submission API
     * https://www.bing.com/webmasters/url-submission-api
     */
    public function submit_urls($urls) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', 'Bing API key not configured');
        }
        
        $endpoint = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrlBatch';
        
        $body = array(
            'siteUrl' => $this->site_url,
            'urlList' => array_values($urls) // Ensure numeric array
        );
        
        $response = wp_remote_post($endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'charset' => 'utf-8',
                'apikey' => $this->api_key
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($code === 200) {
            return array(
                'success' => true,
                'message' => 'Submitted ' . count($urls) . ' URLs to Bing'
            );
        } else {
            return new WP_Error('api_error', $body['Message'] ?? 'Unknown error');
        }
    }
}