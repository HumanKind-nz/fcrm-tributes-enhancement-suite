<?php
/**
 * FCRM Enhancement Suite - List View Layout Template
 * 
 * Clean, typography-focused tribute listing with minimal visual elements.
 * Emphasizes readability and essential information with generous white space.
 * 
 * @package FCRM_Enhancement_Suite
 * @subpackage Templates
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Process attributes the same way FCRM does (copied from modern-grid.php)
$team = get_option('fcrm_team');
$size = intval($attributes['size'] ?? 12);
$sortByService = $attributes['sort-by-service'] === true || $attributes['sort-by-service'] === 'true';
$nameFormat = $attributes['name-format'];
$displayServiceInfo = $attributes['display-service'] === true || $attributes['display-service'] === 'true';
$hideDateOfBirth = $attributes['hide-dob'] === true || $attributes['hide-dob'] === 'true';
$dateFormat = "";
$filterDateFormat = "";
$showFutureTributes = null;
$showPastTributes = null;
$branch = $attributes['branch'] ?? null;
$displayBranch = $attributes['display-branch'] === true || $attributes['display-branch'] === 'true';
$teamGroupIndex = $attributes['team-index'] ?? null;

// Check global settings
if (get_option('fcrm_tributes_hide_dob') == true) {
    $hideDateOfBirth = true;
}

// Additional variables needed for AJAX
$fcrmDefaultImageUrl = get_option('fcrm_tributes_default_image');
$dateLocale = 'en';

// Get shortcode attributes with defaults  
$atts = shortcode_atts(array(
    'search' => 'true',
    'from' => '',
    'size' => $size,
    'layout' => 'minimal'
), $atts);

// Generate unique container ID
$container_id = 'fcrm-minimal-' . wp_rand(1000, 9999);
$uniqueElementId = $container_id;
?>

<div id="<?php echo esc_attr($container_id); ?>" class="fcrm-minimal-layout" data-layout="minimal">
    
    <?php if ($atts['search'] === 'true'): ?>
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
                               id="grid-search-<?php echo esc_attr($container_id); ?>" 
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
                           id="date-range-<?php echo esc_attr($container_id); ?>" 
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
    <?php endif; ?>
    
    <!-- Minimal Tributes List -->
    <div class="minimal-tributes-container">
        <div class="minimal-loading" style="display: none;">
            <div class="loading-text">Loading tributes...</div>
        </div>
        
        <div class="minimal-tributes-list">
            <!-- Tributes will be loaded here via AJAX -->
        </div>
        
        <div class="empty-state" style="display: none;">
            <div class="empty-state-content">
                <h3>No tributes found</h3>
                <p>Try adjusting your search criteria or browse all tributes.</p>
            </div>
        </div>
    </div>
    
    <!-- Load More Button -->
    <div class="load-more-container" style="display: none;">
        <button type="button" class="load-more-btn minimal-load-more">
            <span class="load-more-text">Load More</span>
            <span class="load-more-loading" style="display: none;">Loading...</span>
        </button>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const container = $('#<?php echo esc_js($container_id); ?>');
    const tributesList = container.find('.minimal-tributes-list');
    const loadingDiv = container.find('.minimal-loading');
    const emptyState = container.find('.empty-state');
    const loadMoreContainer = container.find('.load-more-container');
    const loadMoreBtn = container.find('.load-more-btn');
    
    let currentPage = 1;
    let isLoading = false;
    let hasMore = true;
    let currentSearch = {};
    let unifiedSearch = null;
    
    // Initialize unified search if search is enabled
    <?php if ($atts['search'] === 'true'): ?>
    unifiedSearch = new FCRMUnifiedSearch('<?php echo esc_js($container_id); ?>', {
        onSearch: (query, startDate, endDate) => {
            currentSearch = {
                name: query,
                date_from: startDate,
                date_to: endDate
            };
            currentPage = 1;
            hasMore = true;
            loadTributes(1, currentSearch);
        },
        onDateChange: (startDate, endDate) => {
            currentSearch.date_from = startDate;
            currentSearch.date_to = endDate;
            currentPage = 1;
            hasMore = true;
            loadTributes(1, currentSearch);
        }
    });
    <?php endif; ?>
    
    // Date formatting function
    function formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date)) return dateString; // Return original if invalid
        
        const day = date.getDate();
        const month = date.getMonth() + 1;
        const year = date.getFullYear();
        
        return `${day}/${month}/${year}`;
    }
    
    function loadTributes(page = 1, search = {}, append = false) {
        if (isLoading) return;
        
        isLoading = true;
        
        if (!append) {
            loadingDiv.show();
            tributesList.empty();
            emptyState.hide();
            loadMoreContainer.hide();
        } else {
            loadMoreBtn.find('.load-more-text').hide();
            loadMoreBtn.find('.load-more-loading').show();
        }
        
        console.log('Loading tributes with search:', search);
        
        const ajaxData = {
            action: 'get_tributes',
            params: {
                size: <?php echo intval($atts['size']); ?>,
                from: (page - 1) * <?php echo intval($atts['size']); ?>,
                query: search.name || '',
                startDate: search.date_from ? moment(search.date_from).startOf("day").valueOf() : '',
                endDate: search.date_to ? moment(search.date_to).endOf("day").valueOf() : '',
                // Include same configuration as modern grid
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
            }
        };
        
        console.log('Making AJAX request with data:', ajaxData);
        
        $.ajax({
            method: 'POST',
            url: ajax_var.url,
            data: ajaxData
        })
        .done(function(response) {
            console.log('AJAX response received:', response);
            
            let data = {};
            if (response) {
                try {
                    data = JSON.parse(response);
                    console.log('Parsed response data:', data);
                } catch (e) {
                    console.error('Invalid response format:', e);
                    if (page === 1) {
                        emptyState.show();
                    }
                    return;
                }
            }
            
            const tributes = data.results || [];
            const total = data.total || 0;
            
            console.log('Found tributes:', tributes.length, 'Total:', total);
            
            if (tributes.length === 0 && page === 1) {
                emptyState.show();
            } else {
                tributes.forEach(tribute => {
                    tributesList.append(createTributeItem(tribute));
                });
                
                // Update pagination based on total loaded vs total available
                const totalLoaded = (page - 1) * <?php echo intval($atts['size']); ?> + tributes.length;
                hasMore = totalLoaded < total;
                
                if (hasMore) {
                    loadMoreContainer.show();
                } else {
                    loadMoreContainer.hide();
                }
            }
        })
        .fail(function(xhr, status, error) {
            console.error('Failed to load tributes:', error);
            if (page === 1) {
                emptyState.show();
            }
        })
        .always(function() {
            isLoading = false;
            loadingDiv.hide();
            loadMoreBtn.find('.load-more-text').show();
            loadMoreBtn.find('.load-more-loading').hide();
        });
    }
    
    function createTributeItem(tribute) {
        const photoHtml = tribute.displayImage ? 
            `<div class="tribute-photo">
                <img src="${tribute.displayImage}" alt="${tribute.fullName}" loading="lazy">
            </div>` : 
            `<div class="tribute-photo-placeholder">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>`;
        
        // Format dates display
        let datesHtml = '';
        if (tribute.dateOfBirth) {
            datesHtml += formatDate(tribute.dateOfBirth);
        }
        if (tribute.dateOfDeath) {
            if (datesHtml) datesHtml += ' – ';
            datesHtml += formatDate(tribute.dateOfDeath);
        }
        
        const detailUrl = tribute.permalink || '#';
        return `
            <article class="minimal-tribute-item" ${detailUrl !== '#' ? `data-detail-url="${detailUrl}"` : ''}>
                ${photoHtml}
                <div class="tribute-content">
                    <div class="tribute-header">
                        <h3 class="tribute-name">
                            <a href="${detailUrl}" class="tribute-link">${tribute.fullName || 'Unknown'}</a>
                        </h3>
                        <div class="tribute-dates">${datesHtml}</div>
                    </div>
                    ${tribute.serviceInfo ? `<div class="tribute-service">${tribute.serviceInfo}</div>` : ''}
                </div>
            </article>
        `;
    }
    
    // Search functionality is now handled by unified search component
    
    // Whole-row click via inner link
    tributesList.on('click', '.minimal-tribute-item', function(e){
        if (e.target.closest && e.target.closest('a')) return;
        const a = $(this).find('a.tribute-link').get(0);
        const href = a ? a.href : $(this).data('detail-url');
        if (href && href !== '#') window.location.assign(href);
    }).on('keydown', '.minimal-tribute-item', function(e){
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const a = $(this).find('a.tribute-link').get(0);
            if (a) a.click(); else window.location.assign($(this).data('detail-url'));
        }
    });

    // Load more
    loadMoreBtn.on('click', function() {
        if (hasMore && !isLoading) {
            currentPage++;
            loadTributes(currentPage, currentSearch, true);
        }
    });
    
    // Initial load
    loadTributes(1, {
        <?php if (!empty($atts['from'])): ?>
        date_from: '<?php echo esc_js($atts['from']); ?>'
        <?php endif; ?>
    });
});
</script> 