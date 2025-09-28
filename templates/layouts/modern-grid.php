<?php
/**
 * Modern Grid Layout Template
 * 
 * This template creates a completely modern tribute grid system
 * with our own AJAX calls and card rendering (no FCRM JavaScript)
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
    'fcrm-modern-grid',
    'card-style-' . $card_style,
    'columns-' . $grid_columns
];
?>

<div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" id="fcrm-<?php echo $uniqueElementId ?>" data-element-id="<?php echo $uniqueElementId ?>">
    <!-- Unified Search Interface -->
    <div class="fcrm-unified-search fcrm-modern-search">
        <div class="search-container">
            <!-- Name Search - Primary -->
            <div class="name-search-modern">
                <form data-action="search" class="search-form">
                    <div class="input-group">
                        <span class="input-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </span>
                        <input type="text" 
                               class="form-control modern-input" 
                               id="grid-search-<?php echo esc_attr($uniqueElementId); ?>" 
                               placeholder="Search by name..."
                               autocomplete="off">
                        <button class="btn reset-btn modern-clear" type="button" data-action="clear-search" aria-label="Clear search">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                                <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Date Range Picker - Single Input -->
            <div class="date-picker-modern">
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
                           class="form-control modern-input date-range-input" 
                           id="date-range-<?php echo esc_attr($uniqueElementId); ?>" 
                           placeholder="Select date range..."
                           readonly>
                    <button class="btn reset-btn modern-reset" type="button" data-action="clear-dates" aria-label="Clear dates">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                            <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Search Button -->
            <button class="btn modern-search-btn" type="button" data-action="search-submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                </svg>
                <span>Search</span>
            </button>
        </div>
    </div>

    <!-- Modern Grid Container -->
    <div class="fcrm-modern-grid-container">
        <!-- Loading state -->
        <div class="modern-loading" id="tributes-loading-<?php echo $uniqueElementId ?>">
            <div class="loading-spinner"></div>
            <p>Loading tributes...</p>
        </div>

        <!-- Modern Tributes Grid -->
        <div class="modern-tributes-grid" id="tributes-grid-<?php echo $uniqueElementId ?>">
            <!-- Cards will be inserted here by JavaScript -->
        </div>

        <!-- Load More Button -->
        <div class="load-more-container" id="load-more-container-<?php echo $uniqueElementId ?>" style="display: none;">
            <button class="btn btn-secondary load-more-btn" id="load-more-btn-<?php echo $uniqueElementId ?>" data-action="load-more">
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
    
    // Modern Tributes Grid Controller
    class ModernTributesGrid {
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
            
            // Load more
            container.find('[data-action="load-more"]').on('click', () => {
                this.loadTributes(false);
            });
        }
        
        loadTributes(resetPage = false) {
            if (this.isLoading) return;
            
            if (resetPage) {
                this.currentPage = 0;
                this.totalLoaded = 0;
                this.hasMorePages = true;
            }
            
            this.isLoading = true;
            this.showLoading();
            
            let data = {
                action: 'get_tributes',
                params: {
                    size: resetPage ? this.pageSize : this.loadMoreSize,
                    from: resetPage ? 0 : this.currentPage * this.loadMoreSize,
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
                this.handleTributesResponse(response, resetPage);
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
            
            tributes.forEach(tribute => {
                const card = this.createTributeCard(tribute);
                grid.append(card);
            });
            
            this.hideEmptyState();
        }
        
        createTributeCard(tribute) {
            const imageUrl = tribute.displayImage || this.config.defaultImageUrl;
            const hasImage = !!imageUrl;
            
            const card = $(`
                <article class="fcrm-tribute-card modern-card" data-tribute-id="${tribute.id || ''}" ${tribute.permalink ? `data-detail-url="${tribute.permalink}"` : ''}>
                    <div class="tribute-image-container">
                        ${hasImage ? 
                            `<img src="${imageUrl}" alt="${tribute.fullName || 'Tribute photo'}" class="tribute-image" loading="lazy" />` :
                            `<div class="tribute-image-placeholder">
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d=\"M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z\" stroke=\"currentColor\" stroke-width=\"2\"/>
                                    <path d=\"M20.5899 22C20.5899 18.13 16.7399 15 11.9999 15C7.25991 15 3.40991 18.13 3.40991 22\" stroke=\"currentColor\" stroke-width=\"2\"/>
                                </svg>
                            </div>`
                        }
                        ${tribute.permalink ? `<a href=\"${tribute.permalink}\" class=\"tribute-image-link\" aria-label=\"View ${tribute.fullName || 'tribute'} details\"></a>` : ''}
                    </div>
                    
                    <div class="tribute-content">
                        <h3 class="tribute-name">
                            ${tribute.permalink ? 
                                `<a href=\"${tribute.permalink}\" class=\"tribute-name-link\">${tribute.fullName || 'Unknown'}</a>` :
                                tribute.fullName || 'Unknown'
                            }
                        </h3>
                        
                        <div class="tribute-dates">
                            ${this.formatDates(tribute)}
                        </div>
                        
                        ${this.config.displayServiceInfo ? this.formatServiceInfo(tribute) : ''}
                    </div>
                </article>
            `);
            
            // Whole-card click using inner anchor href
            if (tribute.permalink) {
                card.attr('role', 'link');
                card.attr('tabindex', '0');
                card.on('click', function(e) {
                    if (e.target.closest && e.target.closest('a')) return;
                    const a = card.find('a.tribute-name-link').get(0) || card.find('a.tribute-image-link').get(0);
                    const href = a ? a.href : tribute.permalink;
                    if (href && href !== '#') window.location.assign(href);
                });
                card.on('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        const a = card.find('a.tribute-name-link').get(0) || card.find('a.tribute-image-link').get(0);
                        if (a) a.click(); else window.location.assign(tribute.permalink);
                    }
                });
            }
            
            return card;
        }
        
        formatDates(tribute) {
            let html = '';
            
            if (tribute.dateOfBirth && !this.config.hideDateOfBirth) {
                const dob = moment(tribute.dateOfBirth).format(this.config.dateFormat || 'DD/MM/YYYY');
                html += `<span class="birth-date">${dob}</span>`;
            }
            
            if (tribute.dateOfDeath) {
                if (html) html += ' <span class="date-separator">–</span> ';
                const dod = moment(tribute.dateOfDeath).format(this.config.dateFormat || 'DD/MM/YYYY');
                html += `<span class="death-date">${dod}</span>`;
            }
            
            return html;
        }
        
        formatServiceInfo(tribute) {
            if (!tribute.serviceEvent || !tribute.serviceEvent.dateTime) {
                return '';
            }
            
            const serviceDate = moment(tribute.serviceEvent.dateTime);
            const isUpcoming = serviceDate.isAfter(moment());
            
            if (!isUpcoming) return '';
            
            const dateStr = serviceDate.format('ddd, MMM D, YYYY [at] h:mm A');
            
            return `
                <div class="tribute-service-info">
                    <div class="service-datetime">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="service-icon">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                            <polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <span class="service-date">${dateStr}</span>
                    </div>
                    ${tribute.serviceEvent.venue ? `
                        <div class="service-venue">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="service-icon">
                                <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.3639 3.63604C20.0518 5.32387 21 7.61305 21 10Z" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            <span class="venue-name">${tribute.serviceEvent.venue.name || tribute.serviceEvent.venue.address || 'Service location'}</span>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        formatDate(date) {
            if (!date) return '';
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
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
            // You could implement a toast notification here
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
    
    // Initialize the modern grid
    $(document).ready(function() {
        const grid = new ModernTributesGrid('<?php echo $uniqueElementId ?>');
        window.modernTributesGrid_<?php echo $uniqueElementId ?> = grid;
    });
    
})(jQuery);
</script> 