<?php
/**
 * Gallery Grid Layout Template
 * 
 * Image-focused tribute grid with large photos, masonry-style layout,
 * and minimal text overlay. Perfect for showcasing beautiful memorial photos
 * as the primary focus with a Pinterest-like aesthetic.
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
    'fcrm-gallery-grid',
    'card-style-' . $card_style,
    'columns-' . $grid_columns
];
?>

<div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" id="fcrm-<?php echo $uniqueElementId ?>" data-element-id="<?php echo $uniqueElementId ?>">
    <!-- Unified Search Interface -->
    <div class="fcrm-unified-search fcrm-gallery-search">
        <div class="search-container">
            <!-- Name Search - Primary -->
            <div class="name-search-gallery">
                <form data-action="search" class="search-form">
                    <div class="input-group">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <input type="text" 
                               class="form-control gallery-input" 
                               id="grid-search-<?php echo esc_attr($uniqueElementId); ?>" 
                               placeholder="Search by name..."
                               autocomplete="off">
                        <button class="btn reset-btn gallery-clear" type="button" data-action="clear-search" aria-label="Clear search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                                <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Date Range Picker - Single Input -->
            <div class="date-picker-gallery">
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
                           class="form-control gallery-input date-range-input" 
                           id="date-range-<?php echo esc_attr($uniqueElementId); ?>" 
                           placeholder="Select date range..."
                           readonly>
                    <button class="btn reset-btn gallery-reset" type="button" data-action="clear-dates" aria-label="Clear dates">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                            <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Search Button -->
            <button class="btn gallery-search-btn" type="button" data-action="search-submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Search</span>
            </button>
        </div>
    </div>

    <!-- Gallery Grid Container -->
    <div class="fcrm-gallery-grid-container">
        <!-- Loading state -->
        <div class="gallery-loading" id="tributes-loading-<?php echo $uniqueElementId ?>">
            <div class="loading-spinner"></div>
            <p>Loading gallery...</p>
        </div>

        <!-- Gallery Tributes Grid -->
        <div class="gallery-tributes-grid" id="tributes-grid-<?php echo $uniqueElementId ?>">
            <!-- Cards will be inserted here by JavaScript -->
        </div>

        <!-- Load More Button -->
        <div class="load-more-container" id="load-more-container-<?php echo $uniqueElementId ?>" style="display: none;">
            <button class="btn btn-secondary load-more-btn gallery-load-more" id="load-more-btn-<?php echo $uniqueElementId ?>" data-action="load-more">
                <span class="btn-text">Load More Photos</span>
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
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2"/>
                    <polyline points="21,15 16,10 5,21" stroke="currentColor" stroke-width="2"/>
                </svg>
                <h3>No photos found</h3>
                <p>Try adjusting your search criteria or date range.</p>
            </div>
        </div>
    </div>
</div>

<script type="application/javascript">
(function($) {
    'use strict';
    
    // Gallery Tributes Grid Controller
    class GalleryTributesGrid {
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
            
            // Build params matching FireHawk's approach: only include optional
            // fields when they have values. Sending empty strings for query/dates
            // triggers unintended fuzzy search and breaks date override logic
            // in FireHawk's get_tribute_search() handler.
            let params = {
                size: reset ? this.pageSize : this.loadMoreSize,
                from: reset ? 0 : this.currentPage * this.loadMoreSize,
            };

            if (this.searchQuery) params.query = this.searchQuery;
            if (this.startDate) params.startDate = moment(this.startDate).startOf("day").valueOf();
            if (this.endDate) params.endDate = moment(this.endDate).endOf("day").valueOf();

            if (this.config.sortByService) params.sortByService = this.config.sortByService;
            if (this.config.nameFormat) params.nameFormat = this.config.nameFormat;
            if (this.config.branch) params.branch = this.config.branch;
            if (this.config.showFutureTributes) params.showFutureTributes = this.config.showFutureTributes;
            if (this.config.showPastTributes) params.showPastTributes = this.config.showPastTributes;
            if (this.config.displayServiceInfo) params.displayService = this.config.displayServiceInfo;
            if (this.config.teamGroupIndex != null) params.teamGroupIndex = this.config.teamGroupIndex;
            if (this.config.displayBranch) params.displayBranch = this.config.displayBranch;

            let data = {
                action: 'get_tributes',
                params: params
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
                this.showError('Failed to load gallery. Please try again.');
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
            
            // Determine card height for masonry effect
            const cardHeight = this.getRandomHeight();
            
            const card = $(`
                <article class="fcrm-tribute-card gallery-photo-card" data-tribute-id="${tribute.id || ''}" ${detailUrl !== '#' ? `data-detail-url="${detailUrl}"` : ''} style="height: ${cardHeight}px;">
                    <div class="gallery-image-container">
                        ${hasImage ?
                            `<img src="${imageUrl}" alt="${tribute.fullName || 'Memorial photo'}" class="gallery-image" ${loadingAttr} ${fetchPriorityAttr}>` :
                            `<div class="gallery-image-placeholder">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect x=\"3\" y=\"3\" width=\"18\" height=\"18\" rx=\"2\" ry=\"2\" stroke=\"currentColor\" stroke-width=\"2\"/>
                                    <circle cx=\"8.5\" cy=\"8.5\" r=\"1.5\" stroke=\"currentColor\" stroke-width=\"2\"/>
                                    <polyline points=\"21,15 16,10 5,21\" stroke=\"currentColor\" stroke-width=\"2\"/>
                                </svg>
                             </div>`
                        }
                        
                        <div class="gallery-overlay">
                            <div class="gallery-content">
                                <h3 class="gallery-name">
                                    ${detailUrl !== '#' ? 
                                        `<a href=\"${detailUrl}\" class=\"gallery-name-link\">${tribute.fullName || 'Unknown'}</a>` :
                                        tribute.fullName || 'Unknown'
                                    }
                                </h3>
                                
                                ${datesHtml ? `<div class=\"gallery-dates\">${datesHtml}</div>` : ''}
                            </div>
                        </div>
                    </div>
                </article>
            `);
            
            // Whole-card click using inner anchor
            if (detailUrl !== '#') {
                card.attr('role', 'link'); card.attr('tabindex', '0');
                card.on('click', function(e){ if (e.target.closest && e.target.closest('a')) return; const a = card.find('a.gallery-name-link').get(0); const href = a ? a.href : detailUrl; if (href) window.location.assign(href); });
                card.on('keydown', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); const a = card.find('a.gallery-name-link').get(0); if (a) a.click(); else window.location.assign(detailUrl); }});
            }
            
            return card;
        }
        
        getRandomHeight() {
            // Generate heights for masonry effect - varied but not too extreme
            const heights = [300, 320, 350, 380, 400, 420, 450];
            return heights[Math.floor(Math.random() * heights.length)];
        }
        
        showLoading() {
            $(`#tributes-loading-${this.elementId}`).show();
            $(`#load-more-btn-${this.elementId} .btn-text`).hide();
            $(`#load-more-btn-${this.elementId} .btn-spinner`).show();
        }
        
        hideLoading() {
            $(`#tributes-loading-${this.elementId}`).hide();
            $(`#load-more-btn-${this.elementId} .btn-text`).show();
            $(`#load-more-btn-${this.elementId} .btn-spinner`).hide();
        }
        
        showEmptyState() {
            $(`#empty-state-${this.elementId}`).show();
        }
        
        hideEmptyState() {
            $(`#empty-state-${this.elementId}`).hide();
        }
        
        updateLoadMoreButton() {
            const container = $(`#load-more-container-${this.elementId}`);
            if (this.hasMorePages && this.totalLoaded > 0) {
                container.show();
            } else {
                container.hide();
            }
        }
        
        showError(message) {
            console.error('Gallery Grid Error:', message);
            const container = $(`#tributes-grid-${this.elementId}`);
            if (this.totalLoaded === 0) {
                container.html(`
                    <div class="error-state">
                        <p>${message}</p>
                    </div>
                `);
            }
        }
        
        clearGrid() {
            $(`#tributes-grid-${this.elementId}`).empty();
            this.hideEmptyState();
        }
        
        formatDate(date) {
            return date.toISOString().split('T')[0];
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
    }
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        $('.fcrm-gallery-grid').each(function() {
            const elementId = $(this).data('element-id');
            if (elementId) {
                new GalleryTributesGrid(elementId);
            }
        });
    });
    
})(jQuery);
</script> 