<?php
/**
 * Unified Search Component
 * 
 * Modern search interface with date range picker for all tribute layouts
 * 
 * Variables expected:
 * - $uniqueElementId: Unique identifier for the layout instance
 * - $search_theme: Theme class (modern, elegant, gallery, minimal)
 */

if (!isset($search_theme)) {
    $search_theme = 'modern';
}
?>

<div class="fcrm-unified-search fcrm-<?php echo esc_attr($search_theme); ?>-search">
    <div class="search-container">
        <!-- Name Search - Primary -->
        <div class="name-search-<?php echo esc_attr($search_theme); ?>">
            <form data-action="search" class="search-form">
                <div class="input-group">
                    <span class="input-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                            <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </span>
                    <input type="text" 
                           class="form-control <?php echo esc_attr($search_theme); ?>-input" 
                           id="grid-search-<?php echo esc_attr($uniqueElementId); ?>" 
                           placeholder="Search by name..."
                           autocomplete="off">
                    <button class="btn reset-btn <?php echo esc_attr($search_theme); ?>-clear" type="button" data-action="clear-search" aria-label="Clear search">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                            <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Date Range Picker - Single Input -->
        <div class="date-picker-<?php echo esc_attr($search_theme); ?>">
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
                       class="form-control <?php echo esc_attr($search_theme); ?>-input date-range-input" 
                       id="date-range-<?php echo esc_attr($uniqueElementId); ?>" 
                       placeholder="Select date range..."
                       readonly>
                <button class="btn reset-btn <?php echo esc_attr($search_theme); ?>-reset" type="button" data-action="clear-dates" aria-label="Clear dates">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-width="2"/>
                        <line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Search Button -->
        <button class="btn <?php echo esc_attr($search_theme); ?>-search-btn" type="button" data-action="search-submit">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span>Search</span>
        </button>
    </div>
</div> 