<?php
/**
 * Funkce pro zobrazení spolupráce bloků
 */

// Zobrazit spolupráci podle ID nebo slug
function display_spoluprace($identifier) {
  // Najít spolupráci podle ID nebo slug
  if (is_numeric($identifier)) {
    $spoluprace = get_post($identifier);
  } else {
    $spoluprace = get_page_by_path($identifier, OBJECT, 'spoluprace');
  }
  
  if (!$spoluprace || $spoluprace->post_type !== 'spoluprace') {
    return;
  }
  
  // Výpis HTML obsahu
  echo '<div class="spoluprace-block" data-spoluprace-id="' . $spoluprace->ID . '">';
  echo apply_filters('the_content', $spoluprace->post_content);
  echo '</div>';
}

// Shortcode pro vložení spolupráce
add_shortcode('spoluprace', function($atts) {
  $atts = shortcode_atts(['id' => '', 'slug' => ''], $atts);
  $identifier = $atts['id'] ?: $atts['slug'];
  
  if (!$identifier) return '';
  
  ob_start();
  display_spoluprace($identifier);
  return ob_get_clean();
});
