<?php
/**
 * NewsDesk Sitemap - Missing File Diagnostic Tool
 * Upload this to: /wp-content/plugins/newsdesk-sitemap/debug-check.php
 * Access via browser: https://yoursite.com/wp-content/plugins/newsdesk-sitemap/debug-check.php
 */

header( 'Content-Type: text/plain' );

echo "Checking NewsDesk Sitemap File Structure...\n\n";

$base_dir = __DIR__;
$missing_files = 0;

// List of ALL required files defined in class-nds-core.php
$required_files = array(
    // Root
    '/newsdesk-sitemap.php',
    
    // Includes (Phase 1-4)
    '/includes/class-nds-activator.php',
    '/includes/class-nds-deactivator.php',
    '/includes/class-nds-loader.php',
    '/includes/class-nds-i18n.php',
    '/includes/class-nds-core.php',
    '/includes/class-nds-sitemap-generator.php',
    '/includes/class-nds-news-schema.php',
    '/includes/class-nds-ping-service.php',
    '/includes/class-nds-cache-manager.php',
    '/includes/class-nds-validation.php',
    '/includes/class-nds-analytics.php',
    '/includes/class-nds-indexnow-client.php',
    '/includes/class-nds-query-optimizer.php',
    '/includes/class-nds-security.php',

    // Admin (Phase 5)
    '/admin/class-nds-admin-settings.php',
    '/admin/class-nds-admin-dashboard.php',
    '/admin/class-nds-admin-validation.php',
    '/admin/class-nds-admin-logs.php',

    // Meta Boxes (Fix Phase)
    '/admin/meta-boxes/class-nds-breaking-news-meta.php',
    '/admin/meta-boxes/class-nds-genre-meta.php',
    '/admin/meta-boxes/class-nds-stock-ticker-meta.php',

    // Public (Fix Phase)
    '/public/class-nds-public-sitemap.php',
);

foreach ( $required_files as $file ) {
    if ( file_exists( $base_dir . $file ) ) {
        echo "[OK] Found: $file\n";
    } else {
        echo "[ERROR] MISSING: $file\n";
        $missing_files++;
    }
}

echo "\n---------------------------------------------------\n";
if ( $missing_files === 0 ) {
    echo "SUCCESS: All required files are present.\n";
    echo "If activation still fails, check your PHP Error Logs for syntax errors.";
} else {
    echo "FAILURE: You are missing $missing_files files.\n";
    echo "Please create the missing files listed above to fix the Fatal Error.";
}