<?php
/**
 * Univerzální systém pro zobrazení bloků
 * 
 * Systém pro snadné zobrazení opakovaně použitelných HTML bloků
 * (spolupráce, zásobujeme restaurace, atd.)
 * 
 * Funkce:
 * - display_block() - hlavní funkce pro zobrazení bloku
 * - display_spoluprace() - zkrácená verze pro bloky typu "blok"
 * - Shortcodes [spoluprace] a [block] pro vložení do obsahu
 * - Možnost použití vlastních šablon
 */

// Hlavní funkce pro zobrazení bloku
function display_block($post_type, $identifier, $template = null) {
  // Najít blok podle ID nebo slug
  if (is_numeric($identifier)) {
    $block = get_post($identifier);
  } else {
    $block = get_page_by_path($identifier, OBJECT, $post_type);
  }
  
  if (!$block || $block->post_type !== $post_type) {
    return;
  }
  
  // Pokud je specifikována vlastní šablona, použij ji
  if ($template && file_exists(get_template_directory() . '/template-parts/blocks/' . $template . '.php')) {
    set_query_var('block_post', $block);
    get_template_part('template-parts/blocks/' . $template);
    return;
  }
  
  // Výchozí výpis HTML obsahu bloku
  echo '<div class="block block--' . esc_attr($post_type) . '" data-block-id="' . $block->ID . '">';
  
  // Zobrazit featured image, pokud existuje
  if (has_post_thumbnail($block->ID)) {
    echo '<div class="block__image">';
    echo get_the_post_thumbnail($block->ID, 'large');
    echo '</div>';
  }
  
  // Zobrazit nadpis
  echo '<div class="block__title"><h2>' . esc_html($block->post_title) . '</h2></div>';
  
  // Zobrazit obsah
  echo '<div class="block__content">';
  echo apply_filters('the_content', $block->post_content);
  echo '</div>';
  
  echo '</div>';
}

// Zkrácená funkce pro spolupráci a podobné bloky
function display_spoluprace($identifier, $template = null) {
  display_block('blok', $identifier, $template);
}

// Shortcode pro vložení spolupráce do obsahu
add_shortcode('spoluprace', function($atts) {
  $atts = shortcode_atts([
    'id' => '', 
    'slug' => '',
    'template' => null
  ], $atts);
  
  $identifier = $atts['id'] ?: $atts['slug'];
  
  if (!$identifier) return '';
  
  ob_start();
  display_spoluprace($identifier, $atts['template']);
  return ob_get_clean();
});

// Univerzální shortcode pro jakýkoliv blok
add_shortcode('block', function($atts) {
  $atts = shortcode_atts([
    'type' => 'spoluprace',
    'id' => '', 
    'slug' => '',
    'template' => null
  ], $atts);
  
  $identifier = $atts['id'] ?: $atts['slug'];
  
  if (!$identifier) return '';
  
  ob_start();
  display_block($atts['type'], $identifier, $atts['template']);
  return ob_get_clean();
});
