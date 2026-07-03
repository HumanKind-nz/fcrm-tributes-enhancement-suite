<?php
declare(strict_types=1);

/**
 * FCRM Enhancement Suite - List View Layout Template
 * 
 * Clean, typography-focused tribute listing with minimal visual elements.
 * Emphasizes readability and essential information with generous white space.
 * 
 * @package FcrmEnhancementSuite
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
    <?php
    $search_theme = 'modern';
    include __DIR__ . '/partials/unified-search.php';
    ?>
    <?php endif; ?>
    
    <!-- Minimal Tributes List -->
    <div class="minimal-tributes-container">
        <div class="minimal-loading" style="display: none;">
            <div class="loading-spinner"></div>
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
        
        // Build params matching FireHawk's approach: only include optional
        // fields when they have values. Sending empty strings for query/dates
        // triggers unintended fuzzy search and breaks date override logic
        // in FireHawk's get_tribute_search() handler.
        const ajaxParams = {
            size: <?php echo intval($atts['size']); ?>,
            from: (page - 1) * <?php echo intval($atts['size']); ?>,
        };

        if (search.name) ajaxParams.query = search.name;
        if (search.date_from) ajaxParams.startDate = moment(search.date_from).startOf("day").valueOf();
        if (search.date_to) ajaxParams.endDate = moment(search.date_to).endOf("day").valueOf();

        <?php if ($sortByService): ?>ajaxParams.sortByService = true;<?php endif; ?>
        <?php if ($nameFormat): ?>ajaxParams.nameFormat = <?php echo json_encode($nameFormat); ?>;<?php endif; ?>
        <?php if ($branch): ?>ajaxParams.branch = <?php echo json_encode($branch); ?>;<?php endif; ?>
        <?php if ($showFutureTributes): ?>ajaxParams.showFutureTributes = true;<?php endif; ?>
        <?php if ($showPastTributes): ?>ajaxParams.showPastTributes = true;<?php endif; ?>
        <?php if ($displayServiceInfo): ?>ajaxParams.displayService = true;<?php endif; ?>
        <?php if ($teamGroupIndex !== null): ?>ajaxParams.teamGroupIndex = <?php echo json_encode($teamGroupIndex); ?>;<?php endif; ?>
        <?php if ($displayBranch): ?>ajaxParams.displayBranch = true;<?php endif; ?>

        const ajaxData = {
            action: 'get_tributes',
            params: ajaxParams
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

            // Sort by service date (descending), falling back to date of death
            // Field names match FireHawk API response: serviceEvent.dateTime, dateOfDeath
            tributes.sort((a, b) => {
                const dateA = (a.serviceEvent && a.serviceEvent.dateTime) || a.dateOfDeath || '';
                const dateB = (b.serviceEvent && b.serviceEvent.dateTime) || b.dateOfDeath || '';
                if (!dateA && !dateB) return 0;
                if (!dateA) return 1;
                if (!dateB) return -1;
                return new Date(dateB) - new Date(dateA);
            });

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