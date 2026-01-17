<?php
/**
 * Template Part: Produkt Content (základní)
 * 
 * Jednoduchá šablona pro zobrazení produktu
 * Pro pokročilejší verzi viz content-produkt-ovoce.php
 */
?>
aa

<article id="post-<?php the_ID(); ?>" <?php post_class('single-produkt-entry'); ?>>
  <?php if (has_post_thumbnail()) : ?>
    <div class="single-prod-thumb"><?php the_post_thumbnail('large'); ?></div>
  <?php endif; ?>


  <h1 class="single-prod-title"><?php the_title(); ?></h1>
  <div class="single-prod-content"><?php the_content(); ?></div>
</article>