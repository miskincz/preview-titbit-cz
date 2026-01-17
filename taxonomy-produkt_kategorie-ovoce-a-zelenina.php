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
    <!-- Boční navigace - strom kategorií -->
    <nav class="mainOcCattegories__main__nav">
      <?php render_prod_kat_tree(); ?>
    </nav>
    
    <!-- Seznam produktů v kategorii -->
    <div class="mainOcCattegories__main__list">
        <?php
        // Výpis produktů pouze z aktuálního termínu (a jeho podkategorií)
        $paged = get_query_var('paged',1);
        
        $query = new WP_Query([
          'post_type'      => 'produkt',
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
          echo '<div id="product-list" data-term="'. $term->term_id .'" data-page="1">';
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
          
          if($query->max_num_pages > 1 && $paged < $query->max_num_pages) {
            echo '<div class="articlePage">';
            echo '<button id="load-more" class="btn" data-max-page="'. $query->max_num_pages .'">Načíst další</button>';
            echo '</div>';
          }
          
          wp_reset_postdata();
        } else {
          echo '<p>Žádné produkty v této kategorii ani jejích podkategoriích.</p>';
        }
        ?>

  </div><!-- .mainOcCattegories__main__list -->
</div><!-- .mainOcCattegories__main -->



</main>
<?php get_footer(); ?>