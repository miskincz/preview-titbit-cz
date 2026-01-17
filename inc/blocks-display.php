<?php
/**
 * Funkce pro zobrazení bloků pomocí ACF (deprecated)
 * 
 * POZNÁMKA: Tento soubor obsahuje starší implementaci systému bloků
 * Pro aktuální verzi viz blocks-system.php
 * 
 * Původní funkcionalita:
 * - Zobrazení bloků s ACF flexible content
 * - Různé typy bloků: text, obrázek, galerie, produkty, HTML
 * - Shortcode [blok] pro vložení bloků
 */

// Zobrazit blok podle ID nebo slug (starší verze)
function display_block($identifier) {
  // Najít blok podle ID nebo slug
  if (is_numeric($identifier)) {
    $block = get_post($identifier);
  } else {
    $block = get_page_by_path($identifier, OBJECT, 'blok');
  }
  
  if (!$block || $block->post_type !== 'blok') {
    return;
  }
  
  // Získat ACF flexible content
  if (have_rows('blok_obsah', $block->ID)) {
    echo '<div class="custom-block" data-block-id="' . $block->ID . '">';
    
    while (have_rows('blok_obsah', $block->ID)) {
      the_row();
      $layout = get_row_layout();
      
      switch ($layout) {
        case 'text':
          render_text_block();
          break;
        case 'obrazek':
          render_image_block();
          break;
        case 'galerie':
          render_gallery_block();
          break;
        case 'produkty':
          render_products_block();
          break;
        case 'html':
          render_html_block();
          break;
      }
    }
    
    echo '</div>';
  }
}

// Text blok
function render_text_block() {
  $nadpis = get_sub_field('nadpis');
  $text = get_sub_field('text');
  ?>
  <div class="block block--text">
    <?php if ($nadpis) : ?>
      <h2><?php echo esc_html($nadpis); ?></h2>
    <?php endif; ?>
    <?php if ($text) : ?>
      <div class="block__content"><?php echo wpautop($text); ?></div>
    <?php endif; ?>
  </div>
  <?php
}

// Obrázek blok
function render_image_block() {
  $image = get_sub_field('obrazek');
  $popisek = get_sub_field('popisek');
  
  if ($image) :
  ?>
  <div class="block block--image">
    <?php if (is_array($image)) : ?>
      <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
    <?php else : ?>
      <?php echo wp_get_attachment_image($image, 'large'); ?>
    <?php endif; ?>
    <?php if ($popisek) : ?>
      <p class="block__caption"><?php echo esc_html($popisek); ?></p>
    <?php endif; ?>
  </div>
  <?php
  endif;
}

// Galerie blok
function render_gallery_block() {
  $galerie = get_sub_field('galerie');
  
  if ($galerie && is_array($galerie)) :
  ?>
  <div class="block block--gallery">
    <div class="gallery-grid">
      <?php foreach ($galerie as $image_id) : ?>
        <div class="gallery-item">
          <?php echo wp_get_attachment_image($image_id, 'medium'); ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php
  endif;
}

// Produkty blok
function render_products_block() {
  $produkty = get_sub_field('produkty');
  
  if ($produkty && is_array($produkty)) :
  ?>
  <div class="block block--products">
    <ul class="products-list">
      <?php foreach ($produkty as $product) : ?>
        <li>
          <a href="<?php echo get_permalink($product->ID); ?>">
            <?php if (has_post_thumbnail($product->ID)) : ?>
              <?php echo get_the_post_thumbnail($product->ID, 'thumbnail'); ?>
            <?php endif; ?>
            <span><?php echo esc_html(get_the_title($product->ID)); ?></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php
  endif;
}

// HTML blok (vlastní kód)
function render_html_block() {
  $html = get_sub_field('html_kod');
  
  if ($html) :
  ?>
  <div class="block block--html">
    <?php echo $html; // Pozor: používejte pouze důvěryhodný HTML ?>
  </div>
  <?php
  endif;
}

// Shortcode pro vložení bloku
add_shortcode('blok', function($atts) {
  $atts = shortcode_atts(['id' => '', 'slug' => ''], $atts);
  $identifier = $atts['id'] ?: $atts['slug'];
  
  if (!$identifier) return '';
  
  ob_start();
  display_block($identifier);
  return ob_get_clean();
});
