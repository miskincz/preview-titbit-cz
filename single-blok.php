<?php get_header(); ?>

<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
  <main class="siteContainer blok-content">
    
    <?php
    // Zobrazit ACF flexible content
    if (have_rows('blok_obsah')) :
      while (have_rows('blok_obsah')) : the_row();
        $layout = get_row_layout();
        
        // Načíst template part pro každý layout
        get_template_part('template-parts/blok', $layout);
        
      endwhile;
    else :
      // Fallback na klasický obsah
      the_content();
    endif;
    ?>
    
  </main>
<?php endwhile; endif; ?>

<?php get_footer(); ?>
