<?php
/**
 * Template: Single Produkt
 * 
 * Šablona pro zobrazení detailu jednoho produktu
 * Zobrazuje breadcrumbs a načítá obsah z content-produkt-ovoce.php
 */

get_header(); ?>
<main class="siteContainer">
  <?php 
  // Zobrazení breadcrumbs (děličková navigace)
  render_breadcrumbs(); 
  ?>
  
  <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <?php 
    // Načtení šablony produktu
    get_template_part('template-parts/content', 'produkt-ovoce'); 
    ?>
  <?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>