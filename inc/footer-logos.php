<?php
/**
 * Footer Logos Management
 * 
 * Umožňuje správu log v patičce webu prostřednictvím metaboxu
 * na specifické stránce (ID 9 - Základní nastavení)
 * 
 * Funkce:
 * - Vlastní metabox pro výběr více obrázků
 * - Přetahování pro změnu pořadí
 * - Uložení pořadí log
 * - Helper funkce pro zobrazení na frontendu
 */

// ID stránky s logy
if (!defined('MY_FOOTER_LOGOS_PAGE_ID')) {
  define('MY_FOOTER_LOGOS_PAGE_ID', 9);
}

// Registrace metaboxu (opraven podpis callbacku – jen $post)
add_action('add_meta_boxes_page', function ($post) {
  if ($post && (int)$post->ID === (int)MY_FOOTER_LOGOS_PAGE_ID) {
    add_meta_box('footer_logos_box', 'Loga do patičky', 'mytheme_footer_logos_metabox_cb', 'page', 'normal', 'default');
  }
});

// Callback funkce pro vykreslení metaboxu
function mytheme_footer_logos_metabox_cb($post) {
  $ids = (array) get_post_meta($post->ID, 'footer_logos', true);
  $ids = array_filter(array_map('intval', $ids));
  wp_nonce_field('save_footer_logos', 'footer_logos_nonce');
  echo '<p>Vyberte nebo přetáhněte více obrázků. Pořadí lze měnit přetažením.</p>';
  echo '<div id="footer-logos-wrapper" class="footer-logos-wrapper">';
  foreach ($ids as $id) {
    $thumb = wp_get_attachment_image($id, 'thumbnail');
    echo '<div class="footer-logo-item" data-id="'.$id.'">'.$thumb.'<span class="remove">×</span></div>';
  }
  echo '</div>';
  echo '<input type="hidden" id="footer-logos-input" name="footer_logos" value="'.esc_attr(implode(',', $ids)).'" />';
  echo '<p><button type="button" class="button" id="add-footer-logos">Přidat loga</button></p>';
  echo '<style>.footer-logos-wrapper{display:flex;gap:8px;flex-wrap:wrap}.footer-logo-item{position:relative;cursor:move}.footer-logo-item img{display:block;height:80px;width:auto}.footer-logo-item .remove{position:absolute;top:-6px;right:-6px;background:#d63638;color:#fff;border-radius:50%;width:18px;height:18px;line-height:18px;text-align:center;font-weight:bold;font-size:12px;cursor:pointer}</style>';
}

// Uložení dat metaboxu
add_action('save_post_page', function ($post_id) {
  if ($post_id !== (int)MY_FOOTER_LOGOS_PAGE_ID) return;
  if (!isset($_POST['footer_logos_nonce']) || !wp_verify_nonce($_POST['footer_logos_nonce'], 'save_footer_logos')) return;
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  $raw = isset($_POST['footer_logos']) ? sanitize_text_field($_POST['footer_logos']) : '';
  $ids = array_filter(array_map('intval', array_map('trim', explode(',', $raw))));
  update_post_meta($post_id, 'footer_logos', $ids);
});

// Načtení skriptů pro admin
add_action('admin_enqueue_scripts', function () {
  $screen = get_current_screen();
  if ($screen && $screen->id === 'page' && isset($_GET['post']) && (int)$_GET['post'] === (int)MY_FOOTER_LOGOS_PAGE_ID) {
    wp_enqueue_media();
    wp_enqueue_script('footer-logos-admin', get_template_directory_uri().'/assets/js/admin-logos.js', ['jquery','jquery-ui-sortable'], '1.0', true);
  }
});

// Helper funkce pro front-end zobrazení log
function mytheme_get_footer_logos() {
  $ids = (array) get_post_meta((int)MY_FOOTER_LOGOS_PAGE_ID, 'footer_logos', true);
  return array_filter(array_map('intval', $ids));
}

