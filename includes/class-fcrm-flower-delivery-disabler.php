<?php
namespace FCRM\EnhancementSuite;

/**
 * Flower Delivery Disabler
 * 
 * Performance-optimized implementation that disables flower delivery functionality
 * while minimizing runtime overhead.
 */
class Flower_Delivery_Disabler {
	/**
	 * Cache for the disabled state to avoid repeated option checks
	 * 
	 * @var bool|null
	 */
	private $is_disabled = null;

	/**
	 * Initialize the disabler with performance-optimized hooks
	 */
	public function init(): void {
		// Check disabled state once and cache it
		if (!$this->should_disable_flowers()) {
			return;
		}

		// Use early hook to prevent script registration entirely
		add_action('wp_enqueue_scripts', [$this, 'prevent_flower_registration'], 1);
		
		// Use output buffer to efficiently modify HTML output
		add_action('template_redirect', [$this, 'start_output_buffer']);
		
		// Handle API requests early in the request lifecycle
		add_action('init', [$this, 'intercept_flower_requests'], 1);
		
		// Remove flower tab early using existing WordPress filter
		add_filter('fcrm_tribute_tabs', [$this, 'remove_flower_tab'], 1);
	}

	/**
	 * Check if flowers should be disabled (with caching)
	 */
	private function should_disable_flowers(): bool {
		if ($this->is_disabled === null) {
			$this->is_disabled = (bool) apply_filters('fcrm_disable_flowers', 
				get_option('fcrm_enhancement_optimisation_disable_flowers', true)
			);
		}
		return $this->is_disabled;
	}

	/**
	 * Prevent flower-related scripts and styles from being registered
	 * More efficient than dequeuing after registration
	 */
	public function prevent_flower_registration(): void {
		global $wp_scripts, $wp_styles;
		
		// Create efficient lookup arrays for better performance
		$flower_terms = ['flower', 'flowers'];
		
		// Remove from script registry before registration
		if (!empty($wp_scripts->registered)) {
			foreach ($wp_scripts->registered as $handle => $script) {
				foreach ($flower_terms as $term) {
					if (stripos($handle, $term) !== false || 
						(isset($script->src) && stripos($script->src, $term) !== false)) {
						unset($wp_scripts->registered[$handle]);
						break;
					}
				}
			}
		}
		
		// Same for styles
		if (!empty($wp_styles->registered)) {
			foreach ($wp_styles->registered as $handle => $style) {
				foreach ($flower_terms as $term) {
					if (stripos($handle, $term) !== false || 
						(isset($style->src) && stripos($style->src, $term) !== false)) {
						unset($wp_styles->registered[$handle]);
						break;
					}
				}
			}
		}
	}

	/**
	 * Start output buffer to efficiently modify HTML
	 */
	public function start_output_buffer(): void {
		ob_start([$this, 'process_output']);
	}

	/**
	 * Process the HTML output once instead of multiple DOM operations
	 */
	public function process_output(string $html): string {
		// Remove flower-related script tags
		$html = preg_replace(
			'/<script[^>]*(?:flower|flowers)[^>]*>.*?<\/script>/is',
			'',
			$html
		);

		// Add our blocking script once at the end of head
		$blocking_script = '<script>window.FirehawkCRMTributeFlowerDelivery=function(){return{init:function(){return!1},load:function(){return!1}}};</script>';
		$html = str_replace('</head>', $blocking_script . '</head>', $html);

		// Add our CSS once
		$style = '<style>.fcrm-tribute-flowers-container,.fcrm-tributes-flower-menu,.fcrm-tribute-flowers-page-content,.tribute-tab[data-tab="flowers"],[data-flower-delivery="true"]{display:none!important}</style>';
		$html = str_replace('</head>', $style . '</head>', $html);

		return $html;
	}

	/**
	 * Remove flower tab from tribute pages
	 */
	public function remove_flower_tab($tabs): array {
		unset($tabs['flowers']);
		return $tabs;
	}

	/**
	 * Intercept flower delivery API requests early
	 */
	public function intercept_flower_requests(): void {
		if (!isset($_REQUEST['action'])) {
			return;
		}

		// Use static array for better performance
		static $flower_actions = [
			'getProducts' => 1,
			'getProduct' => 1,
			'getTree' => 1,
			'getTreesTotal' => 1,
			'addToCart' => 1,
			'removeFromCart' => 1,
			'getCart' => 1,
			'getCustomerService' => 1,
			'checkout' => 1,
			'processOrder' => 1,
			'createAuthorizeNetHostedForm' => 1,
			'checkOrder' => 1,
			'setFlowerSessionData' => 1,
			'getCartCount' => 1
		];

		if (isset($flower_actions[$_REQUEST['action']])) {
			wp_send_json([
				'success' => false,
				'error' => __('Flower delivery is currently disabled.', 'fcrm-enhancement-suite'),
				'code' => 'feature_disabled'
			], 403);
		}
	}
}