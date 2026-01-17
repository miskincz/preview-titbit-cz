<?php
/**
 * Template Part: Recept (single)
 * 
 * Šablona pro zobrazení jednotlivého receptu
 * Obsahuje: obrázek, autor, počet porcí, ingredience, postup
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class('single-recept'); ?>>
  <div class="recepty">
    <div class="recepty__left">
      <?php if (has_post_thumbnail()) : ?>
        <div class="single-thumb">
          <?php the_post_thumbnail('large'); ?>
        </div>
      <?php endif; ?>
      
      <div class="single-meta">
        <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
          <?php echo get_the_date(); ?>
        </time>
        <span class="single-cats"><?php the_category(', '); ?></span>
      </div>
    </div>
    
    <div class="recepty__right">
      <h1 class="single-title"><?php the_title(); ?></h1>
      
      <?php
      // Načtení ACF polí receptu
      $autor = get_field('recepty_autor');
      $porce = get_field('recepty_porce');
      $ingredience = get_field('recepty_ingredience');
      $postup = get_field('recepty_pracovnipostup');
      if (empty($postup)) {
        $postup = get_field('recepty_pracovni_postup');
      }
      ?>
      
      <?php if ($autor || $porce || $ingredience || $postup) : ?>
        <section class="recept-info">
          <ul class="recept-info__basic">
            <?php if ($autor) : ?>
              <li><strong>Autor:</strong> <?php echo esc_html($autor); ?></li>
            <?php endif; ?>
            <?php if ($porce) : ?>
              <li><strong>Počet porcí:</strong> <?php echo esc_html($porce); ?></li>
            <?php endif; ?>
          </ul>
          
          <div class="recept-info__cols">
            <?php if ($ingredience) : ?>
              <div class="recept-info__col recept-info__ingredience">
                <h2>Ingredience</h2>
                <div class="recept-ingredience">
                  <?php echo wp_kses_post($ingredience); ?>
                </div>
              </div>
            <?php endif; ?>
            
            <?php if ($postup) : ?>
              <div class="recept-info__col recept-info__postup">
                <h2>Postup</h2>
                <div class="recept-postup">
                  <?php echo wp_kses_post($postup); ?>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </section>
      <?php endif; ?>

      <div class="single-content">
        <?php the_content(); ?>
      </div>
    </div>
  </div>
</article>