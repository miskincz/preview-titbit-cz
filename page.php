<?php
/**
 * Template: Page (Základní stránka)
 * 
 * Obecná šablona pro zobrazení statických stránek
 * Načítá obsah z template-parts/content-page.php
 */

get_header(); ?>
<main class="siteContainer">
  <?php if ( have_posts() ) : while ( have_posts() ) : the_post();
    // Načtení obsahu stránky z template part
    get_template_part('template-parts/content','page');
    
    // Komentáře pokud jsou povoleny (běžně vypnuté)
    if ( comments_open() || get_comments_number() ) {
      comments_template();
    }
  endwhile; endif; ?>
</main>
<?php get_footer(); ?>