/**
 * FCRM UI Styling - Admin Interface JavaScript
 * 
 * Handles color pickers, range sliders, scheme application, and live preview updates.
 */

jQuery(document).ready(function($) {
    'use strict';

    // Initialize color pickers
    function initColorPickers() {
        $('.fcrm-color-picker').wpColorPicker({
            change: function(event, ui) {
                // Optional: Add live preview here
                updatePreview();
            },
            clear: function() {
                updatePreview();
            }
        });
    }

    // Initialize range sliders
    function initRangeSliders() {
        $('.fcrm-range-slider').on('input', function() {
            const $slider = $(this);
            const $valueDisplay = $slider.next('.range-value');
            let value = $slider.val();
            
            // Add appropriate unit
            if ($slider.attr('name').includes('radius') || $slider.attr('name').includes('width')) {
                value += 'px';
            } else if ($slider.attr('name').includes('gap') || $slider.attr('name').includes('padding')) {
                value += 'rem';
            } else if ($slider.attr('name').includes('scale') || $slider.attr('name').includes('opacity')) {
                value += '%';
            }
            
            $valueDisplay.text(value);
            updatePreview();
        });
    }

    // Handle font inheritance toggle
    function initFontInheritance() {
        $('input[name="fcrm_ui_font_inherit"]').on('change', function() {
            const $fontFamilyRow = $('.font-family-row');
            if ($(this).is(':checked')) {
                $fontFamilyRow.fadeOut();
            } else {
                $fontFamilyRow.fadeIn();
            }
            updatePreview();
        });
    }

    // Handle color scheme application
    function initColorSchemes() {
        $('.apply-scheme').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const scheme = $button.data('scheme');
            const originalText = $button.text();
            
            $button.text('Applying...').prop('disabled', true);
            
            $.ajax({
                url: fcrm_ui_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'fcrm_apply_color_scheme',
                    scheme: scheme,
                    nonce: fcrm_ui_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update color picker values
                        const schemeData = fcrm_ui_ajax.schemes[scheme];
                        updateColorPickers(schemeData);
                        
                        // Show success message
                        showNotice('Color scheme applied successfully!', 'success');
                        
                        // Update preview
                        updatePreview();
                    } else {
                        showNotice('Failed to apply color scheme: ' + response.data, 'error');
                    }
                },
                error: function() {
                    showNotice('Error applying color scheme. Please try again.', 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        });
    }

    // Update color picker values
    function updateColorPickers(schemeData) {
        $('input[name="fcrm_ui_primary_color"]').wpColorPicker('color', schemeData.primary);
        $('input[name="fcrm_ui_secondary_color"]').wpColorPicker('color', schemeData.secondary);
        $('input[name="fcrm_ui_accent_color"]').wpColorPicker('color', schemeData.accent);
        $('input[name="fcrm_ui_background_color"]').wpColorPicker('color', schemeData.background);
        $('input[name="fcrm_ui_card_background"]').wpColorPicker('color', schemeData.card_background);
        $('input[name="fcrm_ui_text_color"]').wpColorPicker('color', schemeData.text);
        $('input[name="fcrm_ui_border_color"]').wpColorPicker('color', schemeData.border);
    }

    // Show admin notice
    function showNotice(message, type = 'info') {
        const noticeClass = type === 'error' ? 'notice-error' : 'notice-success';
        const $notice = $(`
            <div class="notice ${noticeClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);
        
        $('.fcrm-ui-styling-settings').prepend($notice);
        
        // Auto-dismiss after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
        
        // Manual dismiss
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    // Update live preview (placeholder for future enhancement)
    function updatePreview() {
        // This could be enhanced to show a live preview of changes
        // For now, we'll just mark that changes have been made
        if (!$('.settings-changed-notice').length) {
            $('.fcrm-ui-styling-settings').prepend(`
                <div class="notice notice-info settings-changed-notice">
                    <p><strong>Preview:</strong> Save settings to see changes on your tribute pages.</p>
                </div>
            `);
        }
    }

    // Enhanced scheme preset interaction
    function initSchemePresets() {
        $('.scheme-preset').on('mouseenter', function() {
            $(this).addClass('hover');
        }).on('mouseleave', function() {
            $(this).removeClass('hover');
        });
        
        // Add visual feedback for current scheme
        const currentScheme = $('input[name="fcrm_ui_color_scheme"]').val();
        if (currentScheme) {
            $(`.scheme-preset[data-scheme="${currentScheme}"]`).addClass('active');
        }
    }

    // Form validation
    function initFormValidation() {
        $('input[type="range"]').on('change', function() {
            const value = parseFloat($(this).val());
            const min = parseFloat($(this).attr('min'));
            const max = parseFloat($(this).attr('max'));
            
            if (value < min || value > max) {
                $(this).val(Math.max(min, Math.min(max, value)));
                updatePreview();
            }
        });
    }

    // Collapsible sections
    function initCollapsibleSections() {
        $('.settings-section h3').on('click', function() {
            const $section = $(this).parent();
            const $content = $section.find('.section-content');
            
            $content.slideToggle(300);
            $section.toggleClass('collapsed');
        });
    }

    // Keyboard accessibility
    function initKeyboardAccessibility() {
        $('.apply-scheme').on('keydown', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).click();
            }
        });
        
        $('.scheme-preset').on('keydown', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).find('.apply-scheme').click();
            }
        });
    }

    // Settings form auto-save (optional enhancement)
    function initAutoSave() {
        let saveTimeout;
        
        $('.fcrm-ui-styling-settings input, .fcrm-ui-styling-settings select').on('change', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                // Could implement auto-save here
                console.log('Settings changed - ready for auto-save');
            }, 2000);
        });
    }

    // Responsive preview helper
    function initResponsivePreview() {
        // Add responsive preview toggle if needed in future
        const viewportSizes = {
            mobile: '375px',
            tablet: '768px',
            desktop: '1200px'
        };
        
        // This could be enhanced to show responsive previews
    }

    // Export/Import settings (future enhancement)
    function initExportImport() {
        // Add export/import functionality for settings
        // This would allow sharing configurations between sites
    }

    // Initialize all components
    function initAll() {
        initColorPickers();
        initRangeSliders();
        initFontInheritance();
        initColorSchemes();
        initSchemePresets();
        initFormValidation();
        initCollapsibleSections();
        initKeyboardAccessibility();
        initAutoSave();
        initResponsivePreview();
        initExportImport();
        
        console.log('FCRM UI Styling admin interface initialized');
    }

    // Add enhanced styling for better UX
    function addEnhancedStyling() {
        $(`
            <style>
                .scheme-preset {
                    transition: all 0.3s ease;
                    cursor: pointer;
                }
                
                .scheme-preset.hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }
                
                .scheme-preset.active {
                    border-color: #0073aa;
                    box-shadow: 0 0 0 2px rgba(0, 115, 170, 0.3);
                }
                
                .scheme-preset.active .apply-scheme {
                    background-color: #0073aa;
                    color: white;
                }
                
                .settings-section.collapsed .section-content {
                    display: none;
                }
                
                .settings-section h3 {
                    cursor: pointer;
                    user-select: none;
                }
                
                .settings-section h3:hover {
                    color: #0073aa;
                }
                
                .settings-changed-notice {
                    animation: slideDown 0.3s ease;
                }
                
                @keyframes slideDown {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                .fcrm-range-slider {
                    -webkit-appearance: none;
                    appearance: none;
                    height: 6px;
                    border-radius: 3px;
                    background: #ddd;
                    outline: none;
                }
                
                .fcrm-range-slider::-webkit-slider-thumb {
                    -webkit-appearance: none;
                    appearance: none;
                    width: 18px;
                    height: 18px;
                    border-radius: 50%;
                    background: #0073aa;
                    cursor: pointer;
                }
                
                .fcrm-range-slider::-moz-range-thumb {
                    width: 18px;
                    height: 18px;
                    border-radius: 50%;
                    background: #0073aa;
                    cursor: pointer;
                    border: none;
                }
                
                .info-card {
                    background: #f0f6fc;
                    border: 1px solid #c3dbf0;
                    border-radius: 6px;
                    padding: 1rem;
                    margin-top: 2rem;
                }
                
                .info-card h4 {
                    margin-top: 0;
                    color: #0969da;
                }
            </style>
        `).appendTo('head');
    }

    // Initialize everything when DOM is ready
    initAll();
    addEnhancedStyling();
    
    // Add global error handling
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (settings.url === fcrm_ui_ajax.ajax_url) {
            console.error('FCRM UI Styling AJAX Error:', thrownError);
            showNotice('An error occurred. Please refresh the page and try again.', 'error');
        }
    });
}); 