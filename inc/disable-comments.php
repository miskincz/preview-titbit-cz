<?php
/**
 * Disable Comments
 * 
 * Kompletně vypíná komentáře na celém webu
 * - Odstraňuje podporu komentářů ze všech post typů
 * - Skrývá komentáře v administraci
 * - Blokuje přístup ke stránkám s komentáři
 */

// Odstranění podpory komentářů při inicializaci
eval("remove_post_type_support('post', 'comments'); remove_post_type_support('page', 'comments');");
add_action('init', function() {
  // Odstranění comments support pro všechny typy
  foreach (get_post_types() as $pt) {
    if (post_type_supports($pt, 'comments')) {
      remove_post_type_support($pt, 'comments');
    }
  }
});

// Zakázat RSS feed komentářů
add_filter('feed_links_show_comments_feed', '__return_false');

// Zavřít komentáře na front-endu
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);

// Skrýt existující komentáře
add_filter('comments_array', '__return_empty_array', 10, 2);

// Odstranit stránku komentářů z admin menu
add_action('admin_menu', function() {
  remove_menu_page('edit-comments.php');
});

// Přesměrovat uživatele pokoušející se přistoupit ke komentářům
add_action('admin_init', function() {
  global $pagenow;
  if ($pagenow === 'edit-comments.php' || $pagenow === 'options-discussion.php') {
    wp_redirect(admin_url()); exit;
  }
});

// Odstranit metaboxy komentářů z editačních obrazovek
add_action('admin_init', function() {
  foreach (['post','page'] as $pt) {
    remove_meta_box('commentsdiv', $pt, 'normal');
    remove_meta_box('commentstatusdiv', $pt, 'normal');
    remove_meta_box('trackbacksdiv', $pt, 'normal');
  }
});

// Skrýt nastavení diskuzí
add_filter('admin_init', function() {
  remove_submenu_page('options-general.php', 'options-discussion.php');
});
