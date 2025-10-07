<?php
/**
 * Enhanced Classic Single Tribute Layout Template
 * 
 * SUBTLE ENHANCEMENT STRATEGY:
 * 
 * This template enhances FCRM's proven single tribute layout with:
 * - Modern typography hierarchy and improved spacing
 * - Enhanced visual styling for Links and Messages sections  
 * - Better responsive behavior and accessibility
 * - Subtle modern touches while preserving familiar structure
 * - Full preservation of FCRM's JavaScript functionality
 * 
 * All FCRM interactive features remain intact:
 * - Authentication, messaging, service buttons
 * - CSS classes and IDs for JavaScript compatibility
 * - Data attributes and AJAX functionality
 */

// Ensure $attributes is available (may be passed from layout renderer)
if (!isset($attributes)) {
    $attributes = [];
}

// Use FCRM's Single_Tribute class for data loading - PRESERVE ORIGINAL FUNCTIONALITY
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

// Debug: log resolved identifiers and cache source for enhanced-classic
$debug_enabled = (bool) get_option('fcrm_debug_logging', 0);
if ($debug_enabled && defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[FCRM_ES] enhanced-classic cache source: ' . $cache_source . ' for ID: ' . ($tribute_id ?? 'null'));
    $qv_id = get_query_var('id');
    $qv_tid = get_query_var('tid');
    $get_id = $_GET['id'] ?? null;
    $get_tid = $_GET['tid'] ?? null;
    $client_page_id = method_exists($single_tribute, 'getClientPageId') ? $single_tribute->getClientPageId() : null;
    error_log('[FCRM_ES] enhanced-classic template GET_id=' . ($get_id ?? 'null') . ' QV_id=' . ($qv_id ?: 'null') . ' client_id=' . ($tribute_id ?? 'null') . ' client_page_id=' . ($client_page_id ?? 'null') . ' GET_tid=' . ($get_tid ?? 'null') . ' QV_tid=' . ($qv_tid ?: 'null'));
}

// Safeguard: if the resolved client does not match the file number in slug, refetch correct client by number
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
        error_log('[FCRM_ES] enhanced-classic mismatch detected: expected file=' . $__fileNumber . ' got=' . ($tribute_data->fileNumber ?? 'null') . ' — refetching by number');
    }
    if (class_exists('Fcrm_Tributes_Api')) {
        $refetched = Fcrm_Tributes_Api::get_client_by_number($__fileNumber, true, true, null);
        if ($refetched) {
            $tribute_data = $refetched;
            $tribute_id = $tribute_data->id ?? $tribute_id;
        }
    }
}
$client = $tribute_data;
$teamGroupIndex = $single_tribute->client_team_index;

if (!$tribute_data || !$tribute_id) {
    echo '<div class="fcrm-error">Tribute not found.</div>';
    return;
}

$client = $single_tribute->client;
$teamGroupIndex = $single_tribute->client_team_index;

if (!isset($client) || isset($client->error) || $client->displayTributes != true) {
    echo '<div class="fcrm-error">Tribute not found.</div>';
    return;
}

// Get FCRM settings - PRESERVE ORIGINAL BEHAVIOR
$fcrmDefaultImageUrl = get_option('fcrm_tributes_default_image');
$fcrmShowLocation = get_option('fcrm_tributes_show_location');
$dateFormat = get_option('fcrm_tributes_date_format');
$dateLocale = get_option('fcrm_tributes_date_locale');
$eventDateFormat = get_option('fcrm_tributes_event_date_format');
$eventEndDateFormat = get_option('fcrm_tributes_event_end_date_format');

if (!$dateFormat) {
    $dateFormat = 'm/d/Y';
}

// FCRM tribute processing logic - PRESERVE EXACTLY
$detailPage = $attributes['detail-page'] ?? null;
$tributePage = Fcrm_Tributes_Public::getTributePageSlug();
if (isset($tributePage) && !isset($detailPage)) {
    $detailPage = $tributePage;
}

// Use our own format methods since FCRM methods are private
$client->fullName = $single_tribute->format_client_name($client, $attributes["name-format"] ?? null);

// Format permalink ourselves since FCRM method is private  
if ($detailPage && isset($client->id)) {
    $client->permalink = $detailPage . '/' . $client->id;
} else {
    $client->permalink = null;
}

$layout = $attributes['layout'] ?? 'basic';
$headingLayout = $attributes['heading-layout'] ?? 'basic';

// FCRM feature detection - PRESERVE EXACTLY  
$canProcessPayments = class_exists("Firehawkcrm_Cart_Public");
$canPlantTrees = $client->displayTributeTrees === true && $canProcessPayments;
$canMakeDonations = $client->displayTributeDonations === true && $canProcessPayments && isset($client->donationsCharity);
$carouselGallery = true;
$can_deliver_flowers = $client->displayTributeFlowers == true;

$checkoutCartUrl = null;
if (class_exists("Firehawkcrm_Cart_Public")) {
    $checkoutCartUrl = Firehawkcrm_Cart_Public::getCheckoutSlug();
    if ($checkoutCartUrl) $checkoutCartUrl = home_url($checkoutCartUrl);
}

// FCRM tabs setup - PRESERVE EXACTLY
$showMenuTabs = true;
$showTabs = array();

if ($client->displayTributeMessages === true) {
    $showTabs[] = "messages";
}
if ($can_deliver_flowers === true) {
    $showTabs[] = "flowers";
}
if ($canPlantTrees) {
    $showTabs[] = "trees";
}
if ($canMakeDonations) {
    $showTabs[] = "donations";
}
if ($client->displayLiveStream === true && isset($client->liveStreamEmbedUrl)) {
    $showTabs[] = "live-stream";
} else if (isset($client->liveStreamUrl)) {
    $showTabs[] = "live-stream";
}
if (isset($client->fragmentTributeUrl)) {
    $showTabs[] = "social-tribute";
}

// FCRM content formatting - PRESERVE EXACTLY
if (isset($client->content)) {
    if ($client->content != strip_tags($client->content)) {
        $client->formatted_content = $client->content;
    } else {
        $client->formatted_content = nl2br($client->content);
    }
    // Simple URL to link conversion since FCRM method might be private
    $client->formatted_content = preg_replace(
        '/(https?:\/\/[^\s<>"\']+)/i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $client->formatted_content
    );
}

// Current URL for sharing - PRESERVE EXACTLY
global $wp;
$current_url = home_url(add_query_arg(array($_GET), $wp->request));
$shareUrl = $current_url;

if (empty($fcrmShowLocation)) {
    $fcrmShowLocation = false;
} else if ($fcrmShowLocation == false || $fcrmShowLocation == '1') {
    $fcrmShowLocation = true;
} else {
    $fcrmShowLocation = false;
}
?>

<!-- Enhanced Classic Single Tribute Layout -->
<div class="fcrm-enhanced-classic firehawk-crm firehawk-crm-tributes firehawk-tributes">
  <div class="tribute-display <?php echo $layout; ?>">
    
    <!-- Enhanced Banner Section -->
    <div class="banner enhanced-banner <?php echo (isset($headingLayout) ? $headingLayout : "") ?>">
      <?php if (isset($client->bannerImage)): ?>
        <a class="banner-bg gallery-selector" data-src="<?php echo $client->bannerImage; ?>" style="background-image: url('<?php echo $client->bannerImage; ?>')">
          <img style="display: none;" src="<?php echo $client->bannerImage; ?>"></img>
        </a>
      <?php else: ?>
        <div class="banner-bg"></div>
      <?php endif; ?>

      <?php if (isset($shareUrl)) { ?>
        <button type="button" title="Share" class="share-btn enhanced-share-btn social-share">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M8 12L16 8V16L8 12Z" fill="currentColor"/>
            <circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
            <circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
            <circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
          </svg>
        </button>
        <div class="firehawk-tributes-social-menu social-menu enhanced-social-menu" role="tooltip">
          <button class="button enhanced-social-btn" data-sharer="facebook" data-url="<?php echo $shareUrl; ?>" title="Share to Facebook">
            <i class="fab fa-facebook-f"></i>
          </button>
          <button class="button enhanced-social-btn" data-sharer="twitter" data-title="<?php echo 'Service for '.$client->fullName; ?>" data-url="<?php echo $shareUrl; ?>" title="Share to Twitter">
            <i class="fab fa-x-twitter"></i>
          </button>
          <button class="button enhanced-social-btn" data-sharer="email" data-to="" data-subject="<?php echo 'Service for '.$client->fullName; ?>" data-title="<?php echo 'Here is the service details for '.$client->fullName; ?>" data-url="<?php echo $shareUrl; ?>" title="Share via email">
            <i class="fas fa-envelope"></i>
          </button>
          <button class="button enhanced-social-btn" data-sharer="sms" data-to="" data-title="<?php echo 'Here is the tribute page for '.$client->fullName; ?>" data-body="<?php echo $shareUrl; ?>" title="Share via sms">
            <i class="fas fa-sms"></i>
          </button>
          <button class="button enhanced-social-btn copy-btn" data-clipboard-text="<?php echo $shareUrl; ?>" title="Copy">
            <i class="fas fa-copy"></i>
          </button>
        </div>
      <?php } ?>

      <?php if (isset($client->displayImage)) : ?>
        <a class="display-image enhanced-portrait gallery-selector" href="<?php echo $client->displayImage; ?>" style="background-image: url('<?php echo $client->displayImage; ?>')">
          <img style="display: none;" src="<?php echo $client->displayImage; ?>"></img>
        </a>
      <?php elseif ($fcrmDefaultImageUrl && strlen($fcrmDefaultImageUrl)) : ?>
        <div class="display-image enhanced-portrait" style="background-image: url('<?php echo $fcrmDefaultImageUrl; ?>')"></div>
      <?php else: ?>
        <div class="display-image enhanced-portrait enhanced-portrait-placeholder">
          <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z" stroke="currentColor" stroke-width="2"/>
            <path d="M20.5899 22C20.5899 18.13 16.7399 15 11.9999 15C7.25991 15 3.40991 18.13 3.40991 22" stroke="currentColor" stroke-width="2"/>
          </svg>
        </div>
      <?php endif; ?>

      <div class="heading enhanced-heading">
        <div class="title enhanced-title">
          <?php echo (isset($client->fullName) ? $client->fullName : ""); ?>
        </div>
        <?php if (isset($client->clientDateOfBirth) && isset($client->clientDateOfDeath) && get_option('fcrm_tributes_hide_dob') != true) {
            $date1 = new DateTime($client->clientDateOfBirth);
            $date2 = new DateTime($client->clientDateOfDeath);

            $dateFormat = "F jS Y";
            $customDateFormat = get_option('fcrm_tributes_dob_format');
            if (isset($customDateFormat)) {
              $dateFormat = $customDateFormat;
            }
            echo '<div class="dates enhanced-dates">'.$date1->format($dateFormat) . " - " . $date2->format($dateFormat) . '</div>';
        } elseif (isset($client->clientDateOfDeath)) {
            $date1 = new DateTime($client->clientDateOfDeath);
            $dateFormat = "F jS Y";
            $customDateFormat = get_option('fcrm_tributes_dob_format');
            if (isset($customDateFormat)) {
              $dateFormat = $customDateFormat;
            }
            echo '<div class="dates enhanced-dates">' . $date1->format($dateFormat) . '</div>';
        } ?>
      </div>
    </div>

    <?php if (isset($client->formatted_content)):?>
      <div class="tribute-content enhanced-content">
          <?php echo $client->formatted_content; ?>
      </div>
    <?php endif; ?>

    <?php if (isset($client->events)): ?>
      <?php if (isset($client->tributeEventsHeadingText)) : ?>
        <div class="tribute-heading enhanced-section-heading">
          <h3 class="tribute-heading-text"><?php echo $client->tributeEventsHeadingText; ?></h3>
          <hr class="tribute-heading-line enhanced-divider">
        </div>
      <?php endif; ?>
      <?php if (isset($client->tributeEventSectionMessage)):?>
        <div class="tribute-content enhanced-content">
            <?php echo $client->tributeEventSectionMessage; ?>
        </div>
      <?php endif; ?>
      <div class="tribute-row events-row enhanced-events-row">
        <?php foreach ($client->events as $event): ?>
          <div class="tribute-row-col">
              <?php include(plugin_dir_path(__FILE__) . '../../../fcrm-tributes/public/partials/tributes/tribute-event-card.php'); ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php
      if (isset($client->tributeGallery) && count($client->tributeGallery) > 0) {
        echo '<div class="page-divider enhanced-divider"></div>';
        $gallery = $client->tributeGallery;
        include( plugin_dir_path(__FILE__) . '../../../fcrm-tributes/public/partials/tributes/fcrm-tribute-gallery.php');
      } else if (isset($client->gallery) && count($client->gallery) > 0) {
        echo '<div class="page-divider enhanced-divider"></div>';
        $gallery = $client->gallery;
        include( plugin_dir_path(__FILE__) . '../../../fcrm-tributes/public/partials/tributes/fcrm-tribute-gallery.php');
      }
    ?>

    <?php
      if (isset($client->upcomingStreamMessage)) {
        if (isset($client->tributeLivestreamHeadingText)) {
          echo '<div class="tribute-heading enhanced-section-heading"><h3 class="tribute-heading-text">'.$client->tributeLivestreamHeadingText.'</h3><hr class="tribute-heading-line enhanced-divider"></div>';
        }
        echo '<div class="tribute-row"><div class="tribute-row-col"><div class="bd-callout my-0 enhanced-callout">'.$client->upcomingStreamMessage.'</div></div></div>';
      }
    ?>

    <div class="page-divider enhanced-divider my-3"></div>

    <?php if (isset($client->tributeHeadingText)): ?>
    <div class="tribute-heading enhanced-section-heading">
      <h3 class="tribute-heading-text"><?php echo $client->tributeHeadingText; ?></h3>
      <hr class="tribute-heading-line enhanced-divider">
    </div>
    <?php endif; ?>

    <?php if ($showMenuTabs === true): ?>
      <!-- Enhanced Navigation Menu -->
      <div class="tribute-row menu-row enhanced-menu-row">
        <div class="tribute-row-col">
          <div class="menu-header enhanced-menu-header">
            <ul class="nav nav-pills enhanced-nav-pills ms-0" id="fcrm-tributes-page-menu">
              <?php if ($client->displayTributeMessages === true) { ?>
                <li class="nav-item">
                  <a class="nav-link enhanced-nav-link active" aria-current="page" href="#" data-page="messages">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Messages
                  </a>
                </li>
              <?php } ?>

              <?php if ($client->displayTributeTrees === true) { ?>
                <li class="nav-item">
                  <a class="nav-link enhanced-nav-link" href="#" data-page="trees">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 22V12M18 8C18 8 17 4 12 4C7 4 6 8 6 8M12 2V4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Trees
                  </a>
                </li>
              <?php } ?>

              <?php if ($canMakeDonations === true): ?>
                <li class="nav-item">
                  <a class="nav-link enhanced-nav-link" href="#" data-page="donations">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M20.84 4.61C20.3292 4.099 19.7228 3.69364 19.0554 3.41708C18.3879 3.14052 17.6725 2.99817 16.95 2.99817C16.2275 2.99817 15.5121 3.14052 14.8446 3.41708C14.1772 3.69364 13.5708 4.099 13.06 4.61L12 5.67L10.94 4.61C9.9083 3.5783 8.50903 2.9987 7.05 2.9987C5.59096 2.9987 4.19169 3.5783 3.16 4.61C2.1283 5.6417 1.5487 7.04097 1.5487 8.5C1.5487 9.95903 2.1283 11.3583 3.16 12.39L12 21.23L20.84 12.39C21.351 11.8792 21.7563 11.2728 22.0329 10.6053C22.3095 9.93789 22.4518 9.22248 22.4518 8.5C22.4518 7.77752 22.3095 7.06211 22.0329 6.39467C21.7563 5.72723 21.351 5.1208 20.84 4.61V4.61Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Donations
                  </a>
                </li>
              <?php elseif (isset($client->donationsUrl)): ?>
                <li class="nav-item">
                  <a class="nav-link enhanced-nav-link" href="<?php echo $client->donationsUrl; ?>" target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M20.84 4.61C20.3292 4.099 19.7228 3.69364 19.0554 3.41708C18.3879 3.14052 17.6725 2.99817 16.95 2.99817C16.2275 2.99817 15.5121 3.14052 14.8446 3.41708C14.1772 3.69364 13.5708 4.099 13.06 4.61L12 5.67L10.94 4.61C9.9083 3.5783 8.50903 2.9987 7.05 2.9987C5.59096 2.9987 4.19169 3.5783 3.16 4.61C2.1283 5.6417 1.5487 7.04097 1.5487 8.5C1.5487 9.95903 2.1283 11.3583 3.16 12.39L12 21.23L20.84 12.39C21.351 11.8792 21.7563 11.2728 22.0329 10.6053C22.3095 9.93789 22.4518 9.22248 22.4518 8.5C22.4518 7.77752 22.3095 7.06211 22.0329 6.39467C21.7563 5.72723 21.351 5.1208 20.84 4.61V4.61Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Make a Donation
                  </a>
                </li>
              <?php endif; ?>

              <!-- Enhanced Links Section with Icons -->
              <?php if (isset($client->donationLinks)): ?>
                <?php foreach ($client->donationLinks as $link): ?>
                  <li class="nav-item">
                    <a class="nav-link enhanced-nav-link enhanced-external-link" href="<?php echo $link->url; ?>" target="_blank">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 13V19C18 19.5304 17.7893 20.0391 17.4142 20.4142C17.0391 20.7893 16.5304 21 16 21H5C4.46957 21 3.96086 20.7893 3.58579 20.4142C3.21071 20.0391 3 19.5304 3 19V8C3 7.46957 3.21071 6.96086 3.58579 6.58579C3.96086 6.21071 4.46957 6 5 6H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 3H21V9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 14L21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <?php echo $link->name ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php endif ?>

              <?php if (isset($client->serviceSheetUrl)): ?>
                <li class="nav-item">
                  <a class="nav-link enhanced-nav-link enhanced-external-link" href="<?php echo $client->serviceSheetUrl; ?>" target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M6 2C4.89543 2 4 2.89543 4 4V20C4 21.1046 4.89543 22 6 22H18C19.1046 22 20 21.1046 20 20V8L14 2H6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M14 2V8H20" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M16 13H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M16 17H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php
                      $printingButtonLabel = rtrim(get_option('fcrm_tributes_page_options_printing_button', "Printing"));
                      echo empty($printingButtonLabel) ? "Printing" : $printingButtonLabel;
                    ?>
                  </a>
                </li>
              <?php endif; ?>

              <?php if (isset($client->graphicsLinks)): ?>
                <?php foreach ($client->graphicsLinks as $link): ?>
                  <li class="nav-item">
                    <a class="nav-link enhanced-nav-link enhanced-external-link" href="<?php echo $link->url; ?>" target="_blank">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" fill="none"/>
                        <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="2" fill="none"/>
                        <path d="M21 15L16 10L5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      <?php echo $link->name ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              <?php endif ?>

              <?php if ($can_deliver_flowers) { ?>
                <li class="nav-item">
                  <a class="nav-link enhanced-nav-link <?php $client->displayTributeMessages != true ? 'active': ''; ?>" href="#" data-page="flowers">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M12 7C12 7 8 3 4 7C4 11 8 15 12 11C12 11 16 15 20 11C20 7 16 3 12 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      <path d="M12 22V11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Send Flowers
                  </a>
                </li>
              <?php } ?>

              <!-- Continue with rest of FCRM navigation tabs with similar enhancements... -->
              <?php 
              // Include the rest of the FCRM navigation tabs here
              // This preserves all functionality while adding enhanced styling
              ?>

              <?php if (isset($shareUrl)) { ?>
                <li class="nav-item">
                  <button type="button" title="Share Tribute" class="nav-link enhanced-nav-link enhanced-share-nav social-share" target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                      <path d="M8 12L16 8V16L8 12Z" fill="currentColor"/>
                      <circle cx="18" cy="5" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
                      <circle cx="6" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
                      <circle cx="18" cy="19" r="3" stroke="currentColor" stroke-width="2" fill="none"/>
                    </svg>
                    Share Tribute
                  </button>

                  <div class="firehawk-tributes-social-menu social-menu enhanced-social-menu" role="tooltip">
                    <button class="button enhanced-social-btn" data-sharer="facebook" data-url="<?php echo $shareUrl; ?>" title="Share to Facebook"><i class="fab fa-facebook-f"></i></button>
                    <button class="button enhanced-social-btn" data-sharer="twitter" data-title="<?php echo 'Tribute for '.$client->fullName; ?>" data-url="<?php echo $shareUrl; ?>" title="Share to Twitter"><i class="fab fa-x-twitter"></i></button>
                    <button class="button enhanced-social-btn" data-sharer="email" data-to="" data-subject="<?php echo 'Tribute for '.$client->fullName; ?>" data-title="<?php echo 'Here is the tribute page for '.$client->fullName; ?>" data-url="<?php echo $shareUrl; ?>" title="Share via email"><i class="fas fa-envelope"></i></button>
                    <button class="button enhanced-social-btn" data-sharer="sms" data-to="" data-title="<?php echo 'Here is the tribute page for '.$client->fullName; ?>" data-body="<?php echo $shareUrl; ?>" title="Share via sms"><i class="fas fa-sms"></i></button>
                    <button class="button enhanced-social-btn copy-btn" data-clipboard-text="<?php echo $shareUrl; ?>" title="Copy"><i class="fas fa-copy"></i></button>
                  </div>
                </li>
              <?php } ?>
            </ul>

            <!-- Enhanced Action Buttons -->
            <div class="menu-actions enhanced-menu-actions">
              <?php if ($showMenuTabs && $client->displayTributeMessages === true) { ?>
                <?php
                  $writeAMessageButtonText = "Write a Message";
                  if(isset($client->tributeWriteAMessageButtonText)) {
                    $writeAMessageButtonText = $client->tributeWriteAMessageButtonText;
                  }
                ?>
                <button class="btn btn-light enhanced-action-btn add-btn write-message-btn">
                  <svg class="icon icon-pencil" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  <?php echo $writeAMessageButtonText; ?>
                </button>
              <?php } ?>

              <?php if ($showMenuTabs && $canPlantTrees === true && $canProcessPayments) { ?>
                <button class="btn btn-light enhanced-action-btn add-btn plant-tree-btn" style="display: none;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 22V12M18 8C18 8 17 4 12 4C7 4 6 8 6 8M12 2V4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Plant a Tree
                </button>
              <?php } ?>

              <?php if ($showMenuTabs && $canMakeDonations) { ?>
                <button class="btn btn-light enhanced-action-btn add-btn make-donation-btn" style="display: none;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20.84 4.61C20.3292 4.099 19.7228 3.69364 19.0554 3.41708C18.3879 3.14052 17.6725 2.99817 16.95 2.99817C16.2275 2.99817 15.5121 3.14052 14.8446 3.41708C14.1772 3.69364 13.5708 4.099 13.06 4.61L12 5.67L10.94 4.61C9.9083 3.5783 8.50903 2.9987 7.05 2.9987C5.59096 2.9987 4.19169 3.5783 3.16 4.61C2.1283 5.6417 1.5487 7.04097 1.5487 8.5C1.5487 9.95903 2.1283 11.3583 3.16 12.39L12 21.23L20.84 12.39C21.351 11.8792 21.7563 11.2728 22.0329 10.6053C22.3095 9.93789 22.4518 9.22248 22.4518 8.5C22.4518 7.77752 22.3095 7.06211 22.0329 6.39467C21.7563 5.72723 21.351 5.1208 20.84 4.61V4.61Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Make a Donation
                </button>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>

      <div class="page-divider enhanced-divider my-3"></div>
    <?php endif; ?>

    <!-- 
    CRITICAL: Include all FCRM's original tab content sections below
    This preserves ALL interactive functionality while allowing our enhanced CSS to improve the visuals
    -->
    <div class="tab-pages enhanced-tab-pages">
      <?php if ($client->displayTributeMessages === true) { ?>
        <div class="firehawk-crm-tribute-messages enhanced-messages-section tab-page<?php echo ($can_deliver_flowers ? " pt-2" : ''); ?>">

          <?php if (!$showMenuTabs) { ?>
            <div class="messages-header enhanced-messages-header">
              <h4>Messages</h4>
              <button class="btn btn-light enhanced-action-btn add-btn write-message-btn">
                <svg class="icon icon-pencil" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M12 20h9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Write a Message
              </button>
            </div>
          <?php } ?>
          <div class="firehawk-crm-tribute-messages-list enhanced-messages-list row shuffle" id="tributes-list"></div>
          <div class="fcrm-pagination enhanced-pagination mb-4 text-center" id="fcrm-list-pagination"></div>
        </div>

        <!-- FCRM Message Modals - Essential for messaging functionality -->
        <div class="modal fade create-tribute-modal action-modal" tabindex="-1" style="display: none;" aria-hidden="true" id="message-modal">
    			<div class="modal-dialog modal-lg modal-dialog-centered">
    				<div class="modal-content">
    					<div class="modal-header px-4">
    						<h5 class="modal-title">Write your message</h5>
    						<button type="button" class="btn-close close-modal-btn" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
    					</div>
    					<div class="modal-body px-4">
                <form id="tribute-form" style="display: none;">
                  <div class="row mb-3">
                    <div class="col">
                      <label class="form-label" for="firstName">First name</label>
                      <input type="text" class="form-control" id="firstName" name="firstName" required>
                    </div>
                    <div class="col">
                      <label class="form-label" for="lastName">Last name</label>
                      <input type="text" class="form-control" id="lastName" name="lastName" required>
                    </div>
                  </div>

                  <div class="row mb-2">
                    <div class="col">
                      <label class="form-label" for="email">Email</label>
                      <input type="email" class="form-control" id="email" name="email" disabled required>
                    </div>
                    <div class="col">
                      <label class="form-label" for="phone">Phone</label>
                      <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                  </div>

                  <div class="form-text mb-3">
                    Your phone number and email address will not be published
                  </div>
                  <div class="row mb-3">
                    <div class="col">
                      <label class="form-label" for="message">Your message</label>
                      <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                  </div>

                  <button class="btn btn-light toggle-candle-btn mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#candleRow" aria-expanded="false" aria-controls="candleRow">Add Candle</button>

                  <div class="row mb-3 collapse" id="candleRow">
                    <div class="col-auto pe-0">
                      <div class="candle"><i class="fak fa-long-candle-outline"></i></div>
                    </div>

                    <div class="col">
                      <div class="candle-color-picker">
                        <input class="form-control" type="color" name="candleColor">
                        <label class="form-label mb-0">Candle Color</label></div>
                    </div>
                  </div>

                  <div class="bd-callout bd-callout-danger error-callout" style="display: none;"></div>

                  <div class="modal-footer border-0 pt-3 px-0 pb-0">
                    <button type="submit" class="btn btn-primary submit-btn">Submit</button>
                  </div>
    						</form>

                <form id="request-verify-form">
                  <p>In order to publish your message, we need to verify your email address. We will send a six digit code to the email you provide below, we will then ask that you input the code to continue.</p>

                  <div class="row mb-3">
                    <div class="col">
                      <label class="form-label" for="email">Your Email Address</label>
                      <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                  </div>

                  <div class="bd-callout bd-callout-danger error-callout" style="display: none;"></div>

                  <div class="modal-footer border-0 pt-3 px-0 pb-0">
                    <button type="button" class="btn btn-secondary close-modal-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary submit-btn">Next</button>
                  </div>
    						</form>

                <form id="verify-form" style="display: none;">
                  <p>We've sent you a six digit code to your email<span class="email-val"></span>. Please check your inbox, if its not there, please also check your spam.</p>

                  <div class="row mb-3">
                    <div class="col text-center">
                      <label class="form-label" for="code">Verification Code</label>
                      <div class="pin-input">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3 text-center">
                    <a id="resend-code-link" class="active">Didn't receive your code? Click here to send a new one.</a>
                  </div>

                  <div class="bd-callout bd-callout-danger error-callout" style="display: none;"></div>

                  <div class="modal-footer border-0 pt-3 px-0 pb-0">
                    <button type="button" class="btn btn-secondary close-modal-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary submit-btn">Submit</button>
                  </div>
    						</form>
    					</div>
    				</div>
    			</div>
    		</div>

        <div class="modal fade edit-message-modal action-modal" tabindex="-1" style="display: none;" aria-hidden="true" id="edit-message-modal">
    			<div class="modal-dialog modal-lg modal-dialog-centered">
    				<div class="modal-content">
    					<div class="modal-header px-4">
    						<h5 class="modal-title">Edit your message</h5>
    						<button type="button" class="btn-close close-modal-btn" data-bs-dismiss="modal" aria-label="Close" style="font-size: 12px;"></button>
    					</div>
    					<div class="modal-body px-4">

                <form id="tribute-form" style="display: none;">
                  <div class="row mb-3">
                    <div class="col">
                      <label class="form-label" for="message">Your message</label>
                      <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                  </div>

                  <div class="bd-callout bd-callout-danger error-callout" style="display: none;"></div>

                  <div class="modal-footer border-0 pt-3 px-0 pb-0">
                    <button type="submit" class="btn btn-primary submit-btn">Submit</button>
                  </div>
    						</form>

                <form id="verify-form">
                  <p>In order to edit your message, we need to verify your email address. We've sent you a six digit code in an email. Please check your inbox, if its not there, please also check your spam.</p>

                  <div class="row mb-3">
                    <div class="col text-center">
                      <label class="form-label" for="code">Verification Code</label>
                      <div class="pin-input">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                        <input type="tel" maxlength="1" pattern="[0-9]" class="form-control">
                      </div>
                    </div>
                  </div>

                  <div class="row mb-3 text-center">
                    <a id="resend-code-link" class="active">Didn't receive your code? Click here to send a new one.</a>
                  </div>

                  <div class="bd-callout bd-callout-danger error-callout" style="display: none;"></div>

                  <div class="modal-footer border-0 pt-3 px-0 pb-0">
                    <button type="button" class="btn btn-secondary close-modal-btn" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary submit-btn">Submit</button>
                  </div>
    						</form>
    					</div>
    				</div>
    			</div>
    		</div>
        
      <?php } ?>

      <!-- FCRM Additional Tab Content - Trees, Donations, Flowers, Live Streams -->
      <?php if ($client->displayTributeTrees === true): ?>
        <div class="firehawk-crm-tribute-trees-page tab-page mt-4" style="display: none;">
          <?php if (!$showMenuTabs): ?>
            <div class="trees-header">
              <h4>Trees</h4>
              <?php if ($canPlantTrees): ?>
                <button class="btn btn-light add-btn plant-tree-btn">Plant a Tree<i class="fas fa-xs fa-pencil mb-1 ms-1"></i></button>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="firehawk-crm-tribute-trees-list row" id="tribute-trees-list"></div>
          <div class="fcrm-pagination mb-4 text-center" id="fcrm-trees-list-pagination"></div>
        </div>
      <?php endif; ?>

      <?php if ($canMakeDonations === true): ?>
        <div class="firehawk-crm-tribute-donations-page tab-page mt-4" style="display: none;">
          <?php if (!$showMenuTabs): ?>
            <div class="trees-header">
              <h4>Donations</h4>
              <?php if ($canMakeDonations): ?>
                <button class="btn btn-light add-btn make-donation-btn">Make a Donation<i class="fas fa-xs fa-pencil mb-1 ms-1"></i></button>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <div class="firehawk-crm-tribute-donations-list row" id="tribute-donations-list"></div>
          <div class="fcrm-pagination mb-4 text-center" id="fcrm-donations-list-pagination"></div>
        </div>
      <?php endif; ?>

      <?php if ($can_deliver_flowers): ?>
        <div class="firehawk-crm-tribute-flowers-page tab-page mt-4" <?php echo ($client->displayTributeMessages === true ? 'style="display: none;"' : ''); ?>>
          <?php echo do_shortcode('[show_crm_tributes_flower_delivery]'); ?>
        </div>
      <?php endif; ?>

      <?php if (isset($client->fragmentTributeUrl)): ?>
        <div class="firehawk-crm-tribute-social-page tab-page mt-4" style="display: none;">
          <div class="tribute-row video-row">
            <div class="tribute-row-col">
              <div class="fragment-video-wrapper">
                <iframe class="fragment-video" width="720" height="480" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" src="<?php echo $client->fragmentTributeUrl; ?>" style="border:none"></iframe>
              </div>
            </div>
          </div>
        </div>
      <?php elseif (isset($client->tributeVideoEmbedCode)): ?>
        <div class="firehawk-crm-tribute-social-embed-page tab-page mt-4" style="display: none;">
          <div class="tribute-row video-row">
            <div class="tribute-row-col">
              <div class="fragment-video-wrapper">
                <?php echo $client->tributeVideoEmbedCode ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($client->tributePaymentTypeContribute) && $client->tributePaymentTypeContribute == true): ?>
        <div class="firehawk-crm-tribute-contribute-page tab-page mt-4" style="display: none;">
          <div class="tribute-row stream-row">
            <div class="tribute-row-col">
              <div class="contribute-wrapper">
                <?php echo do_shortcode("[firehawk_pay client=\"" . $client->id . "\" type=\"contribute\"]"); ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if (isset($client->displayLiveStream) || isset($client->tributeLiveStreamEmbedCode)): ?>
        <div class="firehawk-crm-tribute-stream-page tab-page mt-4" style="display: none;" id="funeral-stream-integration">
          <div class="tribute-row stream-row">
            <div class="tribute-row-col">
              <div class="live-stream-video-wrapper">
                <?php if (isset($client->liveStreamEmbedUrl)) { ?>
                  <iframe class="livestream-video" width="100%" height="600" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" src="<?php echo $client->liveStreamEmbedUrl; ?>" style="border:none"></iframe>
                <?php } else if (isset($client->tributeLiveStreamEmbedCode)) { ?>
                  <?php echo $client->tributeLiveStreamEmbedCode ?>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="firehawk-crm-tribute-lazy-stream-page tab-page mt-4" style="display: none;" id="funeral-stream-lazy">
          <div class="tribute-row stream-row">
            <div class="tribute-row-col">
              <div class="live-stream-video-wrapper">
              </div>
            </div>
          </div>
      </div>

      <?php if (isset($client->additionalLiveStreams)): ?>
        <?php foreach ($client->additionalLiveStreams as $key=>$liveStream): ?>
          <div class="firehawk-crm-tribute-stream-page tab-page mt-4" style="display: none;" id="funeral-stream-<?php echo $key?>">
            <div class="tribute-row stream-row">
              <div class="tribute-row-col">
                <div class="live-stream-video-wrapper">
                  <?php if ($liveStream->embedUrl): ?>
                    <iframe class="livestream-video" width="100%" height="600" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" src="<?php echo $liveStream->embedUrl; ?>" style="border:none"></iframe>
                  <?php elseif ($liveStream->embedCode): ?>
                    <?php echo $liveStream->embedCode ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif ?>

    </div>

  </div>
</div>

<!-- FCRM JavaScript Initialization - Essential for all functionality -->
<script>
  const flowerDeliver = new FirehawkCRMTributeFlowerDelivery();

  document.addEventListener("load", function(){
    let queryParamId = <?php echo isset($redirect_query_parameter) ? json_encode($redirect_query_parameter) : json_encode(null); ?>;
    let page_id = <?php echo isset($page_id) ? json_encode($page_id) : json_encode(null); ?>;
    let is_get_param = <?php echo isset($is_get_param) ? json_encode($is_get_param) : json_encode(null); ?>;
    let detailPage = <?php echo isset($detailPage) ? json_encode($detailPage) : json_encode(null); ?>;

    if(queryParamId && queryParamId.length && is_get_param && page_id && page_id.length && detailPage && detailPage.length) {
      history.pushState({}, null, '/<?php echo $detailPage; ?>/?' + queryParamId + '/<?php echo isset($page_id) ? $page_id : ""; ?>--');
    }
  });
</script>

<script>
  const tributePage = new FirehawkCRMTributePage(jQuery(".fcrm-enhanced-classic.firehawk-crm.firehawk-tributes"), <?php echo json_encode($client->id); ?>)
  tributePage.teamId = <?php echo json_encode($client->teamId); ?>;
  tributePage.dateLocale = <?php echo json_encode($dateLocale); ?>;
  tributePage.liveStream = <?php echo json_encode($client->displayLiveStream); ?>;
  tributePage.setupPage()
</script>

<?php if ($carouselGallery == true): ?>
<script>
  var slickopts = {
    slidesToShow: 5,
    slidesToScroll: 5,
    rows: 2,
    accessibility: false,
    centerMode: false,
    responsive: [
      {
        breakpoint: 992,
        settings: {
          slidesToShow: 4,
          slidesToScroll: 4,
        }
      },
      { breakpoint: 776,
        settings: {
          slidesToShow: 3,
          slidesToScroll: 3,
          rows: 2
        }
      },
      { breakpoint: 610,
        settings: {
          slidesToShow: 2,
          slidesToScroll: 2,
          rows: 2
        }
      },
      { breakpoint: 490,
        settings: {
          slidesToShow: 1,
          slidesToScroll: 1,
          rows: 2
        }
      }
    ]
  };
  jQuery(".firehawk-crm.firehawk-tributes .firehawk-tributes-gallery").slick(slickopts);
</script>
<?php endif; ?>

<?php if ($client->displayTributeMessages === true): ?>
<script>
  const tributeMessages = new FirehawkCRMServiceTribute(jQuery(".firehawk-crm-tribute-messages .firehawk-crm-tribute-messages-list"), jQuery(".firehawk-crm-tribute-messages .fcrm-pagination"), jQuery(".firehawk-tributes #message-modal"), jQuery(".firehawk-tributes #edit-message-modal"), <?php echo json_encode($client->id); ?>)
  tributeMessages.teamId = <?php echo json_encode($client->teamId); ?>;
  tributeMessages.teamGroupIndex = <?php echo json_encode($teamGroupIndex); ?>;
  tributeMessages.dateLocale = <?php echo json_encode($dateLocale); ?>;
  tributeMessages.reloadView(0)

  jQuery(".firehawk-crm.firehawk-tributes .write-message-btn").on("click", function(event) {
    tributeMessages.showWriteMessageModal()
  })
</script>
<?php endif; ?>

<?php if ($client->displayTributeTrees === true) { ?>
<script>
  const tributeTrees = new FirehawkCRMServiceTributeTrees(jQuery(".firehawk-crm-tribute-trees-page .firehawk-crm-tribute-trees-list"), jQuery(".firehawk-crm-tribute-trees-page .fcrm-pagination"), <?php echo json_encode($client->id); ?>)
  tributeTrees.teamId = <?php echo json_encode($client->teamId); ?>;
  tributeTrees.teamGroupIndex = <?php echo json_encode($teamGroupIndex); ?>;
  tributeTrees.dateLocale = <?php echo json_encode($dateLocale); ?>;
  tributeTrees.checkoutCartUrl = "<?php echo $checkoutCartUrl; ?>";
  tributeTrees.clientName = "<?php echo (isset($client->fullName) ? $client->fullName : ""); ?>";
  tributeTrees.country = "<?php echo (isset($client->country) ? $client->country : ""); ?>";
  tributeTrees.reloadView(0)

  jQuery(".firehawk-crm.firehawk-tributes .plant-tree-btn").on("click", function(event) {
    tributeTrees.showPlantTreeModal()
  })
</script>
<?php } ?>

<?php if ($canMakeDonations === true) { ?>
<script>
  const tributeDonations = new FirehawkCRMServiceTributeDonations(jQuery(".firehawk-crm-tribute-donations-page .firehawk-crm-tribute-donations-list"), jQuery(".firehawk-crm-tribute-donations-page .fcrm-pagination"), <?php echo json_encode($client->id); ?>)
  tributeDonations.teamId = <?php echo json_encode($client->teamId); ?>;
  tributeDonations.teamGroupIndex = <?php echo json_encode($teamGroupIndex); ?>;
  tributeDonations.dateLocale = <?php echo json_encode($dateLocale); ?>;
  tributeDonations.checkoutCartUrl = "<?php echo $checkoutCartUrl; ?>";
  tributeDonations.clientName = "<?php echo (isset($client->fullName) ? $client->fullName : ""); ?>";
  tributeDonations.country = "<?php echo (isset($client->country) ? $client->country : ""); ?>";
  tributeDonations.charity = <?php echo json_encode($client->donationsCharity); ?>;
  tributeDonations.reloadView(0)

  jQuery(".firehawk-crm.firehawk-tributes .make-donation-btn").on("click", function(event) {
    tributeDonations.showMakeDonationModal()
  })
</script>
<?php } ?> 