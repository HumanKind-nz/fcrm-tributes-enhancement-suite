/**
 * FCRM Unified Search Component
 * 
 * Modern date range picker and search functionality for all tribute layouts
 * Uses Flatpickr for better UX and mobile support
 * 
 * @package FCRM_Enhancement_Suite
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Load Flatpickr CSS and JS
    function loadFlatpickr(callback) {
        if (window.flatpickr) {
            callback();
            return;
        }

        // Load CSS
        const css = document.createElement('link');
        css.rel = 'stylesheet';
        css.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
        document.head.appendChild(css);

        // Load JS
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/flatpickr';
        script.onload = callback;
        document.head.appendChild(script);
    }

    // Unified Search Controller
    window.FCRMUnifiedSearch = class FCRMUnifiedSearch {
        constructor(elementId, options = {}) {
            this.elementId = elementId;
            this.options = {
                dateFormat: 'd/m/Y',
                ...options
            };
            this.flatpickrInstance = null;
            this.flatpickrLoaded = false;
            this.startDate = null;
            this.endDate = null;
            this.searchQuery = null;

            this.init();
        }

        init() {
            // Performance optimization: Don't load Flatpickr until user needs it
            // This saves ~45KB of CDN requests on initial page load
            this.bindEvents();
            this.setupDatePickerOnDemand();
        }

        setupDatePickerOnDemand() {
            const dateInput = $(`#date-range-${this.elementId}`);
            if (!dateInput.length) return;

            // Only load Flatpickr when user focuses on date input
            dateInput.one('focus click', () => {
                if (!this.flatpickrLoaded) {
                    loadFlatpickr(() => {
                        this.initDateRangePicker();
                        this.flatpickrLoaded = true;
                        // Trigger focus again so datepicker opens
                        dateInput.trigger('focus');
                    });
                }
            });
        }

        initDateRangePicker() {
            const dateInput = $(`#date-range-${this.elementId}`);
            if (!dateInput.length) return;

            this.flatpickrInstance = flatpickr(dateInput[0], {
                mode: 'range',
                dateFormat: this.options.dateFormat,
                placeholder: 'Select date range...',
                allowInput: true,
                locale: {
                    rangeSeparator: ' to '
                },
                onChange: (selectedDates) => {
                    this.startDate = selectedDates[0] || null;
                    this.endDate = selectedDates[1] || null;
                    
                    // Trigger search when dates change
                    if (this.options.onDateChange) {
                        this.options.onDateChange(this.startDate, this.endDate);
                    }
                },
                onReady: () => {
                    // Add custom styling
                    $('.flatpickr-calendar').addClass('fcrm-date-picker');
                }
            });
        }

        bindEvents() {
            const container = $(`#fcrm-${this.elementId}`);
            
            // Search form submission
            container.find('[data-action="search"]').on('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });

            // Search button click
            container.find('[data-action="search-submit"]').on('click', () => {
                this.performSearch();
            });

            // Enter key in name search field
            container.find(`#grid-search-${this.elementId}`).on('keypress', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    this.performSearch();
                }
            });

            // Clear search
            container.find('[data-action="clear-search"]').on('click', () => {
                container.find(`#grid-search-${this.elementId}`).val('');
                this.searchQuery = null;
                this.performSearch();
            });

            // Clear dates
            container.find('[data-action="clear-dates"]').on('click', () => {
                if (this.flatpickrInstance) {
                    this.flatpickrInstance.clear();
                }
                this.startDate = null;
                this.endDate = null;
                this.performSearch();
            });
        }

        performSearch() {
            const container = $(`#fcrm-${this.elementId}`);
            this.searchQuery = container.find(`#grid-search-${this.elementId}`).val().toLowerCase();
            
            if (this.options.onSearch) {
                this.options.onSearch(this.searchQuery, this.startDate, this.endDate);
            }
        }

        getDateRange() {
            return {
                startDate: this.startDate,
                endDate: this.endDate
            };
        }

        getSearchQuery() {
            return this.searchQuery;
        }

        clearAll() {
            const container = $(`#fcrm-${this.elementId}`);
            container.find(`#grid-search-${this.elementId}`).val('');
            
            if (this.flatpickrInstance) {
                this.flatpickrInstance.clear();
            }
            
            this.searchQuery = null;
            this.startDate = null;
            this.endDate = null;
        }
    };

})(jQuery); 