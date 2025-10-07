/**
 * FCRM Loading Spinner Manager
 *
 * Handles loading states for tribute pages and API calls
 */

class FCRMLoadingManager {
    constructor() {
        this.overlay = null;
        this.isActive = false;
        this.init();
    }

    init() {
        // Create loading overlay on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.createOverlay());
        } else {
            this.createOverlay();
        }

        // Hook into FCRM's AJAX calls if available
        this.hookIntoFCRM();

        // Handle page navigation loading
        this.handlePageNavigation();
    }

    createOverlay() {
        // Don't create if already exists
        if (this.overlay) return;

        this.overlay = document.createElement('div');
        this.overlay.className = 'fcrm-loading-overlay';
        this.overlay.innerHTML = `
            <div class="fcrm-loading-content">
                <div class="fcrm-loading-spinner"></div>
                <div class="fcrm-loading-text">Loading tribute...</div>
            </div>
        `;

        document.body.appendChild(this.overlay);
    }

    show(message = 'Loading tribute...') {
        if (!this.overlay) this.createOverlay();

        const textElement = this.overlay.querySelector('.fcrm-loading-text');
        if (textElement) {
            textElement.textContent = message;
        }

        this.overlay.classList.add('active');
        this.isActive = true;

        // Prevent scrolling while loading
        document.body.style.overflow = 'hidden';
    }

    hide() {
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }

        this.isActive = false;

        // Restore scrolling
        document.body.style.overflow = '';
    }

    showPageLoading(container, message = 'Loading...') {
        if (!container) return;

        // Remove existing loading indicator
        const existing = container.querySelector('.fcrm-page-loading');
        if (existing) existing.remove();

        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'fcrm-page-loading';
        loadingDiv.innerHTML = `
            <div class="fcrm-loading-content">
                <div class="fcrm-loading-spinner"></div>
                <div class="fcrm-loading-text">${message}</div>
            </div>
        `;

        container.style.position = 'relative';
        container.appendChild(loadingDiv);
    }

    hidePageLoading(container) {
        if (!container) return;

        const loading = container.querySelector('.fcrm-page-loading');
        if (loading) {
            loading.remove();
        }
    }

    hookIntoFCRM() {
        // Hook into jQuery AJAX if available and FCRM is using it
        if (typeof jQuery !== 'undefined') {
            const $ = jQuery;

            // Show loading on AJAX start
            $(document).ajaxStart(() => {
                // Only show for FCRM-related AJAX calls
                const activeRequests = $.active;
                if (activeRequests > 0) {
                    setTimeout(() => {
                        if ($.active > 0 && !this.isActive) {
                            this.show('Loading tribute data...');
                        }
                    }, 200); // Small delay to avoid flashing for quick requests
                }
            });

            // Hide loading on AJAX complete
            $(document).ajaxStop(() => {
                setTimeout(() => {
                    if ($.active === 0) {
                        this.hide();
                    }
                }, 100);
            });

            // Handle AJAX errors
            $(document).ajaxError((event, xhr, settings) => {
                console.warn('FCRM AJAX error:', xhr.status, settings.url);
                this.hide();
            });
        }

        // Hook into fetch API for modern implementations
        this.interceptFetch();
    }

    interceptFetch() {
        const originalFetch = window.fetch;
        let pendingRequests = 0;

        window.fetch = async (url, options = {}) => {
            // Only intercept FCRM API calls
            if (typeof url === 'string' && this.isFCRMApiUrl(url)) {
                pendingRequests++;

                // Show loading after delay
                const loadingTimer = setTimeout(() => {
                    if (pendingRequests > 0 && !this.isActive) {
                        this.show('Loading tribute data...');
                    }
                }, 200);

                try {
                    const response = await originalFetch(url, options);
                    return response;
                } catch (error) {
                    console.warn('FCRM Fetch error:', error);
                    throw error;
                } finally {
                    pendingRequests--;
                    clearTimeout(loadingTimer);

                    if (pendingRequests === 0) {
                        setTimeout(() => this.hide(), 100);
                    }
                }
            }

            return originalFetch(url, options);
        };
    }

    isFCRMApiUrl(url) {
        return url.includes('firehawkTributes') ||
               url.includes('fcrm') ||
               url.includes('tributes');
    }

    handlePageNavigation() {
        // Show loading on page navigation for tribute pages
        if (this.isTributePage()) {
            this.show('Loading tribute page...');

            // Hide loading once page is loaded
            const hideLoading = () => {
                setTimeout(() => this.hide(), 500);
            };

            if (document.readyState === 'complete') {
                hideLoading();
            } else {
                window.addEventListener('load', hideLoading);
            }
        }
    }

    isTributePage() {
        const url = window.location.href;
        const params = new URLSearchParams(window.location.search);

        return url.includes('/tribute') ||
               params.has('id') ||
               document.querySelector('[class*="fcrm"]') !== null;
    }

    // Manual loading control methods
    showGridLoading(gridContainer) {
        if (gridContainer) {
            gridContainer.classList.add('loading');
        }
    }

    hideGridLoading(gridContainer) {
        if (gridContainer) {
            gridContainer.classList.remove('loading');
        }
    }

    showSingleTributeLoading(tributeContainer) {
        if (tributeContainer) {
            tributeContainer.classList.add('loading');
        }
    }

    hideSingleTributeLoading(tributeContainer) {
        if (tributeContainer) {
            tributeContainer.classList.remove('loading');
        }
    }
}

// Initialize loading manager
const fcrmLoading = new FCRMLoadingManager();

// Expose globally for manual control
window.FCRMLoading = fcrmLoading;