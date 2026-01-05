jQuery(document).ready(function($) {
    
    // Manual Ping Handler
    $('#nds-manual-ping').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text(nds_ajax.strings.validating);
        
        $.ajax({
            url: nds_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nds_manual_ping',
                security: nds_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(nds_ajax.strings.success + '\n' + JSON.stringify(response.data.results));
                } else {
                    alert(nds_ajax.strings.error + ': ' + response.data.message);
                }
            },
            error: function() {
                alert(nds_ajax.strings.error);
            },
            complete: function() {
                $btn.prop('disabled', false).text('Ping Search Engines');
            }
        });
    });

    // Clear Cache Handler
    $('#nds-clear-cache').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: nds_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nds_clear_cache',
                security: nds_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(nds_ajax.strings.success);
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });

    // Validation Runner
    $('#nds-run-validation').on('click', function() {
        const $btn = $(this);
        const $results = $('#nds-validation-results');
        
        $btn.prop('disabled', true).text(nds_ajax.strings.validating);
        $results.html('<p>Validating sitemap...</p>');
        
        $.ajax({
            url: nds_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nds_validate_sitemap',
                security: nds_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $results.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                } else {
                    let errorHtml = '<div class="notice notice-error"><p>' + response.data.message + '</p>';
                    if (response.data.errors) {
                        errorHtml += '<ul>';
                        $.each(response.data.errors, function(i, error) {
                            errorHtml += '<li>' + error + '</li>';
                        });
                        errorHtml += '</ul>';
                    }
                    errorHtml += '</div>';
                    $results.html(errorHtml);
                }
            },
            error: function() {
                $results.html('<div class="notice notice-error"><p>Connection error</p></div>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Run Validation');
            }
        });
    });
});