<?php
/**
 * Template: Single Post
 * 
 * Šablona pro zobrazení jednotlivého příspěvku
 * Rozlišuje mezi recepty (kategorie 'recepty') a standardními články
 */

get_header(); ?>
<main class="single-post container">
  <?php 
    // Zobrazení breadcrumbs (děličková navigace)
    render_breadcrumbs(); 
  ?>
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post();
    // Zobrazení receptu pokud je příspěvek v kategorii 'recepty'
    if ( in_category('recepty') ) {
      get_template_part('template-parts/content','recepty');
    } elseif ( in_category('clanky') ) { 
      get_template_part('template-parts/content','blog');
    } else {
      // Standardní zobrazení článku
      get_template_part('template-parts/content','standard');
    }
  endwhile; endif; ?>
</main>
<?php get_footer(); ?>