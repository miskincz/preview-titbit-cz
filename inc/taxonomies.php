<?php
/**
 * Registrace vlastních taxonomií
 * 
 * Obsahuje:
 * - Taxonomie "produkt_kategorie" pro CPT "produkt"
 * - Hierarchická struktura kategorií produktů
 * - Helper funkce pro zobrazení stromu kategorií
 */

// Registrace vlastní taxonomy: Kategorie produktů pro CPT produkt
add_action('init', function(){
  $labels = [
    'name'              => __('Kategorie produktů','mytheme'),
    'singular_name'     => __('Kategorie produktu','mytheme'),
    'search_items'      => __('Hledat kategorie','mytheme'),
    'all_items'         => __('Všechny kategorie','mytheme'),
    'parent_item'       => __('Nadřazená kategorie','mytheme'),
    'parent_item_colon' => __('Nadřazená kategorie:','mytheme'),
    'edit_item'         => __('Upravit kategorii','mytheme'),
    'update_item'       => __('Aktualizovat kategorii','mytheme'),
    'add_new_item'      => __('Přidat novou kategorii','mytheme'),
    'new_item_name'     => __('Název nové kategorie','mytheme'),
    'menu_name'         => __('Kategorie produktů','mytheme'),
  ];

  $args = [
    'hierarchical'      => true,  // Umožňuje hierarchii (jako kategorie)
    'labels'            => $labels,
    'show_ui'           => true,
    'show_admin_column' => true,
    'query_var'         => true,
    'rewrite'           => ['slug' => 'produkt-kategorie', 'with_front' => false],
    'show_in_rest'      => true,
  ];

  register_taxonomy('produkt_kategorie', ['produkt'], $args);
});

// Helper funkce: Vykreslí strom kategorií s označením aktivní položky
if (!function_exists('render_prod_kat_tree')) {
  function render_prod_kat_tree($parent_id = 0) {
    $term = get_queried_object();
    $terms = get_terms([
      'taxonomy'   => 'produkt_kategorie',
      'hide_empty' => false,
      'parent'     => $parent_id,
    ]);
    if ($terms && !is_wp_error($terms)) {
      echo '<ul class="prod-cat-tree">';
      foreach ($terms as $t) {
        // Přidat class "active" pro aktuální kategorii
        $active = ($t->term_id == $term->term_id) ? ' active' : '';
        echo '<li class="prod-cat-item'.esc_attr($active).'">';
        echo '<a href="'.esc_url(get_term_link($t)).'">'.esc_html($t->name).'</a>';
        // Rekurzivní volání pro podkategorie
        render_prod_kat_tree($t->term_id);
        echo '</li>';
      }
      echo '</ul>';
    }
  }
}