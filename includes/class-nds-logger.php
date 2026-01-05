<?php
/**
 * Debug Logger for Development
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class NDS_Logger {
    private static $log_file;

    public static function init() {
        $upload_dir = wp_upload_dir();
        self::$log_file = $upload_dir['basedir'] . '/nds-debug.log';
    }

    public static function log($message, $context = array()) {
        if (!self::$log_file) self::init();
        
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = sprintf(
            "[%s] %s %s\n",
            $timestamp,
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents(self::$log_file, $log_entry, FILE_APPEND);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('NDS: ' . $message);
        }
    }

    public static function error($message, $context = array()) {
        self::log("ERROR: " . $message, $context);
    }

    public static function info($message, $context = array()) {
        self::log("INFO: " . $message, $context);
    }
}