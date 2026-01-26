<?php 
/**
 * Template: Taxonomy - Produktové řady
 * 
 * Šablona pro zobrazení kategorie "Produktové řady" a jejích podkategorií
 * - Hlavní kategorie (bez parenta): zobrazí úvodní sekci s obrázkem
 * - Podkategorie (s parentem): zobrazí zjednodušené zobrazení bez úvodní sekce
 */

get_header(); 
  global $term;
  $term = get_queried_object();
  
  // Zjistit, zda je toto hlavní kategorie nebo podkategorie
  $is_subcategory = ! empty( $term->parent );

?>
<main class="siteContainer">
  <?php render_breadcrumbs(); ?>
  

  <div class="mainOcCattegories__top">
    <div class="mainOcCattegories__top__image">
      <?php
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
    <div class="mainOcCattegories__top__text"<?php
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
          printf('<img src="%s" alt="" class="index__categories__logo">', esc_url(is_array($logo) ? $logo['url'] : $logo));
        }
      ?>
      <h1><?php echo esc_html( $term->name ); ?></h1>
      <div class="mainOcCattegories__top__text__caption"><?php if ($caption) {echo wp_kses_post($caption);} ?></div>
    </div><!-- .mainOcCattegories__top__caption -->
  </div><!-- .mainOcCattegories__top -->

  <?php if ( $is_subcategory ) : // Navigace jen pro podkategorie ?>
  <div class="mainOcCattegories__main">
    
    <nav class="mainOcCattegories__main__nav">
      <?php render_prod_kat_tree(); ?>
    </nav>
    
    
    <div class="mainOcCattegories__main__list">
        <?php
        // Výpis produktů pouze z aktuálního termínu (a jeho podkategorií)
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
          
          // Stránkování
          if($query->max_num_pages > 1) {
            $current_url = get_term_link($term);
            $max_pages = $query->max_num_pages;
            $pages_to_show = 5;
            $range = floor($pages_to_show / 2);
            
            $start = max(1, $paged - $range);
            $end = min($max_pages, $start + $pages_to_show - 1);
            
            if ($end - $start + 1 < $pages_to_show) {
              $start = max(1, $end - $pages_to_show + 1);
            }
            
            echo '<nav class="pagination" aria-label="Stránkování produktů">';
            
            $last_printed = 0;
            for ($i = 1; $i <= $max_pages; $i++) {
              if (
                $i == 1 || 
                $i == $max_pages || 
                ($i >= $start && $i <= $end)
              ) {
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
<?php endif; ?>

<?php if ( ! $is_subcategory ) : // Bloky jen pro hlavní kategorie ?>

  <?php
    // Načtení podkategorií aktuální kategorie
    $subcategories = get_terms([
      'taxonomy' => 'produkt_kategorie',
      'parent' => $term->term_id,
      'hide_empty' => false,
      'orderby' => 'name',
      'exclude' => get_term_by('slug', 'uni', 'produkt_kategorie')->term_id, // Vyloučit kategorii 'uni'
    ]);
    
    if ($subcategories && ! is_wp_error($subcategories)) {
      echo '<ul class="index__categories">';
      foreach ($subcategories as $subcat) {
        $bg_style = '';
        
        // Načtení obrázku z ACF pole kategorie
        $image = get_field('ockat_hp_obrazek', 'produkt_kategorie_' . $subcat->term_id);
        if ($image) {
          $bg_style = ' style="background-image: url(' . esc_url($image) . ')"';
        }

        // Výpis podkategorie s obrázkem na pozadí
        echo '<li' . $bg_style . '>';
        printf('<a href="%s">', get_term_link($subcat));
        
        // Načtení a výpis loga
        $logo = get_field('ockat_logo', 'produkt_kategorie_' . $subcat->term_id);
        if ($logo) {
          printf('<img src="%s" alt="" class="index__categories__logo2">', esc_url(is_array($logo) ? $logo['url'] : $logo));
        }
        
        printf('<span class="btn btn--block">%s</span>', esc_html($subcat->name));
        echo '</a>';
        echo '</li>';
      }
      echo '</ul>';
    }
    ?>
<div class="grid grid--col-2 grid--full-height">
  <div>
    <?php display_block(11796, 'spoluprace'); ?>
  </div>
  <div>
    <?php display_block(11797, 'zasobujemerestaurace'); ?>
  </div>
</div>
<?php endif; ?>

<?php if ( ! $is_subcategory ) : // Bloky jen pro hlavní kategorie ?>

<?php endif; ?>


</main>
<?php get_footer(); ?>