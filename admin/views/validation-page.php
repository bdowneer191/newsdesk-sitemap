<?php
/**
 * Modern Validation Page View
 *
 * @package    NewsDesk_Sitemap
 * @subpackage NewsDesk_Sitemap/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="nds-admin-wrap">
    <div class="nds-header">
        <h1><?php esc_html_e( 'Sitemap Validator', 'newsdesk-sitemap' ); ?></h1>
        <p><?php esc_html_e( 'Verify your sitemap against Google News technical guidelines.', 'newsdesk-sitemap' ); ?></p>
    </div>

    <div class="nds-settings-container">
        <div class="nds-nav">
             <div style="padding:20px;">
                <h3 style="margin-top:0; font-size:14px; text-transform:uppercase; color:#6b7280;"><?php esc_html_e( 'How it works', 'newsdesk-sitemap' ); ?></h3>
                <p style="font-size:13px; color:#4b5563; line-height:1.5;">
                    <?php esc_html_e( 'This tool fetches your live sitemap XML and parses it to check for common errors such as missing required tags, incorrect date formats, or empty content.', 'newsdesk-sitemap' ); ?>
                </p>
             </div>
        </div>

        <div class="nds-content">
            <div class="nds-panel">
                 <h2><?php esc_html_e( 'Live Validation', 'newsdesk-sitemap' ); ?></h2>
                 <div style="padding:10px 0;">
                    <p style="margin-bottom:20px;"><?php esc_html_e( 'Click the button below to start the diagnosis.', 'newsdesk-sitemap' ); ?></p>
                    
                    <button type="button" class="button button-primary" id="nds-run-validation" style="padding:10px 20px !important;">
                        <?php esc_html_e( 'Run Validation Analysis', 'newsdesk-sitemap' ); ?>
                    </button>

                    <div id="nds-validation-results" style="margin-top:30px; display:none;">
                        <!-- Results injected via JS -->
                        <div class="nds-placeholder-loading" style="background:#f3f4f6; padding:20px; border-radius:8px; text-align:center;">
                            <span class="spinner is-active" style="float:none; margin:0;"></span> <?php esc_html_e( 'Analyzing XML structure...', 'newsdesk-sitemap' ); ?>
                        </div>
                    </div>
                 </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#nds-run-validation').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var $results = $('#nds-validation-results');
        
        $btn.prop('disabled', true);
        $results.show().html('<div style="background:#f3f4f6; padding:20px; border-radius:8px; text-align:center; color:#6b7280;"><span class="dashicons dashicons-update" style="font-size:20px; vertical-align:middle; margin-right:5px; animation:spin 2s linear infinite;"></span> ' + nds_ajax.strings.validating + '</div>');
        
        $.ajax({
            url: nds_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nds_validate_sitemap',
                nonce: nds_ajax.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false);
                
                if(response.success) {
                    var html = '<div style="background:#ecfdf5; border:1px solid #10b981; color:#064e3b; padding:15px; border-radius:8px; margin-bottom:15px;">';
                    html += '<strong style="display:block; margin-bottom:5px;"><span class="dashicons dashicons-yes" style="color:#10b981;"></span> ' + response.data.message + '</strong>';
                    html += '<a href="' + response.data.url + '" target="_blank" style="color:#059669; text-decoration:none;">' + response.data.url + '</a>';
                    html += '</div>';
                    $results.html(html);
                } else {
                     var html = '<div style="background:#fef2f2; border:1px solid #ef4444; color:#991b1b; padding:15px; border-radius:8px;">';
                    html += '<strong style="display:block; margin-bottom:5px;"><span class="dashicons dashicons-warning" style="color:#dc2626;"></span> ' + response.data.message + '</strong>';
                    if (response.data.errors) {
                        html += '<ul style="margin:10px 0 0 20px; list-style-type:disc;">';
                        // Handle if errors is string or array (simplified)
                         html += '<li>' + JSON.stringify(response.data.errors) + '</li>';
                        html += '</ul>';
                    } else if (response.data.error) {
                         html += '<p>' + response.data.error + '</p>';
                    }
                    html += '</div>';
                    $results.html(html);
                }
            },
            error: function() {
                $btn.prop('disabled', false);
                 $results.html('<div style="background:#fef2f2; border:1px solid #ef4444; padding:15px; border-radius:8px; color:#b91c1c;">Server Error. Please try again.</div>');
            }
        });
    });
});
</script>
<style>
@keyframes spin { 100% { -webkit-transform: rotate(360deg); transform:rotate(360deg); } }
</style>