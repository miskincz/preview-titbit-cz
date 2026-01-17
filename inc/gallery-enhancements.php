<?php
/**
 * Vylepšení WordPress galerie
 * 
 * Přidává lightbox funkcionalitu a vylepšené styly pro WordPress galerie
 * Používá GLightbox knihovnu pro zobrazení obrázků v lightboxu
 */

// Přidat lightbox funkcionalitu k WordPress galerii
add_filter('wp_get_attachment_link', function($link, $id, $size, $permalink, $icon, $text) {
  if (!$permalink && !$icon && !$text) {
    $full_image = wp_get_attachment_image_src($id, 'full');
    if ($full_image) {
      $link = sprintf(
        '<a href="%s" data-lightbox="gallery" data-title="%s">%s</a>',
        esc_url($full_image[0]),
        esc_attr(get_the_title($id)),
        wp_get_attachment_image($id, $size)
      );
    }
  }
  return $link;
}, 10, 6);

// Přidat vlastní CSS pro galerii
add_action('wp_head', function() {
  ?>
  <style>
    /* Grid layout pro WordPress galerii */
    .wp-block-gallery {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1rem;
      list-style: none;
      padding: 0;
    }
    .wp-block-gallery .wp-block-image {
      margin: 0;
    }
    /* Styly pro obrázky v galerii */
    .wp-block-gallery img {
      width: 100%;
      height: 250px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      transition: transform 0.3s ease;
    }
    .wp-block-gallery img:hover {
      transform: scale(1.05);
    }
  </style>
  <?php
});

// Načtení GLightbox knihovny pro lightbox
add_action('wp_enqueue_scripts', function() {
  wp_enqueue_style('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/css/glightbox.min.css', [], '3.2.0');
  wp_enqueue_script('glightbox', 'https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/js/glightbox.min.js', [], '3.2.0', true);
  
  // Inicializovat GLightbox pro galerii produktů
  wp_add_inline_script('glightbox', '
    document.addEventListener("DOMContentLoaded", function() {
      const lightbox = GLightbox({
        selector: "[data-lightbox]",
        touchNavigation: true,
        loop: true,
        autoplayVideos: false,
        descPosition: "bottom",
        slideEffect: "slide"
      });
    });
  ');
});
