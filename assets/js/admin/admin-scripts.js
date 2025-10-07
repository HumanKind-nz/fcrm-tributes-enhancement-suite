/**
 * FCRM Enhancement Suite - Admin Scripts
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize admin functionality
        initAdminFeatures();
        
        // Handle form submissions with loading states
        handleFormSubmissions();
        
        // Initialize toggle switches
        initToggleSwitches();
        
        // Initialize color picker
        initColorPicker();
        
    });

    /**
     * Initialize admin features
     */
    function initAdminFeatures() {
        // Add smooth scrolling to anchor links
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            var target = $(this.getAttribute('href'));
            if (target.length) {
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 500);
            }
        });

        // Add loading animation to buttons on click
        $('.button-primary, .button-secondary').on('click', function() {
            var $button = $(this);
            if (!$button.hasClass('loading')) {
                $button.addClass('loading');
                setTimeout(function() {
                    $button.removeClass('loading');
                }, 2000);
            }
        });

        // Animate progress bars on page load
        setTimeout(function() {
            $('.progress-bar-fill').each(function() {
                var $fill = $(this);
                var width = $fill.css('width');
                $fill.css('width', '0%').animate({
                    width: width
                }, 1000);
            });
        }, 500);
    }

    /**
     * Handle form submissions with loading states
     */
    function handleFormSubmissions() {
        $('form').on('submit', function(e) {
            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"], button[type="submit"]');
            
            // Don't prevent default - let WordPress handle the form submission
            
            // Add loading state
            $submitButton.addClass('loading').prop('disabled', true);
            
            // Show processing message
            showNotification('Processing settings...', 'info');
        });
    }

    /**
     * Initialize toggle switches
     */
    function initToggleSwitches() {
        $('.toggle-switch input').on('change', function() {
            var $toggle = $(this);
            var isChecked = $toggle.is(':checked');
            var settingName = $toggle.attr('name');
            var settingLabel = $toggle.closest('tr').find('th').text();
            
            // Show status update
            showNotification(
                settingLabel + ' ' + (isChecked ? 'enabled' : 'disabled'),
                isChecked ? 'success' : 'warning'
            );

            // Add visual feedback
            $toggle.closest('.toggle-switch').addClass('changed');
            setTimeout(function() {
                $toggle.closest('.toggle-switch').removeClass('changed');
            }, 300);
        });
    }

    /**
     * Initialize color picker
     */
    function initColorPicker() {
        if ($('.color-picker').length) {
            $('.color-picker').each(function() {
                var $input = $(this);
                
                // Simple color picker functionality
                $input.on('change', function() {
                    var color = $(this).val();
                    showNotification('Primary colour updated to ' + color, 'info');
                });
            });
        }
    }

    /**
     * Show notification message
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="alert alert-' + type + '">' + message + '</div>');
        
        // Remove existing notifications
        $('.alert').fadeOut(300, function() {
            $(this).remove();
        });
        
        // Add new notification at the top of tab content
        $('.tab-content').prepend($notification);
        
        // Auto-hide after 4 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
    }

    /**
     * Initialize progress bars with animation
     */
    function initProgressBars() {
        $('.progress-bar').each(function() {
            var $progressBar = $(this);
            var $fill = $progressBar.find('.progress-bar-fill');
            var targetWidth = $fill.data('width') || $fill.css('width');
            
            // Animate progress bar
            $fill.css('width', '0%');
            setTimeout(function() {
                $fill.animate({
                    width: targetWidth
                }, 1000);
            }, 200);
        });
    }

    /**
     * Handle settings section collapsing
     */
    $('.settings-section h3').on('click', function() {
        var $section = $(this).closest('.settings-section');
        var $content = $section.find('.section-content');
        
        $content.slideToggle(300);
        $section.toggleClass('collapsed');
    });

    /**
     * Add tooltips to form elements
     */
    function initTooltips() {
        $('[data-tooltip]').each(function() {
            var $element = $(this);
            var tooltip = $element.data('tooltip');
            
            $element.on('mouseenter', function() {
                var $tooltip = $('<div class="tooltip">' + tooltip + '</div>');
                $('body').append($tooltip);
                
                var offset = $element.offset();
                $tooltip.css({
                    top: offset.top - $tooltip.outerHeight() - 10,
                    left: offset.left + ($element.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                });
            });
            
            $element.on('mouseleave', function() {
                $('.tooltip').remove();
            });
        });
    }

    // Initialize tooltips
    initTooltips();

    /**
     * Handle reset confirmation
     */
    $('input[name="reset"]').on('click', function(e) {
        if (!confirm('Are you sure you want to reset all settings? This action cannot be undone.')) {
            e.preventDefault();
        }
    });

    /**
     * Add keyboard shortcuts
     */
    $(document).on('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.which === 83) {
            e.preventDefault();
            $('.button-primary').first().click();
            showNotification('Settings saved via keyboard shortcut!', 'success');
        }
    });

})(jQuery); 