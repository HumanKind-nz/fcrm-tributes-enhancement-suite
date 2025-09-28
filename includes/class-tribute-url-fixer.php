<?php
namespace FCRM\EnhancementSuite;

/**
 * Tribute URL Fixer
 *
 * Fixes FCRM tribute ID detection for pretty URLs like /funeral/name-ID/
 * FCRM expects ?id=ID but WordPress generates /funeral/name-ID/ URLs
 */
class Tribute_URL_Fixer {

    /**
     * Initialize the URL fixer
     */
    public static function init() {

        // Hook very early to modify query vars before FCRM processes them
        add_action('parse_request', [__CLASS__, 'parse_tribute_url'], 1);
        add_filter('query_vars', [__CLASS__, 'add_tribute_query_vars'], 10);

        // Also hook into WordPress init to catch earlier requests
        add_action('wp', [__CLASS__, 'parse_tribute_url_fallback'], 1);

        // Hook into template redirect as another fallback
        add_action('template_redirect', [__CLASS__, 'parse_tribute_url_fallback'], 1);

        // Intercept content to convert grid shortcode to single tribute when ID is present
        add_filter('the_content', [__CLASS__, 'convert_grid_to_single_tribute'], 10);

    }

    /**
     * Fallback URL parsing for different hook contexts
     */
    public static function parse_tribute_url_fallback() {
        global $wp;
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        // Check if this looks like a tribute URL and we haven't set the ID yet
        if (self::is_tribute_url($request_uri) && (empty($_GET['id']) || empty($wp->query_vars['id'] ?? null))) {
            $tribute_file_number = self::extract_tribute_id($request_uri);
            $tribute_slug = self::extract_tribute_slug($request_uri);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[FCRM_ES] parse_tribute_url_fallback request_uri=' . $request_uri . ' file=' . ($tribute_file_number ?? 'null') . ' slug=' . ($tribute_slug ?? 'null'));
            }

            // Always expose the parsed file-number as an internal var for our own usage
            if ($tribute_file_number) {
                if (isset($wp->query_vars)) {
                    $wp->query_vars['tribute_id'] = $tribute_file_number;
                }
            }

            // Respect FireHawk's readable permalink flow: pass the full slug to `id`
            $use_custom_link = get_option('fcrm_tributes_readable_permalinks');
            $use_custom_link = !empty($use_custom_link) && $use_custom_link !== '0';

            if ($use_custom_link && $tribute_slug) {
                if (isset($wp->query_vars)) {
                    $wp->query_vars['id'] = $tribute_slug;
                }
                // Sync main WP_Query as well
                global $wp_query;
                if ($wp_query instanceof \WP_Query) {
                    $wp_query->set('id', $tribute_slug);
                }
                $_GET['id'] = $tribute_slug;
            }
        }
    }

    /**
     * Add tribute query vars that WordPress should recognize
     */
    public static function add_tribute_query_vars($vars) {
        $vars[] = 'tribute_id';
        $vars[] = 'tribute_team_index';
        $vars[] = 'tribute_redirect_id';
        return $vars;
    }

    /**
     * Parse tribute URLs and extract the ID
     */
    public static function parse_tribute_url($wp) {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        // Check if this looks like a tribute URL
        if (!self::is_tribute_url($request_uri)) {
            return;
        }

        // Extract components
        $tribute_file_number = self::extract_tribute_id($request_uri);
        $tribute_slug = self::extract_tribute_slug($request_uri);

        // Debug logging
        $debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
        if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FCRM_ES] parse_tribute_url request_uri=' . $request_uri . ' file=' . ($tribute_file_number ?? 'null') . ' slug=' . ($tribute_slug ?? 'null'));
        }

        // Always expose the parsed file-number as an internal var for our own usage
        if ($tribute_file_number) {
            $wp->query_vars['tribute_id'] = $tribute_file_number;
        }

        // Respect FireHawk's readable permalink flow: pass the full slug to `id`
        $use_custom_link = get_option('fcrm_tributes_readable_permalinks');
        $use_custom_link = !empty($use_custom_link) && $use_custom_link !== '0';

        if ($use_custom_link && $tribute_slug) {
            $wp->query_vars['id'] = $tribute_slug;
            // Sync main WP_Query as well
            global $wp_query;
            if ($wp_query instanceof \WP_Query) {
                $wp_query->set('id', $tribute_slug);
            }
            $_GET['id'] = $tribute_slug;
        }
    }

    /**
     * Check if URL looks like a tribute URL (with specific tribute ID)
     */
    private static function is_tribute_url($url) {
        // Check for /funeral/ or tribute-related paths, but only if they have additional path segments
        // This prevents /funeral/ (base page) from being treated as a tribute URL
        $patterns = [
            '/funeral/.+/',
            '/tribute/.+/',
            '/memorial/.+/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match('#' . $pattern . '#', $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract tribute ID from URL path
     *
     * Expected format: /funeral/name-with-dashes-TRIBUTEID/
     * Where TRIBUTEID typically starts with letters (FIN, etc.)
     */
    private static function extract_tribute_id($url) {
        // Remove query string if present
        $path = parse_url($url, PHP_URL_PATH);

        // Pattern to match tribute IDs that start with letters followed by numbers (like FIN53015551)
        // This pattern looks for the last occurrence of letters followed by numbers
        if (preg_match('/.*-([A-Z]+\d+)\/?$/', $path, $matches)) {
            return $matches[1];
        }

        // Fallback pattern: if the above doesn't work, try other patterns
        if (preg_match('/.*-([A-Z0-9]{8,})\/?$/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract the full tribute slug (last path segment) from URL
     * Example: /funeral/stonepaulineannepolly-1-FIN53015553/ -> stonepaulineannepolly-1-FIN53015553
     */
    private static function extract_tribute_slug($url) {
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) {
            return null;
        }
        $segments = array_values(array_filter(explode('/', $path), function($seg) {
            return $seg !== '';
        }));
        if (empty($segments)) {
            return null;
        }
        $last = end($segments);
        // Avoid returning the base path like "funeral" with no slug
        if (in_array($last, ['funeral', 'tribute', 'memorial'], true)) {
            return null;
        }
        return $last;
    }

    /**
     * Get the current tribute ID if available
     */
    public static function get_current_tribute_id() {
        global $wp;

        // Check various sources for tribute ID (prefer main query var first)
        $sources = [
            get_query_var('id'),
            $wp->query_vars['id'] ?? null,
            $_GET['id'] ?? null,
            get_query_var('tribute_id'),
            $wp->query_vars['tribute_id'] ?? null
        ];

        foreach ($sources as $id) {
            if (!empty($id)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[FCRM_ES] get_current_tribute_id source=' . print_r($sources, true) . ' chosen=' . $id);
                }
                return $id;
            }
        }

        // Last resort: try to extract from current URL
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $fallback = self::extract_tribute_id($current_url);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[FCRM_ES] get_current_tribute_id fallback from url=' . $current_url . ' -> ' . ($fallback ?? 'null'));
        }
        return $fallback;
    }

    /**
     * Test API connectivity for a tribute ID
     */
    public static function test_api_response($tribute_id) {
        if (!class_exists('Single_Tribute')) {
            return ['error' => 'Single_Tribute class not available'];
        }

        try {
            // Create Single_Tribute instance and test API
            $single_tribute = new Single_Tribute();

            // Temporarily set the ID to test
            $_GET['id'] = $tribute_id;

            // Try to detect client
            $single_tribute->detectClient();

            $result = [
                'tribute_id' => $tribute_id,
                'client_found' => !empty($single_tribute->client),
                'client_data' => $single_tribute->client,
                'has_name' => !empty($single_tribute->client->clientFirstName ?? null),
                'has_image' => !empty($single_tribute->client->displayImage ?? null),
                'api_errors' => []
            ];

            // Test if we can get basic client info
            if ($single_tribute->client) {
                $result['client_summary'] = [
                    'id' => $single_tribute->client->id ?? 'MISSING',
                    'firstName' => $single_tribute->client->clientFirstName ?? 'MISSING',
                    'lastName' => $single_tribute->client->clientLastName ?? 'MISSING',
                    'displayImage' => $single_tribute->client->displayImage ?? 'MISSING'
                ];
            }

            return $result;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'tribute_id' => $tribute_id
            ];
        }
    }

    /**
     * Convert grid shortcode to single tribute when ID is present in URL
     */
    public static function convert_grid_to_single_tribute($content) {
        // Only process on tribute pages
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        if (!self::is_tribute_url($current_url)) {
            return $content;
        }

        // Check if we have a tribute ID
        $tribute_id = self::get_current_tribute_id();
        if (empty($tribute_id)) {
            return $content;
        }

        // If the page already uses the single tribute shortcode, leave it untouched
        if (preg_match('/\[show_crm_tribute[^\]]*\]/i', $content)) {
            return $content;
        }

        // Convert grid shortcode to single tribute, preserving existing attributes and adding/replacing id
        $content = preg_replace_callback(
            '/\[show_crm_tributes_grid([^\]]*)\]/i',
            function($matches) use ($tribute_id) {
                $attrs = $matches[1] ?? '';
                if (preg_match('/\bid\s*=\s*"[^"]*"/i', $attrs)) {
                    // Replace existing id value
                    $attrs = preg_replace('/\bid\s*=\s*"[^"]*"/i', 'id="' . $tribute_id . '"', $attrs);
                } else {
                    // Append id attribute
                    $attrs = rtrim($attrs) . ' id="' . $tribute_id . '"';
                }
                return '[show_crm_tribute' . $attrs . ']';
            },
            $content
        );

        return $content;
    }

}