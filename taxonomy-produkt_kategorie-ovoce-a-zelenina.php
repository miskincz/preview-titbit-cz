<?php 
/**
 * Template: Taxonomy - Ovoce a zelenina
 * 
 * Šablona pro zobrazení kategorie "Ovoce a zelenina" a jejích podkategorií
 * Obsahuje: obrázek kategorie, popisek, navigaci a seznam produktů
 */

get_header(); 
  global $term;
  $term = get_queried_object();

?>
<main class="siteContainer">
  <?php 
  // Breadcrumbs navigace
  render_breadcrumbs(); 
  ?>
  
  <div class="mainOcCattegories__top">
    <!-- Obrázek kategorie -->
    <div class="mainOcCattegories__top__image">
      <?php
        // Načtení obrázku a popisku z ACF polí
        $image   = get_field('ockat_obrazek',  'produkt_kategorie_' . $term->term_id);
        $caption = get_field('ockat_popisek', 'produkt_kategorie_' . $term->term_id);
        if ($image) {
          
          
            $alt = '';
            if ( $id = attachment_url_to_postid($image) ) {
              $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
            }
            printf(
              '<img src="%s" alt="%s">',
              esc_url($image),
              esc_attr( $alt ?: $term->name )
            );
          
        }
      ?>
    </div><!-- .mainOcCattegories__top__image -->
    <!-- Textová část s nadpisem a popiskem -->
    <div class="mainOcCattegories__top__text"<?php
      // Načtení barev z ACF polí pro styling
      $bg_color = get_field('ockat_barva_pozadi', 'produkt_kategorie_' . $term->term_id);
      $text_color = get_field('ockat_barva_textu', 'produkt_kategorie_' . $term->term_id);
      if ($bg_color || $text_color) {
        echo ' style="';
        if ($bg_color) echo 'background-color: ' . esc_attr($bg_color) . ';';
        if ($text_color) echo 'color: ' . esc_attr($text_color) . ';';
        echo '"';
      }
    ?>>
      <?php
        $logo = get_field('ockat_logo', 'produkt_kategorie_' . $term->term_id);
        if ($logo) {
          printf('<img src="%s" alt="" class="mainOcCattegories__top__text__logo">', esc_url(is_array($logo) ? $logo['url'] : $logo));
        }
        // Pokud je termín druhé či další úrovně, vypiš nadřazenou kategorii jako H2
        if ( $term->parent ) {
          $ancestors = get_ancestors( $term->term_id, 'produkt_kategorie' );
          $top_id    = end( $ancestors );
          $top_term  = get_term( $top_id, 'produkt_kategorie' );
          if ( ! is_wp_error( $top_term ) ) {
            echo '<h2>' . esc_html( $top_term->name ) . '</h2>';
          }
        }
      ?>
      <h1><?php echo esc_html( $term->name ); ?></h1>
      <div class="mainOcCattegories__top__text__caption"><?php if ($caption) {echo wp_kses_post($caption);} ?></div>
    </div><!-- .mainOcCattegories__top__caption -->
  </div><!-- .mainOcCattegories__top -->

  <div class="mainOcCattegories__main">
    <!-- Horizontální navigace - podkategorie -->
    <nav class="mainOcCattegories__main__nav">
      <?php render_prod_kat_horizontal_menu(); ?>
    </nav>
    
    <!-- Seznam produktů v kategorii -->
    <div class="mainOcCattegories__main__list">
        <?php
        // Výpis produktů pouze z aktuálního termínu (a jeho podkategorií)
        $paged = get_query_var('paged',1);
        
        $paged = get_query_var('paged',1);
        
        $query = new WP_Query([
          'post_type'      => 'produkt',
          'orderby'        => 'title',
          'order'          => 'ASC',
          'tax_query'      => [[
            'taxonomy'         => 'produkt_kategorie',
            'field'            => 'term_id',
            'terms'            => $term->term_id,
            'include_children' => true,
          ]],
          'posts_per_page' => 15,
          'paged'          => $paged,
        ]);
        
        if ($query->have_posts()) {
          echo '<div id="product-list" data-term="'. $term->term_id .'" data-page="'. $paged .'">';
          echo '<ul>';
          while ($query->have_posts()) { $query->the_post();
            printf('<li><a href="%s">%s%s</a></li>',
              get_permalink(),
              has_post_thumbnail() ? get_the_post_thumbnail(get_the_ID(),'medium',['class'=>'term-thumb']) : '',
              esc_html(get_the_title())
            );
          }
          echo '</ul>';
          echo '</div>';
      ?>

      <div class="pagePagination">
        <?php  
          // Stránkování
          // Načíst další tlačítko
          if($query->max_num_pages > 1 && $paged < $query->max_num_pages) {
            echo '<div class="pagePagination__loadMore">';
            echo '<button id="load-more" class="btn" data-max-page="'. $query->max_num_pages .'">Načíst další</button>';
            echo '</div>';
          }

          if($query->max_num_pages > 1) {
            $current_url = get_term_link($term);
            $max_pages = $query->max_num_pages;
            $pages_to_show = 5; // Vždy 5 prostředních stran
            $range = floor($pages_to_show / 2); // 2
            
            // Dynamicky urči rozsah tak, aby bylo vždy 5 stran kolem aktuální
            $start = max(1, $paged - $range);
            $end = min($max_pages, $start + $pages_to_show - 1);
            
            // Pokud je end blízko maxu, posun start doleva
            if ($end - $start + 1 < $pages_to_show) {
              $start = max(1, $end - $pages_to_show + 1);
            }
            
            echo '<nav class="pagination" aria-label="Stránkování produktů">';
            
            // Čísla stran
            $last_printed = 0;
            for ($i = 1; $i <= $max_pages; $i++) {
              // Vždy zobrazuj: první, poslední, nebo v rozsahu
              if (
                $i == 1 || 
                $i == $max_pages || 
                ($i >= $start && $i <= $end)
              ) {
                // Přidej tři tečky pokud je mezera
                if ($i > $last_printed + 1 && $last_printed > 0) {
                  echo '<span class="pagination__dots">...</span>';
                }
                
                $page_url = add_query_arg('paged', $i, $current_url);
                $is_current = ($i === $paged);
                $active_class = $is_current ? ' pagination__link--active' : '';
                $aria_current = $is_current ? ' aria-current="page"' : '';
                printf(
                  '<a href="%s" class="pagination__link%s"%s>%d</a>',
                  esc_url($page_url),
                  $active_class,
                  $aria_current,
                  $i
                );
                $last_printed = $i;
              }
            }
            
            echo '</nav>';
          }
          
          
          
          wp_reset_postdata();
        } else {
          echo '<p>Žádné produkty v této kategorii ani jejích podkategoriích.</p>';
        }
        ?>
      </div>

  </div><!-- .mainOcCattegories__main__list -->
</div><!-- .mainOcCattegories__main -->



</main>
<?php get_footer(); ?>