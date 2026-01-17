<?php
/**
 * Template Part: Standard Post
 * 
 * Základní šablona pro standardní příspěvek (ne recept)
 * Zobrazuje náhledový obrázek, nadpis, datum, obsah a štítky
 */
?>

Standart
<article id="post-<?php the_ID(); ?>" <?php post_class('single-standard'); ?>>
  <header class="single-header">
    <?php if ( has_post_thumbnail() ) : ?>
      <div class="single-thumb"><?php the_post_thumbnail('large'); ?></div>
    <?php endif; ?>
    <h1 class="single-title"><?php the_title(); ?></h1>
    <div class="single-meta">
      <time datetime="<?php echo esc_attr( get_the_date('c') ); ?>"><?php echo get_the_date(); ?></time>
      <span class="single-cats"><?php the_category(', '); ?></span>
    </div>
  </header>
  <div class="single-content">
    <?php the_content(); ?>
  </div>
  <footer class="single-footer">
    <?php the_tags('<div class="single-tags"><strong>Štítky:</strong> ',', ','</div>'); ?>
  </footer>
</article>