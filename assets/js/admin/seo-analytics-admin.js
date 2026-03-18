/**
 * SEO Analytics Admin JavaScript
 * Handles media uploader for social image selection
 */

jQuery(document).ready(function($) {
    
    // WordPress media uploader variable
    let mediaUploader;
    
    // Upload button click handler
    $(document).on('click', '#upload-social-image', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Create the media uploader
        mediaUploader = wp.media({
            title: 'Select Social Share Image',
            button: {
                text: 'Use This Image'
            },
            library: {
                type: 'image'
            },
            multiple: false
        });
        
        // When an image is selected, run a callback
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Update the hidden input with the image URL
            $('#social-image-url').val(attachment.url);
            
            // Update the preview image
            $('#social-image-preview').attr('src', attachment.url);
            
            // Update button text and show remove button
            $('#upload-social-image').text('Change Image');
            
            // Add remove button if it doesn't exist
            if (!$('#remove-social-image').length) {
                $('<button type="button" id="remove-social-image" class="button" style="margin-left: 10px;">Use Default</button>')
                    .insertAfter('#upload-social-image');
            }
        });
        
        // Open the uploader dialog
        mediaUploader.open();
    });
    
    // Remove button click handler
    $(document).on('click', '#remove-social-image', function(e) {
        e.preventDefault();

        // Get the default image URL from the PHP constant
        const defaultImageUrl = fcrmSeoAnalyticsAdmin.defaultImageUrl;

        // Clear the hidden input
        $('#social-image-url').val('');

        // Reset the preview to default image
        $('#social-image-preview').attr('src', defaultImageUrl);

        // Update button text and hide remove button
        $('#upload-social-image').text('Upload Image');
        $(this).remove();
    });

    // Instant Indexing: Check for new tributes
    function checkNewTributes(dryRun) {
        var $results = $('#fcrm-check-results');
        var $dryBtn = $('#fcrm-check-tributes-dry');
        var $submitBtn = $('#fcrm-check-tributes-submit');
        var label = dryRun ? 'Checking...' : 'Checking & submitting...';

        $dryBtn.prop('disabled', true);
        $submitBtn.prop('disabled', true);
        $results.show().html('<p>' + label + '</p>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fcrm_check_new_tributes',
                nonce: fcrmSeoAnalyticsAdmin.indexingNonce || '',
                dry_run: dryRun ? '1' : '0'
            },
            success: function(response) {
                if (response.success) {
                    var d = response.data;
                    var html = '<div style="background: #fff; border: 1px solid #ddd; border-radius: 4px; padding: 12px;">';
                    html += '<p><strong>Total tributes found:</strong> ' + d.total_tributes + '</p>';
                    html += '<p><strong>Previously known:</strong> ' + d.known_tributes + '</p>';
                    html += '<p><strong>New tributes:</strong> ' + d.new_tributes + '</p>';

                    if (d.first_run) {
                        html += '<p style="color: #2271b1;"><em>First run — stored ' + d.total_tributes + ' tributes as baseline. Run again to detect new ones.</em></p>';
                    }

                    if (d.new_urls && d.new_urls.length > 0) {
                        html += '<p><strong>' + (d.dry_run ? 'Would submit:' : 'Submitted:') + '</strong></p>';
                        html += '<ul style="margin: 5px 0 5px 20px; list-style: disc;">';
                        d.new_urls.forEach(function(item) {
                            html += '<li><strong>' + (item.name || 'Unknown') + '</strong> — <code style="font-size: 11px;">' + item.url + '</code></li>';
                        });
                        if (d.new_tributes > 20) {
                            html += '<li><em>...and ' + (d.new_tributes - 20) + ' more</em></li>';
                        }
                        html += '</ul>';
                    } else if (!d.first_run) {
                        html += '<p style="color: #00a32a;">No new tributes detected since last check.</p>';
                    }

                    html += '</div>';
                    $results.html(html);
                } else {
                    $results.html('<p style="color: #d63638;">' + (response.data || 'Check failed') + '</p>');
                }
            },
            error: function() {
                $results.html('<p style="color: #d63638;">Request failed</p>');
            },
            complete: function() {
                $dryBtn.prop('disabled', false).text('Check Now (Dry Run)');
                $submitBtn.prop('disabled', false).text('Check & Submit');
            }
        });
    }

    $(document).on('click', '#fcrm-check-tributes-dry', function(e) {
        e.preventDefault();
        checkNewTributes(true);
    });

    $(document).on('click', '#fcrm-check-tributes-submit', function(e) {
        e.preventDefault();
        if (confirm('This will submit new tribute URLs to enabled indexing APIs. Continue?')) {
            checkNewTributes(false);
        }
    });

    // Instant Indexing: Reset known tributes
    $(document).on('click', '#fcrm-reset-known', function(e) {
        e.preventDefault();
        if (!confirm('Reset the known tributes list? The next check will treat all tributes as new.')) return;

        var $btn = $(this);
        $btn.prop('disabled', true).text('Resetting...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fcrm_reset_known_tributes',
                nonce: fcrmSeoAnalyticsAdmin.indexingNonce || ''
            },
            success: function(response) {
                if (response.success) {
                    $('#fcrm-check-results').show().html('<p style="color: #00a32a;">Known tributes list reset. Run a check to re-detect.</p>');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Reset Known List');
            }
        });
    });

    // Instant Indexing: Test connection
    $(document).on('click', '#fcrm-test-indexing', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $result = $('#fcrm-indexing-test-result');

        $btn.prop('disabled', true).text('Testing...');
        $result.text('');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fcrm_test_indexing',
                nonce: fcrmSeoAnalyticsAdmin.indexingNonce || ''
            },
            success: function(response) {
                if (response.success) {
                    const results = response.data;
                    let msg = [];
                    if (results.google) msg.push('Google: ' + results.google);
                    if (results.indexnow) msg.push('IndexNow: ' + results.indexnow);
                    $result.css('color', '#00a32a').text(msg.join(' | '));
                } else {
                    $result.css('color', '#d63638').text(response.data || 'Test failed');
                }
            },
            error: function() {
                $result.css('color', '#d63638').text('Request failed');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Test Connection');
            }
        });
    });

    // Instant Indexing: Clear log
    $(document).on('click', '#fcrm-clear-indexing-log', function(e) {
        e.preventDefault();
        const $btn = $(this);

        $btn.prop('disabled', true).text('Clearing...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'fcrm_clear_indexing_log',
                nonce: fcrmSeoAnalyticsAdmin.indexingNonce || ''
            },
            success: function(response) {
                if (response.success) {
                    $('.fcrm-indexing-log').fadeOut(300, function() {
                        $(this).replaceWith('<p class="description" style="margin-top: 15px;">Log cleared. New entries will appear here when tributes are detected and submitted.</p>');
                    });
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Clear Log');
            }
        });
    });

}); 