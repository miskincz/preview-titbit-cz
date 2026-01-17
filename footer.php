<?php
/**
 * Template: Footer
 * 
 * Hlavní patička webu obsahující:
 * - Sekce dodavatelů a partnerů (loga)
 * - 5 sloupců s informacemi (logo, text, kontakt, menu, e-shop)
 * - Copyright informace
 */
?>
<div class="sectionDodavame">
  <div class="siteContainer">
    <h4>Dodáváme do obchodních řetězců, restaurací a distribucí</h4>
    <?php 
    // Zobrazení log z ACF pole na stránce nastavení
    if (function_exists('mytheme_get_footer_logos')): 
      $logos = mytheme_get_footer_logos(); 
      if ($logos): 
    ?>
      <ul>
        <?php foreach ($logos as $id): ?>
          <li><?php echo wp_get_attachment_image($id, 'medium'); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; endif; ?>
  </div>
</div><!-- .sectionDodavame -->

<footer class="footer">
  <div class="footer__content">
    <div class="siteContainer">
      <div class="footer__container">
        <!-- Sloupec 1: Logo firmy -->
        <div class="footer__container__col1">
          <?php
            // Načtení obrázku z ACF pole na stránce ID 9 (nastavení)
            $img = get_field('footer_image', 9);
            if ($img) {
              if (is_numeric($img)) { // doporučeno nastavit v ACF návrat: ID
                echo wp_get_attachment_image($img, 'full', false, ['class' => 'footer-image']);
              } else { // URL nebo array
                $url = is_array($img) ? ($img['url'] ?? '') : $img;
                if ($url) {
                  $alt = '';
                  if ($id = attachment_url_to_postid($url)) {
                    $alt = get_post_meta($id, '_wp_attachment_image_alt', true);
                  }
                  printf('<img class="footer-image" src="%s" alt="%s">', esc_url($url), esc_attr($alt ?: get_bloginfo('name')));
                }
              }
            }
          ?>
        </div><!-- .footer__container__col1 -->

        <!-- Sloupec 2: Popisný text -->
        <div class="footer__container__col2">
          <?php if ($t = get_field('footer_text', 9)) : ?>
            <div class="footer-text"><?php echo nl2br( esc_html($t) ); ?></div>
          <?php endif; ?>
        </div><!-- .footer__container__col2 --> 
        
        <!-- Sloupec 3: Kontaktní informace -->
        <div class="footer__container__col3">
          <h4>Kde nás nahdete?</h4>
          <?php if ($t = get_field('footer_kdenasnajedete', 9)) : ?>
            <div class="footer-text"><?php echo nl2br( esc_html($t) ); ?></div>
          <?php endif; ?>
        </div><!-- .footer__container__col3 -->
        
        <!-- Sloupec 4: Menu O Titbitu -->
        <div class="footer__container__col4">
          <h4>O Titbitu</h4>
          <?php wp_nav_menu(['theme_location'=>'footer-menu']).' '; ?>
        </div><!-- .footer__container__col4 -->
        
        <!-- Sloupec 5: E-shop informace -->
        <div class="footer__container__col5">
          <h4>E-shop</h4>
          <?php if ($t = get_field('footer_eshop', 9)) : ?>
            <div class="footer-text"><?php echo nl2br( esc_html($t) ); ?></div>
          <?php endif; ?>
        </div><!-- .footer__container__col5 -->
      </div><!-- .footer__container -->
    </div><!-- .siteContainer -->
  </div>

  <!-- Copyright sekce -->
  <div class="footer__copyright">
    <div class="siteContainer">
      &copy; <?php echo date("Y"); ?> <?php bloginfo('name'); ?>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
