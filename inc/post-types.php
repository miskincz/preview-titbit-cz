<?php
/**
 * Registrace vlastních post typů
 * 
 * Obsahuje:
 * - CPT "produkt" pro produkty (ovoce, zelenina, ready-to-eat atd.)
 * - CPT "blok" pro opakovaně použitelné HTML bloky (spolupráce, atd.)
 */

// Registrace vlastního post typu: Produkt (produkty)
add_action('init', function(){
  $labels = [
    'name'               => __('Produkty','mytheme'),
    'singular_name'      => __('Produkt','mytheme'),
    'menu_name'          => __('Produkty','mytheme'),
    'name_admin_bar'     => __('Produkt','mytheme'),
    'add_new'            => __('Přidat nový','mytheme'),
    'add_new_item'       => __('Přidat nový produkt','mytheme'),
    'new_item'           => __('Nový produkt','mytheme'),
    'edit_item'          => __('Upravit produkt','mytheme'),
    'view_item'          => __('Zobrazit produkt','mytheme'),
    'all_items'          => __('Všechny produkty','mytheme'),
    'search_items'       => __('Hledat produkty','mytheme'),
    'not_found'          => __('Nenalezeny žádné produkty','mytheme'),
    'not_found_in_trash' => __('V koši nejsou žádné produkty','mytheme'),
  ];

  $args = [
    'labels'             => $labels,
    'public'             => true,
    'has_archive'        => true,
    'rewrite'            => ['slug' => 'produkt', 'with_front' => false],
    'menu_position'      => 20,
    'menu_icon'          => 'dashicons-products',
    'supports'           => ['title','editor','thumbnail','excerpt','author','revisions'],
    'show_in_rest'       => true,
  ];

  register_post_type('produkt', $args);
});

// Custom Post Type: Bloky (pro HTML bloky - spolupráce, atd.)
add_action('init', function() {
  register_post_type('blok', [
    'labels' => [
      'name' => 'Bloky',
      'singular_name' => 'Bloky',
      'add_new' => 'Přidat blok',
      'add_new_item' => 'Přidat nový blok',
      'edit_item' => 'Upravit blok',
      'all_items' => 'Všechny bloky',
    ],
    'public' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'menu_icon' => 'dashicons-awards',
    'supports' => ['title', 'editor', 'thumbnail'],
    'has_archive' => false,
    'publicly_queryable' => false,
  ]);
});
