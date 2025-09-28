/**
 * FCRM Layouts - Frontend JavaScript
 * 
 * Handles layout functionality including AJAX load more and modal preservation
 * 
 * @package FCRM_Enhancement_Suite
 * @subpackage Layouts
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Layout functionality object
    const FCRMLayouts = {
        
        // Configuration
        config: {
            loadMoreSelector: '.fcrm-load-more-button',
            gridSelector: '.fcrm-tributes-grid',
            spinnerSelector: '.load-more-spinner',
            modalSelector: '#fcrm-tribute-modal',
            cardSelector: '.fcrm-tribute-card'
        },

        // Initialize layout functionality
        init: function() {
            this.bindEvents();
            this.initAccessibility();
            this.preserveFCRMFunctionality();
            
            // Log initialization for debugging
            if (window.console && fcrmLayouts.activeLayout !== 'default') {
                console.log('FCRM Modern Layouts initialized:', fcrmLayouts.activeLayout);
            }
        },

        // Bind event handlers
        bindEvents: function() {
            // Load more button
            $(document).on('click', this.config.loadMoreSelector, this.handleLoadMore.bind(this));
            
            // Image lazy loading
            this.initLazyLoading();
            
            // Keyboard navigation
            this.initKeyboardNavigation();
        },

        // Handle load more functionality
        handleLoadMore: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $spinner = $button.siblings(this.config.spinnerSelector);
            const $grid = $(this.config.gridSelector);
            
            // Disable button and show spinner
            $button.prop('disabled', true);
            $spinner.show();
            
            // Get data attributes
            const limit = parseInt($button.data('limit')) || 6;
            const offset = parseInt($button.data('offset')) || 0;
            const attributes = $button.data('attributes') || {};
            
            // Prepare AJAX data
            const ajaxData = {
                action: 'fcrm_load_more_tributes',
                nonce: fcrmLayouts.nonce,
                limit: limit,
                offset: offset,
                attributes: attributes,
                layout: fcrmLayouts.activeLayout
            };
            
            // Make AJAX request
            $.ajax({
                url: fcrmLayouts.ajaxUrl,
                type: 'POST',
                data: ajaxData,
                success: this.handleLoadMoreSuccess.bind(this, $button, $spinner, $grid),
                error: this.handleLoadMoreError.bind(this, $button, $spinner)
            });
        },

        // Handle successful load more response
        handleLoadMoreSuccess: function($button, $spinner, $grid, response) {
            $spinner.hide();
            
            if (response.success && response.data.html) {
                // Append new cards to grid
                const $newCards = $(response.data.html);
                $grid.append($newCards);
                
                // Update offset
                const currentOffset = parseInt($button.data('offset')) || 0;
                const newOffset = currentOffset + (response.data.count || 0);
                $button.data('offset', newOffset);
                
                // Hide button if no more items
                if (!response.data.hasMore) {
                    $button.parent().hide();
                } else {
                    $button.prop('disabled', false);
                }
                
                // Trigger custom event for other scripts
                $(document).trigger('fcrmLayoutsLoaded', [$newCards]);
                
                // Re-initialize functionality for new cards
                this.initCardFunctionality($newCards);
                
            } else {
                // Handle error
                this.handleLoadMoreError($button, $spinner, response.data?.message || 'Failed to load more tributes');
            }
        },

        // Handle load more error
        handleLoadMoreError: function($button, $spinner, errorMessage) {
            $spinner.hide();
            $button.prop('disabled', false);
            
            // Show error message
            if (typeof errorMessage === 'string') {
                const $error = $('<div class="fcrm-error-message" style="color: #dc3545; font-size: 0.875rem; margin-top: 0.5rem;">' + errorMessage + '</div>');
                $button.parent().append($error);
                
                // Remove error after 5 seconds
                setTimeout(function() {
                    $error.fadeOut(function() {
                        $error.remove();
                    });
                }, 5000);
            }
            
            console.error('FCRM Load More Error:', errorMessage);
        },

        // Initialize lazy loading for images
        initLazyLoading: function() {
            // Use Intersection Observer if available
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                observer.unobserve(img);
                            }
                        }
                    });
                });

                // Observe all lazy images
                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        },

        // Initialize keyboard navigation
        initKeyboardNavigation: function() {
            $(document).on('keydown', this.config.cardSelector, function(e) {
                const $card = $(this);
                const $cards = $(FCRMLayouts.config.cardSelector);
                const currentIndex = $cards.index($card);
                
                switch(e.key) {
                    case 'ArrowRight':
                    case 'ArrowDown':
                        e.preventDefault();
                        const nextIndex = (currentIndex + 1) % $cards.length;
                        $cards.eq(nextIndex).focus();
                        break;
                        
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        e.preventDefault();
                        const prevIndex = currentIndex === 0 ? $cards.length - 1 : currentIndex - 1;
                        $cards.eq(prevIndex).focus();
                        break;
                        
                    case 'Enter':
                    case ' ':
                        e.preventDefault();
                        const $link = $card.find('.tribute-name-link, .view-details-button').first();
                        if ($link.length) {
                            $link[0].click();
                        }
                        break;
                }
            });
        },

        // Initialize accessibility features
        initAccessibility: function() {
            // Add ARIA labels and roles
            $(this.config.cardSelector).each(function() {
                const $card = $(this);
                const $name = $card.find('.tribute-name');
                const name = $name.text().trim();
                
                if (name && !$card.attr('aria-label')) {
                    $card.attr({
                        'role': 'article',
                        'aria-label': 'Tribute for ' + name,
                        'tabindex': '0'
                    });
                }
            });
            
            // Improve button accessibility
            $(this.config.loadMoreSelector).attr({
                'aria-describedby': 'fcrm-load-more-description'
            });
            
            // Add description for load more button
            if (!$('#fcrm-load-more-description').length) {
                $('<div id="fcrm-load-more-description" class="sr-only">Loads additional tribute cards to the current view</div>')
                    .appendTo('body');
            }
        },

        // Initialize functionality for new cards (after AJAX load)
        initCardFunctionality: function($cards) {
            // Add accessibility attributes
            $cards.each(function() {
                const $card = $(this);
                const $name = $card.find('.tribute-name');
                const name = $name.text().trim();
                
                if (name) {
                    $card.attr({
                        'role': 'article',
                        'aria-label': 'Tribute for ' + name,
                        'tabindex': '0'
                    });
                }
            });
            
            // Initialize any FCRM-specific functionality for new cards
            if (typeof window.initFCRMCards === 'function') {
                window.initFCRMCards($cards);
            }
        },

        // Preserve existing FCRM functionality
        preserveFCRMFunctionality: function() {
            // Ensure FCRM modal functionality is preserved
            if (typeof window.fcrmModal !== 'undefined') {
                // FCRM modal is already initialized, nothing to do
                return;
            }
            
            // Basic modal functionality if FCRM doesn't provide it
            $(document).on('click', '.fcrm-modal-close', function() {
                $(FCRMLayouts.config.modalSelector).hide();
            });
            
            // Close modal on background click
            $(document).on('click', FCRMLayouts.config.modalSelector, function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });
            
            // Close modal on escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $(FCRMLayouts.config.modalSelector).is(':visible')) {
                    $(FCRMLayouts.config.modalSelector).hide();
                }
            });
        },

        // Utility function to refresh layout
        refreshLayout: function() {
            // Trigger any layout-specific refresh logic
            $(document).trigger('fcrmLayoutsRefresh');
            
            // Re-initialize accessibility
            this.initAccessibility();
        },

        // Utility function to get current layout settings
        getLayoutSettings: function() {
            return {
                activeLayout: fcrmLayouts.activeLayout,
                gridColumns: fcrmLayouts.gridColumns,
                cardStyle: fcrmLayouts.cardStyle
            };
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we have layout settings
        if (typeof fcrmLayouts !== 'undefined' && fcrmLayouts.activeLayout !== 'default') {
            FCRMLayouts.init();
        }
    });

    // Expose to global scope for other scripts
    window.FCRMLayouts = FCRMLayouts;

})(jQuery); 