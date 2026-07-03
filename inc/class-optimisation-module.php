<?php
declare(strict_types=1);

/**
 * FCRM Optimisation Module
 */

if (!defined('ABSPATH')) {
    exit;
}

use function FcrmEnhancementSuite\Helpers\get_setting;

class FCRM_Optimisation_Module {
    
    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 99);

        // Note: Conditional asset loading is now handled by the main plugin class
        // to ensure it works regardless of module enable/disable state
    }

    public function register_settings(): void {
        // Settings now registered centrally in inc/settings-page.php.
    }

    public function render_settings(): void {
        // Settings now rendered by React admin UI.
    }

    /**
     * Enqueue the navigation spinner on grid tribute pages.
     */
    public function enqueue_frontend_assets(): void {
        // Use the standardised tribute page detection from main plugin.
        if (!\FcrmEnhancementSuite\TributeDetection\is_tribute_page()) {
            return;
        }

        // Spinner only on grid pages (not single tribute pages, which set ?id=).
        if (isset($_GET['id'])) {
            return;
        }

        wp_enqueue_style(
            'fcrm-navigation-spinner',
            FCRM_ENHANCEMENT_SUITE_URL . 'assets/css/navigation-spinner.css',
            [],
            FCRM_ENHANCEMENT_SUITE_VERSION
        );

        wp_enqueue_script(
            'fcrm-navigation-spinner',
            FCRM_ENHANCEMENT_SUITE_URL . 'assets/js/navigation-spinner.js',
            [],
            FCRM_ENHANCEMENT_SUITE_VERSION,
            true
        );

        // Inject the configurable spinner colour as a CSS custom property.
        // spinner_color is sanitised on save (sanitize_color_value); escape on output.
        $spinner_color = (string) get_setting('spinner_color', '#667eea');
        wp_add_inline_style(
            'fcrm-navigation-spinner',
            ':root{--fcrm-spinner-color:' . esc_html($spinner_color) . ';}'
        );
    }

}
