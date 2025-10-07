<?php
/**
 * Elegant Grid Layout Template
 * 
 * Sophisticated, upscale tribute grid system with refined typography,
 * elegant colour palettes, and premium spacing for high-end funeral homes.
 */

$dateLocale = get_option('fcrm_tributes_date_locale');
if (!$dateLocale || empty($dateLocale)) {
    $dateLocale = null;
}
$uniqueElementId = uniqid();
$fcrmDefaultImageUrl = get_option('fcrm_tributes_default_image');

// Get layout configuration
$card_style = get_option('fcrm_layout_card_style', 'standard');
$grid_columns = get_option('fcrm_layout_grid_columns', 'auto');

// Build CSS classes
$container_classes = [
    'fcrm-elegant-grid',
    'card-style-' . $card_style,
    'columns-' . $grid_columns
];
?>

<div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" id="fcrm-<?php echo $uniqueElementId ?>" data-element-id="<?php echo $uniqueElementId ?>">
    <!-- Unified Search Interface -->
    <div class="fcrm-unified-search fcrm-elegant-search">
        <div class="search-container">
            <!-- Name Search - Primary -->
            <div class="name-search-elegant">
                <form data-action="search" class="search-form">
                    <div class="input-group">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <input type="text" 
                               class="form-control elegant-input" 
                               id="grid-search-<?php echo esc_attr($uniqueElementId); ?>" 
                               placeholder="Search by name..."
                               autocomplete="off">
                        <button class="btn reset-btn elegant-clear" type="button" data-action="clear-search" aria-label="Clear search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                                <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Date Range Picker - Single Input -->
            <div class="date-picker-elegant">
                <div class="input-group">
                    <span class="input-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                            <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/>
                            <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/>
                            <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </span>
                    <input type="text" 
                           class="form-control elegant-input date-range-input" 
                           id="date-range-<?php echo esc_attr($uniqueElementId); ?>" 
                           placeholder="Select date range..."
                           readonly>
                    <button class="btn reset-btn elegant-reset" type="button" data-action="clear-dates" aria-label="Clear dates">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                            <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Search Button -->
            <button class="btn elegant-search-btn" type="button" data-action="search-submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Search</span>
            </button>
        </div>
    </div>

    <!-- Elegant Grid Container -->
    <div class="fcrm-elegant-grid-container">
        <!-- Loading state -->
        <div class="elegant-loading" id="tributes-loading-<?php echo $uniqueElementId ?>">
            <div class="loading-spinner"></div>
            <p>Loading tributes...</p>
        </div>

        <!-- Elegant Tributes Grid -->
        <div class="elegant-tributes-grid" id="tributes-grid-<?php echo $uniqueElementId ?>">
            <!-- Cards will be inserted here by JavaScript -->
        </div>

        <!-- Load More Button -->
        <div class="load-more-container" id="load-more-container-<?php echo $uniqueElementId ?>" style="display: none;">
            <button class="btn btn-secondary load-more-btn elegant-load-more" id="load-more-btn-<?php echo $uniqueElementId ?>" data-action="load-more">
                <span class="btn-text">Load More Tributes</span>
                <span class="btn-spinner" style="display: none;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2V6M12 18V22M4.93 4.93L7.76 7.76M16.24 16.24L19.07 19.07M2 12H6M18 12H22M4.93 19.07L7.76 16.24M16.24 7.76L19.07 4.93" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </button>
        </div>

        <!-- Empty state -->
        <div class="empty-state" id="empty-state-<?php echo $uniqueElementId ?>" style="display: none;">
            <div class="empty-state-content">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="empty-icon">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                </svg>
                <h3>No tributes found</h3>
                <p>Try adjusting your search criteria or date range.</p>
            </div>
        </div>
    </div>
</div>

<script type="application/javascript">
(function($) {
    'use strict';
    
    // Elegant Tributes Grid Controller
    class ElegantTributesGrid {
        constructor(elementId) {
            this.elementId = elementId;
            this.currentPage = 0;
            this.pageSize = <?php echo $size ? $size : 16; ?>;
            this.loadMoreSize = <?php echo get_option('fcrm_layout_load_more_size', $size ? $size : 16); ?>;
            this.totalLoaded = 0;
            this.isLoading = false;
            this.hasMorePages = true;
            this.searchQuery = null;
            this.startDate = null;
            this.endDate = null;
            this.unifiedSearch = null;
            
            // Configuration from PHP
            this.config = {
                sortByService: <?php echo json_encode($sortByService); ?>,
                team: <?php echo json_encode($team); ?>,
                dateLocale: <?php echo json_encode($dateLocale); ?>,
                nameFormat: <?php echo json_encode($nameFormat); ?>,
                dateFormat: <?php echo json_encode($dateFormat); ?>,
                filterDateFormat: <?php echo json_encode($filterDateFormat); ?>,
                showFutureTributes: <?php echo json_encode($showFutureTributes); ?>,
                showPastTributes: <?php echo json_encode($showPastTributes); ?>,
                branch: <?php echo json_encode($branch); ?>,
                displayBranch: <?php echo json_encode($displayBranch); ?>,
                displayServiceInfo: <?php echo json_encode($displayServiceInfo); ?>,
                hideDateOfBirth: <?php echo json_encode($hideDateOfBirth); ?>,
                teamGroupIndex: <?php echo json_encode($teamGroupIndex); ?>,
                defaultImageUrl: <?php echo json_encode($fcrmDefaultImageUrl); ?>
            };
            
            this.init();
        }
        
        init() {
            this.initUnifiedSearch();
            this.bindEvents();
            this.loadTributes(true); // Initial load
        }
        
        initUnifiedSearch() {
            this.unifiedSearch = new FCRMUnifiedSearch(this.elementId, {
                onSearch: (query, startDate, endDate) => {
                    this.searchQuery = query;
                    this.startDate = startDate;
                    this.endDate = endDate;
                    this.loadTributes(true);
                },
                onDateChange: (startDate, endDate) => {
                    this.startDate = startDate;
                    this.endDate = endDate;
                    this.loadTributes(true);
                }
            });
        }
        
        bindEvents() {
            const container = $(`#fcrm-${this.elementId}`);
            
            // Load more button
            container.find('[data-action="load-more"]').on('click', () => {
                this.loadTributes(false);
            });
        }
        
        loadTributes(reset = false) {
            if (this.isLoading) return;
            
            if (reset) {
                this.currentPage = 0;
                this.totalLoaded = 0;
                this.hasMorePages = true;
            }
            
            this.isLoading = true;
            this.showLoading();
            
            let data = {
                action: 'get_tributes',
                params: {
                    size: reset ? this.pageSize : this.loadMoreSize,
                    from: reset ? 0 : this.currentPage * this.loadMoreSize,
                    query: this.searchQuery || '',
                    startDate: this.startDate ? moment(this.startDate).startOf("day").valueOf() : '',
                    endDate: this.endDate ? moment(this.endDate).endOf("day").valueOf() : '',
                    ...this.config
                }
            };
            
            console.log('Making AJAX request with data:', data);
            
            $.ajax({
                method: 'POST',
                url: ajax_var.url,
                data: data
            })
            .done((response) => {
                this.handleTributesResponse(response, reset);
            })
            .fail((xhr, status, error) => {
                console.error('Failed to load tributes:', error);
                this.hideLoading();
                this.showError('Failed to load tributes. Please try again.');
            });
        }
        
        handleTributesResponse(response, isNewSearch = false) {
            this.isLoading = false;
            this.hideLoading();
            
            let data = {};
            if (response) {
                try {
                    data = JSON.parse(response);
                } catch (e) {
                    console.error('Invalid response format:', e);
                    this.showError('Invalid response from server.');
                    return;
                }
            }
            
            const tributes = data.results || [];
            const total = data.total || 0;
            
            if (isNewSearch) {
                this.clearGrid();
            }
            
            if (tributes.length === 0 && isNewSearch) {
                this.showEmptyState();
                return;
            }
            
            // Render tributes
            this.renderTributes(tributes);
            
            // Update pagination state
            this.currentPage++;
            this.totalLoaded += tributes.length;
            this.hasMorePages = this.totalLoaded < total;
            this.updateLoadMoreButton();
        }
        
        renderTributes(tributes) {
            const grid = $(`#tributes-grid-${this.elementId}`);

            tributes.forEach((tribute, index) => {
                // Pass absolute index to track position across pages
                const absoluteIndex = this.totalLoaded + index;
                const card = this.createTributeCard(tribute, absoluteIndex);
                grid.append(card);
            });

            this.hideEmptyState();
        }

        createTributeCard(tribute, absoluteIndex) {
            const detailUrl = tribute.permalink || '#';
            const imageUrl = tribute.displayImage || this.config.defaultImageUrl || '';
            const hasImage = imageUrl && imageUrl.trim() !== '';

            // Performance optimization: Don't lazy load first 9 images (3 rows of 3)
            // This prevents LCP degradation by ensuring above-the-fold images load immediately
            const shouldLazyLoad = absoluteIndex >= 9;
            const loadingAttr = shouldLazyLoad ? 'loading="lazy"' : '';

            // Performance optimization: Add fetch priority to first image (LCP element)
            const fetchPriorityAttr = absoluteIndex === 0 ? 'fetchpriority="high"' : '';

            // Format dates display
            let datesHtml = '';
            if (tribute.dateOfBirth && !this.config.hideDateOfBirth) {
                const dob = this.formatDateString(tribute.dateOfBirth);
                datesHtml += dob;
            }
            
            if (tribute.dateOfDeath) {
                if (datesHtml) datesHtml += ' – ';
                const dod = this.formatDateString(tribute.dateOfDeath);
                datesHtml += dod;
            }
            
            // Format service info display
            let serviceInfoHtml = '';
            if (this.config.displayServiceInfo && tribute.serviceEvent && tribute.serviceEvent.dateTime) {
                const serviceDate = new Date(tribute.serviceEvent.dateTime);
                const dateStr = this.formatServiceDate(serviceDate);
                serviceInfoHtml = `<div class="elegant-service-info">
                    <span class="service-label">Service:</span> ${dateStr}
                </div>`;
            }
            
            const card = $(`
                <article class="fcrm-tribute-card elegant-memorial-card" data-tribute-id="${tribute.id || ''}" ${detailUrl !== '#' ? `data-detail-url="${detailUrl}"` : ''}>
                    <div class="elegant-image-container">
                        <div class="elegant-image-frame">
                            ${hasImage ?
                                `<img src="${imageUrl}" alt="${tribute.fullName || 'Memorial photo'}" class="elegant-portrait" ${loadingAttr} ${fetchPriorityAttr}>` :
                                `<div class="elegant-image-placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="2"/>
                                        <path d="M20.5899 22C20.5899 18.13 16.7399 15 11.9999 15C7.25991 15 3.40991 18.13 3.40991 22" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                 </div>`
                            }
                        </div>
                    </div>
                    
                    <div class="elegant-content">
                        <h3 class="elegant-name">
                            ${detailUrl !== '#' ? 
                                `<a href="${detailUrl}" class="elegant-name-link">${tribute.fullName || 'Unknown'}</a>` :
                                tribute.fullName || 'Unknown'
                            }
                        </h3>
                        
                        ${datesHtml ? `
                            <div class="elegant-divider"></div>
                            <div class="elegant-dates">${datesHtml}</div>
                        ` : ''}
                        
                        ${serviceInfoHtml}
                    </div>
                </article>
            `);
            
            // Make entire card clickable (without nesting links)
            if (detailUrl !== '#') {
                card.attr('role', 'link');
                card.attr('tabindex', '0');
                card.attr('aria-label', `View tribute for ${tribute.fullName || 'tribute'}`);
                
                // Click anywhere navigates unless an inner link was clicked
                card.on('click', function(e) {
                    if (e.target.closest && e.target.closest('a')) return; // allow default link behaviour
                    const linkEl = card.find('a.elegant-name-link').get(0);
                    const href = linkEl ? linkEl.href : detailUrl;
                    if (href && href !== '#') {
                        window.location.assign(href);
                    }
                });
                
                // Keyboard accessibility
                card.on('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const linkEl = card.find('a.elegant-name-link').get(0);
                        if (linkEl) {
                            linkEl.click();
                        } else if (detailUrl && detailUrl !== '#') {
                            window.location.assign(detailUrl);
                        }
                    }
                });
            }
            
            return card;
        }
        
        formatDateString(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            
            const day = date.getDate();
            const month = date.toLocaleString('en-US', { month: 'short' });
            const year = date.getFullYear();
            
            return `${day} ${month} ${year}`;
        }
        
        formatServiceDate(date) {
            return date.toLocaleDateString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }
        
        showLoading() {
            $(`#tributes-loading-${this.elementId}`).show();
        }
        
        hideLoading() {
            $(`#tributes-loading-${this.elementId}`).hide();
        }
        
        clearGrid() {
            $(`#tributes-grid-${this.elementId}`).empty();
        }
        
        showEmptyState() {
            $(`#empty-state-${this.elementId}`).show();
        }
        
        hideEmptyState() {
            $(`#empty-state-${this.elementId}`).hide();
        }
        
        showError(message) {
            console.error(message);
        }
        
        updateLoadMoreButton() {
            const container = $(`#load-more-container-${this.elementId}`);
            const button = $(`#load-more-btn-${this.elementId}`);
            
            if (this.hasMorePages) {
                container.show();
                button.prop('disabled', false).find('.btn-text').text('Load More Tributes');
            } else {
                container.hide();
            }
        }
    }
    
    // Initialize the elegant grid
    $(document).ready(function() {
        const grid = new ElegantTributesGrid('<?php echo $uniqueElementId ?>');
        window.elegantTributesGrid_<?php echo $uniqueElementId ?> = grid;
    });
    
})(jQuery);
</script>