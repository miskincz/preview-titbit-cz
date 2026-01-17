<?php
/*
Template Name: Blog – Články
Description: Vypíše pouze příspěvky z kategorie "Články".
*/
get_header();
?>
<main class="siteContainer">
  <?php 
  // Breadcrumbs navigace
  render_breadcrumbs(); 
  ?>

    <!-- Hlavní obsah -->
    <div class="blogList">
      <?php      
      // Ostatní články
      $q = new WP_Query([
        'post_type' => 'post',
        'category_name' => 'clanky',
        'posts_per_page' => 9,
        'offset' => 1,
        'paged' => 1,
      ]);
      
      if ($q->have_posts()): ?>
        <div data-category="<?php echo get_category_by_slug('clanky')->term_id; ?>" data-page="1" class="blogList__list">
          <?php while ($q->have_posts()): $q->the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('blog-card'); ?>>
              <?php if (has_post_thumbnail()) : ?>
                <a href="<?php the_permalink(); ?>" class="blog-card__image">
                  <?php the_post_thumbnail('medium'); ?>
                </a>
              <?php endif; ?>
              <div class="blog-card__content">
                <h3 class="blog-card__title">
                  <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
              </div>
            </article>
          <?php endwhile; ?>
              </article>
        
        <?php if ($q->max_num_pages > 1): ?>
          <div class="articlePage">
            <button id="load-more-blog" data-max-page="<?php echo $q->max_num_pages; ?>">Načíst další</button>
          </div>
        <?php endif; ?>
     </div>   
      <?php else: ?>
        <p>Žádné články nenalezeny.</p>
      <?php endif; wp_reset_postdata(); ?>
    

   
  </div>
</main>
<?php get_footer(); ?>