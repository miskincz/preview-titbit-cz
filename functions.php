<?php
/**
 * Hlavní soubor funkcí WordPress tématu
 * Obsahuje: enqueuování stylů/skriptů, registraci post typů, taxonomií, 
 * URL rewrite pravidla, AJAX funkce a helper funkce
 */

// ==========================================
// ENQUEUE STYLŮ A SKRIPTŮ
// ==========================================

// Načtení hlavního CSS souboru
function mytheme_enqueue_styles() {
  wp_enqueue_style('main-style', get_template_directory_uri() . '/css/style.css?ver=1.0.1');
}
add_action('wp_enqueue_scripts', 'mytheme_enqueue_styles');

// Načtení vlastních JavaScriptů (functions.js pro smooth scroll apod.)
add_action('wp_enqueue_scripts', function() {
  wp_enqueue_script('mytheme-functions', get_template_directory_uri() . '/assets/js/functions.js', [], '1.0.0', true);
});

// ==========================================
// ADMIN - QUICK PRODUCT EDITOR
// ==========================================

add_action( 'admin_enqueue_scripts', function( $hook ) {
  // Debug: Log všech hook hodnot
  error_log( 'Admin hook: ' . $hook );
  
  // Zkontrolovat, zda jsme na správné admin stránce
  if ( strpos( $hook, 'quick-product-editor' ) === false ) {
    return;
  }
  
  // Enqueue jQuery (už je v WordPressu, ale explicito)
  wp_enqueue_script( 'jquery' );
  
  // Enqueue WordPress media picker skriptu a stylů
  wp_enqueue_media();
  
  // Enqueue vlastní CSS
  wp_enqueue_style(
    'qpe-style',
    get_template_directory_uri() . '/inc/quick-editor/quick-editor.css',
    [],
    '1.0.0'
  );
  
  // Enqueue vlastní JavaScript
  wp_enqueue_script(
    'qpe-script',
    get_template_directory_uri() . '/inc/quick-editor/quick-editor.js',
    ['jquery', 'wp-util'],
    '1.0.0',
    true
  );
  
  // Předání proměnných do JavaScriptu
  wp_localize_script(
    'qpe-script',
    'qpeData',
    [
      'ajaxUrl' => admin_url( 'admin-ajax.php' ),
      'nonce' => wp_create_nonce( 'qpe_nonce' ),
      'perPage' => 20,
    ]
  );
} );

// ==========================================
// INICIALIZACE TÉMATU
// ==========================================

add_action('after_setup_theme', function () {
  // Podpora náhledových obrázků pro posty, stránky a produkty
  add_theme_support('post-thumbnails', ['post','page','produkt']);
  
  // Vlastní velikosti obrázků pro produkty
  add_image_size('product-main', 800, 800, false);  // Hlavní obrázek produktu (bez ořezu)
  add_image_size('product-thumb', 200, 133, true);  // Miniatura pro galerii (s ořezem)
  add_image_size('product-grid', 600, 400, true);   // Produkty v gridu (s ořezem)
  
  // Registrace navigačních menu
  register_nav_menus([
    'header-menu' => __('Menu v hlavičce (horní)', 'mytheme'),
    'main-menu'   => __('Hlavní navigace', 'mytheme'),
    'footer-menu' => __('Menu v patičce', 'mytheme'),
    'hp-kategorie' => __('Kategorie na HP', 'mytheme'),
    'hp-vlastni-produkce' => __('Vlastní produkce na HP', 'mytheme'),
  ]);
});

// ==========================================
// ACF OPTIONS PAGE
// ==========================================

// Registrace ACF options page pro globální nastavení
if (function_exists('acf_add_options_page')) {
  add_action('acf/init', function () {
    if (!acf_get_options_page('basic-settings')) {
      acf_add_options_page([
        'page_title' => 'Základní nastavení',
        'menu_title' => 'Základní nastavení',
        'menu_slug'  => 'basic-settings',
        'capability' => 'edit_posts',
        'redirect'   => false
      ]);
    }
  });
}

// ==========================================
// NAČTENÍ POMOCNÝCH SOUBORŮ
// ==========================================

require_once get_template_directory() . '/inc/footer-logos.php';           // Funkce pro zobrazení log v patičce
require_once get_template_directory() . '/inc/admin-ui.php';               // Úpravy admin rozhraní
require_once get_template_directory() . '/inc/post-types.php';             // Registrace vlastních post typů (produkt)
require_once get_template_directory() . '/inc/taxonomies.php';             // Registrace taxonomií (produkt_kategorie)
require_once get_template_directory() . '/inc/disable-comments.php';       // Vypnutí komentářů
require_once get_template_directory() . '/inc/instagram-import.php';       // Import z Instagramu
require_once get_template_directory() . '/inc/blocks-system.php';          // Univerzální systém bloků
require_once get_template_directory() . '/inc/media-folders.php';          // Organizace médií do kategorií
require_once get_template_directory() . '/inc/gallery-enhancements.php';   // Vylepšení WP galerie + lightbox
require_once get_template_directory() . '/inc/product-gallery.php';        // Vlastní galerie pro produkty
require_once get_template_directory() . '/inc/quick-editor/admin-page.php'; // Quick Product Editor - Admin stránka
require_once get_template_directory() . '/inc/quick-editor/ajax-handler.php'; // Quick Product Editor - AJAX handlery

// ==========================================
// URL REWRITE - PRODUKTY
// ==========================================

// Oprava URL pro CPT 'produkt' - použití '/produkt/slug/' místo '/produkty/slug/'
add_filter('post_type_link', function($post_link, $post) {
  if ($post->post_type === 'produkt') {
    return home_url('/produkt/' . $post->post_name . '/');
  }
  return $post_link;
}, 10, 2);

// ==========================================
// URL REWRITE - TAXONOMIE PRODUKTŮ
// ==========================================

// Odstranění base slug 'produkt-kategorie' a hierarchická URL struktura
// Výsledek: /ovoce-a-zelenina/korenova-zelenina/mrkev/
add_filter('term_link', function($url, $term, $taxonomy) {
  if ($taxonomy !== 'produkt_kategorie') {
    return $url;
  }
  
  // Získat předky termínu
  $ancestors = get_ancestors($term->term_id, 'produkt_kategorie');
  if ($ancestors) {
    $ancestors = array_reverse($ancestors);
    $slugs = [];
    
    // Sestavit cestu z předků
    foreach ($ancestors as $ancestor_id) {
      $anc = get_term($ancestor_id, 'produkt_kategorie');
      if (!is_wp_error($anc)) {
        $slugs[] = $anc->slug;
      }
    }
    $slugs[] = $term->slug;
    $path = implode('/', $slugs);
  } else {
    $path = $term->slug;
  }
  
  return home_url("/{$path}/");
}, 10, 3);

// ==========================================
// REWRITE RULES - KATEGORIE PRODUKTŮ
// ==========================================

add_action('init', function() {
  // Pravidla pro "Ovoce a zelenina"
  add_rewrite_rule('^ovoce-a-zelenina/([^/]+)/([^/]+)/?$', 'index.php?produkt_kategorie=$matches[2]', 'top'); // 3. úroveň
  add_rewrite_rule('^ovoce-a-zelenina/([^/]+)/?$', 'index.php?produkt_kategorie=$matches[1]', 'top');          // 2. úroveň
  add_rewrite_rule('^ovoce-a-zelenina/?$', 'index.php?produkt_kategorie=ovoce-a-zelenina', 'top');            // 1. úroveň
  
  // Pravidla pro "Ready to Eat"
  add_rewrite_rule('^ready-to-eat/([^/]+)/([^/]+)/?$', 'index.php?produkt_kategorie=$matches[2]', 'top');
  add_rewrite_rule('^ready-to-eat/([^/]+)/?$', 'index.php?produkt_kategorie=$matches[1]', 'top');
  add_rewrite_rule('^ready-to-eat/?$', 'index.php?produkt_kategorie=ready-to-eat', 'top');
  
  // Pravidla pro "Produktové řady"
  add_rewrite_rule('^produktove-rady/([^/]+)/([^/]+)/?$', 'index.php?produkt_kategorie=$matches[2]', 'top');
  add_rewrite_rule('^produktove-rady/([^/]+)/?$', 'index.php?produkt_kategorie=$matches[1]', 'top');
  add_rewrite_rule('^produktove-rady/?$', 'index.php?produkt_kategorie=produktove-rady', 'top');
});

// Kontrola existence termínu před aplikací rewrite (prevence 404)
add_action('parse_request', function($wp) {
  if (isset($wp->query_vars['produkt_kategorie']) && !is_admin()) {
    $slug = $wp->query_vars['produkt_kategorie'];
    $term = get_term_by('slug', $slug, 'produkt_kategorie');
    
    // Pokud termín neexistuje, resetuj query var a nech WP najít stránku
    if (!$term) {
      unset($wp->query_vars['produkt_kategorie']);
    }
  }
});

// ==========================================
// AJAX - NAČÍTÁNÍ PRODUKTŮ
// ==========================================

// Registrace skriptu pro AJAX "Načíst další"
add_action('wp_enqueue_scripts', function(){
  // Jediný skript pro produkty i blog!
  wp_enqueue_script('load-more', get_template_directory_uri().'/assets/js/load-more.js', ['jquery'], '1.0.5', true);
  
  // Předání AJAX URL do JavaScriptu
  wp_localize_script('load-more', 'mytheme', [
    'ajax_url' => admin_url('admin-ajax.php')
  ]);
});

/**
 * AJAX handler pro načítání dalších produktů
 */
function load_more_products() {
  $term_id = intval($_POST['term']);
  $paged   = intval($_POST['page']) + 1;
  
  $query = new WP_Query([
    'post_type'      => 'produkt',
    'orderby'        => 'title',
    'order'          => 'ASC',
    'tax_query'      => [[
      'taxonomy'         => 'produkt_kategorie',
      'field'            => 'term_id',
      'terms'            => $term_id,
      'include_children' => true,
    ]],
    'posts_per_page' => 15,
    'paged'          => $paged,
  ]);
  
  $products_html = '';
  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      
      $products_html .= sprintf('<li><a href="%s">%s%s</a></li>',
        get_permalink(),
        has_post_thumbnail() ? get_the_post_thumbnail(get_the_ID(),'medium',['class'=>'term-thumb']) : '',
        esc_html(get_the_title())
      );
    }
    wp_reset_postdata();
  }
  
  // Vrát JSON s produkty a informacemi o stránkování
  wp_send_json([
    'html' => $products_html,
    'paged' => $paged,
    'max_pages' => $query->max_num_pages
  ]);
}
add_action('wp_ajax_load_more_products', 'load_more_products');
add_action('wp_ajax_nopriv_load_more_products', 'load_more_products');

/**
 * AJAX handler pro načítání dalších blogových příspěvků
 */
function load_more_blog_posts() {
  $cat_id = intval($_POST['category']);
  $paged  = intval($_POST['page']) + 1;
  
  $query = new WP_Query([
    'post_type'      => 'post',
    'cat'            => $cat_id,
    'posts_per_page' => 15,
    'paged'          => $paged,
  ]);
  
  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      ?>
      <article id="post-<?php the_ID(); ?>" <?php post_class('blog-item'); ?>>
        <a href="<?php the_permalink(); ?>" class="blog-item__thumb">
          <?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>
        </a>
        <div class="blog-item__body">
          <h2 class="blog-item__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <div class="blog-item__meta">
            <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo get_the_date(); ?></time>
          </div>
          <div class="blog-item__excerpt"><?php the_excerpt(); ?></div>
        </div>
      </article>
      <?php
    }
    wp_reset_postdata();
  }
  wp_die();
}
add_action('wp_ajax_load_more_blog_posts', 'load_more_blog_posts');
add_action('wp_ajax_nopriv_load_more_blog_posts', 'load_more_blog_posts');

// ==========================================
// HELPER FUNKCE
// ==========================================

/**
 * Vykreslí strom kategorií produktů v postranním menu
 * Zobrazí root kategorii + její přímé potomky
 * Aktivní položky mají class="active"
 */
function render_prod_kat_tree() {
  global $term;
  $current_term = get_queried_object();
  
  // Najít root kategorii (nejhornější předek)
  $ancestors = get_ancestors($current_term->term_id, 'produkt_kategorie');
  if ($ancestors) {
    $root_term_id = end($ancestors);
    $root_term = get_term($root_term_id, 'produkt_kategorie');
  } else {
    $root_term = $current_term; // Aktuální termín je root
  }
  
  if (!$root_term || is_wp_error($root_term)) return;
  
  // Získat přímé potomky root kategorie
  $child_terms = get_terms([
    'taxonomy' => 'produkt_kategorie',
    'parent' => $root_term->term_id,
    'hide_empty' => false,
    'orderby' => 'name'
  ]);
  
  // Výpis stromu
  echo '<ul>';
  
  // Root kategorie
  $class = ($current_term->term_id == $root_term->term_id) ? ' class="active"' : '';
  printf('<li%s><a href="%s">%s</a>', $class, get_term_link($root_term), esc_html($root_term->name));
  
  // Podkategorie
  if ($child_terms) {
    echo '<ul>';
    foreach ($child_terms as $child) {
      $class = ($current_term->term_id == $child->term_id) ? ' class="active"' : '';
      printf('<li%s><a href="%s">%s</a></li>', $class, get_term_link($child), esc_html($child->name));
    }
    echo '</ul>';
  }
  
  echo '</li></ul>';
}

/**
 * Vykreslí vodorovné menu s podkategoriemi
 * Řadí se podle pozice v menu "Hlavní navigace"
 * Root kategorie (nadpoložka) + její podpoložky v menu
 * Aktivní položky mají class="active"
 */
function render_prod_kat_horizontal_menu() {
  $current_term = get_queried_object();
  
  // Najít root kategorii (nejhornější předek)
  $ancestors = get_ancestors($current_term->term_id, 'produkt_kategorie');
  if ($ancestors) {
    $root_term_id = end($ancestors);
    $root_term = get_term($root_term_id, 'produkt_kategorie');
  } else {
    $root_term = $current_term; // Aktuální termín je root
  }
  
  if (!$root_term || is_wp_error($root_term)) return;
  
  // Získat položky z menu "Hlavní navigace"
  $menu_name = 'main-menu';
  $locations = get_nav_menu_locations();
  $menu_id = isset($locations[$menu_name]) ? $locations[$menu_name] : false;
  
  $menu_items = [];
  $root_menu_item = null;
  
  if ($menu_id) {
    $items = wp_get_nav_menu_items($menu_id);
    
    // Najít menu item pro root kategorii a jeho podpoložky
    foreach ($items as $item) {
      // Kontrola, zda jde o taxonomy item pro produkt_kategorie
      if ($item->object === 'produkt_kategorie' && $item->object_id == $root_term->term_id) {
        $root_menu_item = $item;
        break;
      }
    }
    
    // Pokud jsme našli root item, najděme jeho podpoložky
    if ($root_menu_item) {
      foreach ($items as $item) {
        if ($item->menu_item_parent == $root_menu_item->ID && $item->object === 'produkt_kategorie') {
          $menu_items[] = $item;
        }
      }
    }
  }
  
  // Pokud menu items nejsou v menu, použij fallback na taxonomii
  if (empty($menu_items)) {
    $child_terms = get_terms([
      'taxonomy' => 'produkt_kategorie',
      'parent' => $root_term->term_id,
      'hide_empty' => false,
      'orderby' => 'name'
    ]);
    
    // Převést termy na formát kompatibilní s menu items
    foreach ($child_terms as $child) {
      $item = new stdClass();
      $item->object_id = $child->term_id;
      $item->title = $child->name;
      $item->url = get_term_link($child);
      $menu_items[] = $item;
    }
  }
  
  // Výpis vodorovného menu
  echo '<ul class="cat-menu">';
  
  // Root kategorie jako nadpoložka
  $class = ($current_term->term_id == $root_term->term_id) ? ' class="active"' : '';
  printf('<li%s><a href="%s">%s</a>', $class, get_term_link($root_term), esc_html($root_term->name));
  
  // Podpoložky z menu
  if (!empty($menu_items)) {
    echo '<ul>';
    foreach ($menu_items as $item) {
      $item_url = isset($item->url) ? $item->url : get_term_link($item->object_id, 'produkt_kategorie');
      $item_title = isset($item->title) ? $item->title : get_term($item->object_id, 'produkt_kategorie')->name;
      $class = ($current_term->term_id == $item->object_id) ? ' class="active"' : '';
      printf('<li%s><a href="%s">%s</a></li>', $class, esc_url($item_url), esc_html($item_title));
    }
    echo '</ul>';
  }
  
  echo '</li></ul>';
}

/**
 * Vykreslí breadcrumbs (drobečková navigace)
 * Podporuje: produkty, kategorie produktů, posts, pages, archivy
 * První položka je ikona domečku
 */
function render_breadcrumbs() {
  echo '<nav class="breadcrumbs" aria-label="Breadcrumbs">';
  echo '<ul>';
  
  // Domů - SVG ikona
  echo '<li class="breadcrumbs__home"><a href="' . home_url('/') . '" aria-label="Domů"><svg width="16" height="18" viewBox="0 0 16 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.5 17.1667V8.83333H10.5V17.1667M0.5 6.33333L8 0.5L15.5 6.33333V15.5C15.5 15.942 15.3244 16.366 15.0118 16.6785C14.6993 16.9911 14.2754 17.1667 13.8333 17.1667H2.16667C1.72464 17.1667 1.30072 16.9911 0.988155 16.6785C0.675595 16.366 0.5 15.942 0.5 15.5V6.33333Z" stroke="#1E1E1E" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>';
  
  // === SINGLE PRODUKT ===
  if (is_singular('produkt')) {
    $terms = get_the_terms(get_the_ID(), 'produkt_kategorie');
    if ($terms && !is_wp_error($terms)) {
      $term = array_shift($terms);
      
      // Vypsat předky kategorie
      if ($term->parent) {
        $ancestors = get_ancestors($term->term_id, 'produkt_kategorie');
        $ancestors = array_reverse($ancestors);
        foreach ($ancestors as $ancestor_id) {
          $ancestor = get_term($ancestor_id, 'produkt_kategorie');
          echo '<li><a href="' . get_term_link($ancestor) . '">' . esc_html($ancestor->name) . '</a></li>';
        }
      }
      
      // Kategorie produktu
      echo '<li><a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a></li>';
    }
    
    // Název produktu (aktivní)
    echo '<li aria-current="page">' . get_the_title() . '</li>';
    
  // === TAXONOMIE PRODUKT_KATEGORIE ===
  } elseif (is_tax('produkt_kategorie')) {
    $term = get_queried_object();
    
    // Vypsat předky
    if ($term->parent) {
      $ancestors = get_ancestors($term->term_id, 'produkt_kategorie');
      $ancestors = array_reverse($ancestors);
      foreach ($ancestors as $ancestor_id) {
        $ancestor = get_term($ancestor_id, 'produkt_kategorie');
        echo '<li><a href="' . get_term_link($ancestor) . '">' . esc_html($ancestor->name) . '</a></li>';
      }
    }
    
    // Aktuální kategorie
    echo '<li aria-current="page">' . esc_html($term->name) . '</li>';
    
  // === SINGLE POST ===
  } elseif (is_single()) {
    $categories = get_the_category();
    if ($categories) {
      $category = $categories[0];
      echo '<li><a href="' . get_category_link($category) . '">' . esc_html($category->name) . '</a></li>';
    }
    echo '<li aria-current="page">' . get_the_title() . '</li>';
    
  // === PAGE ===
  } elseif (is_page()) {
    // Rodiče stránky
    if (wp_get_post_parent_id(get_the_ID())) {
      $parent_id = wp_get_post_parent_id(get_the_ID());
      $breadcrumbs = [];
      
      while ($parent_id) {
        $page = get_post($parent_id);
        $breadcrumbs[] = '<li><a href="' . get_permalink($page) . '">' . get_the_title($page) . '</a></li>';
        $parent_id = $page->post_parent;
      }
      
      $breadcrumbs = array_reverse($breadcrumbs);
      foreach ($breadcrumbs as $crumb) {
        echo $crumb;
      }
    }
    echo '<li aria-current="page">' . get_the_title() . '</li>';
    
  // === KATEGORIE ===
  } elseif (is_category()) {
    $category = get_queried_object();
    echo '<li aria-current="page">' . esc_html($category->name) . '</li>';
    
  // === TAG ===
  } elseif (is_tag()) {
    $tag = get_queried_object();
    echo '<li aria-current="page">' . esc_html($tag->name) . '</li>';
    
  // === ARCHIV ===
  } elseif (is_archive()) {
    echo '<li aria-current="page">' . post_type_archive_title('', false) . '</li>';
    
  // === VYHLEDÁVÁNÍ ===
  } elseif (is_search()) {
    echo '<li aria-current="page">Výsledky vyhledávání: ' . get_search_query() . '</li>';
    
  // === 404 ===
  } elseif (is_404()) {
    echo '<li aria-current="page">404 - Stránka nenalezena</li>';
  }
  
  echo '</ul>';
  echo '</nav>';
}

// ==========================================
// ADMIN ÚPRAVY
// ==========================================

// Zajistit podporu featured image pro produkty (fallback)
add_action('admin_init', function() {
  add_post_type_support('produkt', 'thumbnail');
});

// ==========================================
// PODPORA SVG SOUBORŮ
// ==========================================

/**
 * Povolení SVG souborů v administraci
 * Přidá SVG MIME typ do seznamu povolených souborů
 */
add_filter('upload_mimes', function($mimes) {
  $mimes['svg'] = 'image/svg+xml';
  return $mimes;
});

/**
 * Sanitizace SVG souborů z bezpečnostních důvodů
 * Zajistí, aby SVG soubory neobsahovaly škodlivý kód
 */
add_filter('wp_handle_upload_prefilter', function($file) {
  if ($file['type'] === 'image/svg+xml') {
    // Kontrola, zda jde o SVG soubor
    if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'svg') {
      // Přečíst obsah souboru a ověřit strukturu
      $content = @file_get_contents($file['tmp_name']);
      if ($content === false) {
        $file['error'] = __('Nepodařilo se přečíst SVG soubor.', 'mytheme');
        return $file;
      }
      
      // Parsovat XML a kontrola bezpečnosti
      $xml = @simplexml_load_string($content);
      if ($xml === false) {
        $file['error'] = __('Nejedná se o validní SVG soubor.', 'mytheme');
        return $file;
      }
      
      // Kontrola na zakázané elementy (script, iframe, apod.)
      $dom = new DOMDocument();
      $dom->loadXML($content);
      
      $dangerous_elements = ['script', 'iframe', 'object', 'embed', 'link'];
      foreach ($dangerous_elements as $element) {
        $elements = $dom->getElementsByTagName($element);
        if ($elements->length > 0) {
          $file['error'] = __('SVG soubor obsahuje zakázané elementy.', 'mytheme');
          return $file;
        }
      }
      
      // Kontrola na event handlery (onclick, onload, apod.)
      $xpath = new DOMXPath($dom);
      $nodes = $xpath->query('//*[@*[starts-with(name(), "on")]]');
      if ($nodes->length > 0) {
        $file['error'] = __('SVG soubor obsahuje zakázané event handlery.', 'mytheme');
        return $file;
      }
    }
  }
  
  return $file;
});

/**
 * Povolení SVG obrázků v media library
 */
add_filter('wp_get_attachment_image_src', function($image, $attachment_id, $size, $icon) {
  $mime = get_post_mime_type($attachment_id);
  if ($mime === 'image/svg+xml') {
    return [wp_get_attachment_url($attachment_id), '', ''];
  }
  return $image;
}, 10, 4);

/**
 * Zobrazení SVG náhledů v media library
 */
add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
  $filetype = wp_check_filetype($filename, $mimes);
  if ($filetype['ext'] === 'svg' || pathinfo($filename, PATHINFO_EXTENSION) === 'svg') {
    $data['ext'] = 'svg';
    $data['type'] = 'image/svg+xml';
  }
  return $data;
}, 10, 4);