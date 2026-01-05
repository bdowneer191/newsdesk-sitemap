jQuery(document).ready(function ($) {

    // Manual Ping Handler
    $('#nds-manual-ping').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var originalText = $btn.text();

        $btn.prop('disabled', true).text(nds_ajax.strings.validating);

        $.ajax({
            url: nds_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nds_manual_ping',
                nonce: nds_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $btn.text(nds_ajax.strings.success);
                    setTimeout(function () {
                        $btn.text(originalText).prop('disabled', false);
                    }, 2000);
                } else {
                    alert(response.data.message || nds_ajax.strings.error);
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function () {
                alert(nds_ajax.strings.error);
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });

    // Clear Cache Handler
    $('#nds-clear-cache').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var originalText = $btn.text();

        $btn.prop('disabled', true).text('Clearing...');

        $.ajax({
            url: nds_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'nds_clear_cache',
                nonce: nds_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    $btn.text('Cleared!');
                    setTimeout(function () {
                        $btn.text(originalText).prop('disabled', false);
                    }, 2000);
                } else {
                    alert(response.data.message);
                    $btn.text(originalText).prop('disabled', false);
                }
            }
        });
    });

    // Tab Navigation for Settings (if using JS tabs)
    // Currently using PHP GET params, but we could enhance this to be instant
});
