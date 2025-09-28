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
    
}); 