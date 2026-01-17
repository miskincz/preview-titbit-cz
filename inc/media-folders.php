<?php
/**
 * Media Library Folders - Organizace médií do kategorií
 * 
 * Přidává taxonomii pro kategorizaci médií v knihovně médií
 * Umožňuje lepší organizaci obrázků, souborů a dalších médií
 * 
 * Funkce:
 * - Vlastní taxonomie pro média
 * - Filtrování médií podle kategorií v administraci
 * - Automatické vytvoření přednastavených kategorií
 */

// Registrace taxonomie pro kategorie médií
add_action('init', function() {
  register_taxonomy('media_folder', 'attachment', [
    'labels' => [
      'name' => 'Kategorie médií',
      'singular_name' => 'Kategorie',
      'menu_name' => 'Kategorie',
      'all_items' => 'Všechny kategorie',
      'edit_item' => 'Upravit kategorii',
      'view_item' => 'Zobrazit kategorii',
      'update_item' => 'Aktualizovat kategorii',
      'add_new_item' => 'Přidat kategorii',
      'new_item_name' => 'Nový název kategorie',
      'search_items' => 'Hledat kategorie',
    ],
    'hierarchical' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'query_var' => true,
    'rewrite' => false,
    'show_in_rest' => true,
    'show_in_quick_edit' => true,
  ]);
});

// Přidat filtr podle kategorií do Media Library v administraci
add_action('restrict_manage_posts', function() {
  if (get_current_screen()->id === 'upload') {
    $terms = get_terms([
      'taxonomy' => 'media_folder',
      'hide_empty' => false,
    ]);
    
    if ($terms && !is_wp_error($terms)) {
      $current = isset($_GET['media_folder']) ? $_GET['media_folder'] : '';
      echo '<select name="media_folder" class="postform">';
      echo '<option value="">Všechny kategorie</option>';
      foreach ($terms as $term) {
        printf(
          '<option value="%s"%s>%s (%d)</option>',
          esc_attr($term->slug),
          selected($current, $term->slug, false),
          esc_html($term->name),
          $term->count
        );
      }
      echo '</select>';
    }
  }
});

// Vytvořit přednastavené kategorie při aktivaci tématu
add_action('after_switch_theme', function() {
  $folders = [
    'Produkty' => 'produkty',
    'Ovoce a zelenina' => 'ovoce-a-zelenina',
    'Ready to Eat' => 'ready-to-eat',
    'Produktové řady' => 'produktove-rady',
    'Obchody - loga' => 'obchody-loga',
    'E-shopy - loga' => 'eshopy-loga',
    'Blog' => 'blog',
    'Homepage' => 'homepage',
  ];
  
  foreach ($folders as $name => $slug) {
    if (!term_exists($slug, 'media_folder')) {
      wp_insert_term($name, 'media_folder', ['slug' => $slug]);
    }
  }
});
