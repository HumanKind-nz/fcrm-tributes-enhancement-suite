/**
 * FCRM Cache Controls
 * 
 * Handles admin bar cache clearing functionality
 */

/**
 * Clear all FCRM cache
 */
function fcrmClearCache() {
    if (!confirm('Are you sure you want to clear all FCRM cache? This will temporarily slow down tribute pages until the cache is rebuilt.')) {
        return;
    }

    // Show loading indicator
    const cacheNode = document.getElementById('wp-admin-bar-fcrm-cache');
    if (cacheNode) {
        cacheNode.style.opacity = '0.5';
    }

    // Make AJAX request
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'fcrm_clear_cache',
            nonce: fcrmCacheNonce
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                showCacheMessage('Cache cleared successfully!', 'success');
                
                // Update cache stats if visible
                updateCacheStats();
            } else {
                showCacheMessage('Failed to clear cache: ' + response.message, 'error');
            }
        },
        error: function() {
            showCacheMessage('Network error while clearing cache', 'error');
        },
        complete: function() {
            // Remove loading indicator
            if (cacheNode) {
                cacheNode.style.opacity = '1';
            }
        }
    });
}

/**
 * Clear cache for specific client
 * 
 * @param {string} clientId Client ID to clear cache for
 */
function fcrmClearClientCache(clientId) {
    if (!confirm('Clear cache for this tribute?')) {
        return;
    }

    // Show loading indicator
    const cacheNode = document.getElementById('wp-admin-bar-fcrm-cache');
    if (cacheNode) {
        cacheNode.style.opacity = '0.5';
    }

    // Make AJAX request
    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'fcrm_clear_client_cache',
            client_id: clientId,
            nonce: fcrmCacheNonce
        },
        success: function(response) {
            if (response.success) {
                showCacheMessage('Tribute cache cleared successfully!', 'success');
                updateCacheStats();
            } else {
                showCacheMessage('Failed to clear tribute cache: ' + response.message, 'error');
            }
        },
        error: function() {
            showCacheMessage('Network error while clearing tribute cache', 'error');
        },
        complete: function() {
            // Remove loading indicator
            if (cacheNode) {
                cacheNode.style.opacity = '1';
            }
        }
    });
}

/**
 * Show cache operation message
 * 
 * @param {string} message Message to display
 * @param {string} type Message type (success, error, info)
 */
function showCacheMessage(message, type = 'info') {
    // Create message element
    const messageDiv = document.createElement('div');
    messageDiv.className = `fcrm-cache-message fcrm-cache-message-${type}`;
    messageDiv.textContent = message;
    
    // Style the message
    messageDiv.style.cssText = `
        position: fixed;
        top: 32px;
        right: 20px;
        background: ${type === 'success' ? '#46b450' : type === 'error' ? '#dc3232' : '#0073aa'};
        color: white;
        padding: 12px 20px;
        border-radius: 4px;
        z-index: 999999;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        transition: opacity 0.3s ease;
    `;
    
    // Add to page
    document.body.appendChild(messageDiv);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        messageDiv.style.opacity = '0';
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 300);
    }, 4000);
}

/**
 * Update cache statistics in admin bar
 */
function updateCacheStats() {
    // This would make an AJAX call to get updated stats
    // For now, we'll just refresh the page after a short delay
    setTimeout(() => {
        const statsNode = document.getElementById('wp-admin-bar-fcrm-cache-stats');
        if (statsNode) {
            // Update the stats display
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'fcrm_get_cache_stats',
                    nonce: fcrmCacheNonce
                },
                success: function(response) {
                    if (response.success && statsNode.querySelector('a')) {
                        statsNode.querySelector('a').textContent = `Cached Items: ${response.data.transient_count}`;
                    }
                }
            });
        }
    }, 1000);
}

/**
 * Initialize cache controls when DOM is ready
 */
jQuery(document).ready(function($) {
    // Add hover effects to cache menu items
    $('#wp-admin-bar-fcrm-cache .ab-item').hover(
        function() {
            $(this).css('background-color', 'rgba(255,255,255,0.1)');
        },
        function() {
            $(this).css('background-color', '');
        }
    );
    
    // Add keyboard support for cache controls
    $('#wp-admin-bar-fcrm-cache-clear-all a').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            fcrmClearCache();
        }
    });
    
    $('#wp-admin-bar-fcrm-cache-clear-client a').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            const onclick = this.getAttribute('onclick');
            if (onclick) {
                eval(onclick);
            }
        }
    });
}); 