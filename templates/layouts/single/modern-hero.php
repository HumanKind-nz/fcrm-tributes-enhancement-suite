<?php
/**
 * Modern Hero Single Tribute Layout Template
 * 
 * HYBRID APPROACH IMPLEMENTATION:
 * 
 * This template preserves ALL FCRM functionality while modernising visuals:
 * - Uses original FCRM data loading methods
 * - Preserves ALL CSS classes and IDs that JavaScript expects
 * - Maintains data-* attributes for interactive features
 * - Keeps authentication, messaging, and service button systems
 * - Adds modern styling layer on top of functional structure
 * 
 * Critical: We MUST maintain FCRM's structure for JavaScript compatibility
 */

// Use FCRM's own Single_Tribute system - PRESERVE ORIGINAL FUNCTIONALITY
if (!class_exists('Single_Tribute')) {
    echo '<div class="fcrm-error">FCRM Tributes plugin not available.</div>';
    return;
}

// Use Firehawk's client caching if available (v2.3.1+)
global $activeTribute;
$cache_source = 'new_instance';

if (isset($activeTribute) && method_exists($activeTribute, 'get_active_page_id')) {
    $activePageId = $activeTribute->get_active_page_id();
    $activeClient = $activeTribute->getClient();

    // Verify cache matches requested tribute
    if ($activePageId == $activeTribute->client_page_id && isset($activeClient)) {
        $single_tribute = $activeTribute;  // Reuse cached instance
        $cache_source = 'firehawk_global';
    }
}

// Fallback: create new instance if cache not available
if (!isset($single_tribute)) {
    $single_tribute = new Single_Tribute();
    $activeTribute = $single_tribute;  // Set global for subsequent requests
    $single_tribute->detectClient();
}

$tribute_data = $single_tribute->client;
$tribute_id = $tribute_data->id ?? null;

// Debug: log resolved identifiers and cache source
$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[FCRM_ES] modern-hero cache source: ' . $cache_source . ' for ID: ' . ($tribute_id ?? 'null'));
    $qv_id = get_query_var('id');
    $qv_tid = get_query_var('tid');
    $get_id = $_GET['id'] ?? null;
    $get_tid = $_GET['tid'] ?? null;
    $client_page_id = method_exists($single_tribute, 'getClientPageId') ? $single_tribute->getClientPageId() : null;
    error_log('[FCRM_ES] modern-hero template GET_id=' . ($get_id ?? 'null') . ' QV_id=' . ($qv_id ?: 'null') . ' client_id=' . ($tribute_id ?? 'null') . ' client_page_id=' . ($client_page_id ?? 'null') . ' GET_tid=' . ($get_tid ?? 'null') . ' QV_tid=' . ($qv_tid ?: 'null'));
}

// Safeguard: if resolved client doesn't match slug file number, refetch by number
$__slug = get_query_var('id');
$__fileNumber = null;
if ($__slug) {
    $parts = explode('-', $__slug);
    if (count($parts) >= 3) {
        $__fileNumber = implode('-', array_slice($parts, 2));
    }
}
if ($__fileNumber && (!isset($tribute_data->fileNumber) || strcasecmp($tribute_data->fileNumber, $__fileNumber) !== 0)) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[FCRM_ES] modern-hero mismatch detected: expected file=' . $__fileNumber . ' got=' . ($tribute_data->fileNumber ?? 'null') . ' — refetching by number');
    }
    if (class_exists('Fcrm_Tributes_Api')) {
        $refetched = Fcrm_Tributes_Api::get_client_by_number($__fileNumber, true, true, null);
        if ($refetched) {
            $tribute_data = $refetched;
            $tribute_id = $tribute_data->id ?? $tribute_id;
        }
    }
}


if (!$tribute_data || !$tribute_id) {
    echo '<div class="fcrm-error">Tribute not found.</div>';
    return;
}

// Add fullName using Single_Tribute's formatter (public instance method)
if (isset($single_tribute) && isset($tribute_data)) {
    $nameFormat = isset($attributes) ? ($attributes['name-format'] ?? null) : null;
    $tribute_data->fullName = $single_tribute->format_client_name($tribute_data, $nameFormat);
}

// Get other FCRM settings - PRESERVE ORIGINAL BEHAVIOR
$fcrmDefaultImageUrl = get_option('fcrm_tributes_default_image');
$dateFormat = get_option('fcrm_tributes_date_format', 'j M Y');
$fcrmShowLocation = get_option('fcrm_tributes_show_location');
$showLocation = $fcrmShowLocation; // Alias for hero section
$displayServiceInfo = isset($attributes) ? ($attributes['display-service'] ?? true) : true;
$hideDateOfBirth = isset($attributes) ? ($attributes['hide-dob'] ?? false) : false;
$eventDateFormat = get_option('fcrm_tributes_event_date_format');
$eventEndDateFormat = get_option('fcrm_tributes_event_end_date_format');

// Set $client for compatibility with FireHawk's event card template
$client = $tribute_data;
$teamGroupIndex = $single_tribute->client_team_index;

// Build the unique ID for this tribute - PRESERVE FCRM's ID SYSTEM
$uniqueElementId = 'fcrm-tribute-' . $tribute_id;
?>

<!-- Modern Hero Single Tribute Layout -->
<div class="fcrm-modern-single fcrm-modern-hero" id="<?php echo esc_attr($uniqueElementId); ?>">
    
    <!-- Modern Hero Section -->
    <div class="tribute-hero-section">
        <div class="hero-background">
            <?php if (!empty($tribute_data->displayImage)): ?>
                <img 
                    src="<?php echo esc_url($tribute_data->displayImage); ?>" 
                    alt="<?php echo esc_attr($tribute_data->fullName ?? 'Tribute photo'); ?>"
                    class="hero-background-image"
                />
                <div class="hero-overlay"></div>
            <?php endif; ?>
        </div>
        
        <div class="hero-content">
            <div class="container">
                <div class="hero-inner">
                    
                    <!-- Tribute Image -->
                    <div class="tribute-image-container">
                        <?php if (!empty($tribute_data->displayImage)): ?>
                            <img 
                                src="<?php echo esc_url($tribute_data->displayImage); ?>" 
                                alt="<?php echo esc_attr($tribute_data->fullName ?? 'Tribute photo'); ?>"
                                class="tribute-portrait"
                            />
                        <?php elseif (!empty($fcrmDefaultImageUrl)): ?>
                            <img 
                                src="<?php echo esc_url($fcrmDefaultImageUrl); ?>" 
                                alt="<?php echo esc_attr($tribute_data->fullName ?? 'Default tribute photo'); ?>"
                                class="tribute-portrait tribute-default"
                            />
                        <?php else: ?>
                            <div class="tribute-portrait-placeholder">
                                <svg width="120" height="120" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M20.5899 22C20.5899 18.13 16.7399 15 11.9999 15C7.25991 15 3.40991 18.13 3.40991 22" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Tribute Details -->
                    <div class="tribute-details">
                        <h1 class="tribute-name">
                            <?php echo esc_html($tribute_data->fullName ?? 'Unknown'); ?>
                        </h1>
                        
                        <div class="tribute-dates">
                            <?php if (!empty($tribute_data->clientDateOfBirth) && !$hideDateOfBirth): ?>
                                <span class="birth-date">
                                    <?php echo esc_html(date($dateFormat, strtotime($tribute_data->clientDateOfBirth))); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($tribute_data->clientDateOfDeath)): ?>
                                <?php if (!empty($tribute_data->clientDateOfBirth) && !$hideDateOfBirth): ?>
                                    <span class="date-separator">–</span>
                                <?php endif; ?>
                                <span class="death-date">
                                    <?php echo esc_html(date($dateFormat, strtotime($tribute_data->clientDateOfDeath))); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($tribute_data->epitaph)): ?>
                            <div class="tribute-epitaph">
                                <p><?php echo esc_html($tribute_data->epitaph); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Service Information -->
                        <?php if ($displayServiceInfo && (!empty($tribute_data->serviceDateTime) || !empty($tribute_data->serviceVenue))): ?>
                            <div class="service-information">
                                <?php if (!empty($tribute_data->serviceDateTime)): ?>
                                    <div class="service-datetime">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="service-icon">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                            <polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                        <span class="service-date">
                                            <?php echo esc_html(date('l, j F Y \a\t g:i A', strtotime($tribute_data->serviceDateTime))); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tribute_data->serviceVenue) && $showLocation): ?>
                                    <div class="service-venue">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="service-icon">
                                            <path d="M21 10C21 17 12 23 12 23C12 23 3 17 3 10C3 7.61305 3.94821 5.32387 5.63604 3.63604C7.32387 1.94821 9.61305 1 12 1C14.3869 1 16.6761 1.94821 18.3639 3.63604C20.0518 5.32387 21 7.61305 21 10Z" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                        <span class="venue-name">
                                            <?php echo esc_html($tribute_data->serviceVenue); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Service Event Cards Section -->
    <?php if (isset($client->events) && is_array($client->events) && count($client->events) > 0): ?>
      <div class="container events-section">
        <?php if (isset($client->tributeEventsHeadingText)) : ?>
          <div class="tribute-heading modern-section-heading">
            <h2 class="tribute-heading-text"><?php echo esc_html($client->tributeEventsHeadingText); ?></h2>
          </div>
        <?php endif; ?>
        <?php if (isset($client->tributeEventSectionMessage)):?>
          <div class="tribute-content modern-content">
              <?php echo wp_kses_post($client->tributeEventSectionMessage); ?>
          </div>
        <?php endif; ?>

        <?php
        // Find the active FCRM Tributes plugin directory for event card template (version-agnostic)
        $fcrm_event_card_path = null;
        $plugin_dir = WP_PLUGIN_DIR;

        // Check common FCRM plugin directory patterns
        $possible_event_paths = [
          $plugin_dir . '/fcrm-tributes/public/partials/tributes/tribute-event-card.php',
          $plugin_dir . '/fcrm-tributes-2.0.1.12/public/partials/tributes/tribute-event-card.php',
          $plugin_dir . '/fcrm-tributes-2.2.0/public/partials/tributes/tribute-event-card.php',
          $plugin_dir . '/fcrm-tributes-2.3.1/public/partials/tributes/tribute-event-card.php',
          $plugin_dir . '/fcrm-tributes-2.3.1-dev/public/partials/tributes/tribute-event-card.php',
        ];

        foreach ($possible_event_paths as $path) {
          if (file_exists($path)) {
            $fcrm_event_card_path = $path;
            break;
          }
        }
        ?>

        <?php if ($fcrm_event_card_path): ?>
          <div class="tribute-row events-row modern-events-row">
            <?php foreach ($client->events as $event): ?>
              <div class="tribute-row-col">
                  <?php include($fcrm_event_card_path); ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
            <?php error_log('[FCRM_ES] modern-hero: tribute-event-card.php template not found in any FireHawk directory'); ?>
          <?php endif; ?>
          <div class="tribute-row events-row">
            <div class="tribute-row-col">
              <p class="fcrm-error">Service event information unavailable (FireHawk template not found).</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!--
    CRITICAL: Below this point we MUST include FCRM's original structure
    for messaging, service buttons, and interactive features to work.

    This is where the HYBRID approach is essential - we modernise the visual
    presentation above, but preserve FCRM's functional systems below.
    -->


    <!-- Include FCRM's original tribute content structure -->
    <?php
    // HYBRID APPROACH: Include FCRM's original template structure
    // but with our modern CSS classes applied on top

    if (class_exists('Fcrm_Tributes_Public')) {
        // Get FCRM's original single tribute output but apply our modern styling
        $fcrm_public = new Fcrm_Tributes_Public('fcrm-tributes', '2.2.0');
        $original_content = $fcrm_public->shortcode_crm_tribute_display([
            'id' => $tribute_id
        ]);

        // For now, hide the original hero section and show our modern one
        // but keep the messaging and interactive parts
        $modern_content = preg_replace(
            '/<div class="fcrm-tributes.*?<\/div>/s',
            '',
            $original_content,
            1
        );

        echo '<div class="fcrm-original-content-preserved">';
        echo $modern_content;
        echo '</div>';
    }
    ?>

</div> 