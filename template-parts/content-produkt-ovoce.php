<?php
/**
 * Template Part: Produkt - Ovoce a zelenina (detailní zobrazení)
 * 
 * Pokročilá šablona pro zobrazení produktu s kompletními informacemi:
 * - Hlavní obrázek produktu s BIO značkou
 * - Fotogalerie s lightboxem
 * - Základní informace (latinský název, charakteristika)
 * - Produktové řady (ikony kategorií)
 * - Balení a dostupnost v měsících
 * - Kde koupit (loga obchodů a e-shopů)
 * - Související produkty
 * - Dynamické záložky (popis, využití, nutriční hodnoty)
 * - Související články a recepty
 * - JavaScript pro ovládání záložek
 */
?>
<article <?php post_class('produktDetail'); ?>>
  <div class="produktDetail__main">
    <!-- Hlavní obrázek produktu -->
    <div class="produktDetail__image">
      <?php if ( has_post_thumbnail() ) : ?>
        <?php the_post_thumbnail('large'); ?>
      <?php endif; ?>

      <!-- Fotogalerie produktu -->
      <?php 
      // Načtení galerie z meta pole
      $galerie_ids = get_post_meta(get_the_ID(), '_product_gallery', true);
      $galerie = $galerie_ids ? explode(',', $galerie_ids) : [];
       if ( $galerie && is_array($galerie) ) : ?>
        <div class="produktDetail__gallery">          
          <div class="gallery-grid">
            <?php foreach ( $galerie as $foto_id ) : 
              $foto_id = trim($foto_id);
              if (!$foto_id) continue;
              $full_image = wp_get_attachment_image_url($foto_id, 'full');
            ?>
              <!-- Odkaz na plnou velikost obrázku pro lightbox -->
              <a href="<?php echo esc_url($full_image); ?>" 
                 class="gallery-item"
                 data-lightbox="gallery">
                <?php echo wp_get_attachment_image( $foto_id, 'product-thumb' ); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div><!-- .produktDetail__image -->

    <div class="produktDetail__info">
      <h1 class="title--h1"><?php the_title(); ?></h1>

      <!-- Latinský název -->
      <?php if ( $latinsky = get_field('produkty_latinsky_nazev') ) : ?>
        <div class="produktDetail__latin"><?php echo esc_html( $latinsky ); ?></div>
      <?php endif; ?>

      <!-- Další názvy -->
      <!-- <?php if ( $dalsi = get_field('produkty_dalsi_nazvy') ) : ?>
        <div class="produktDetail__altNames"><?php echo esc_html( $dalsi ); ?></div>
      <?php endif; ?> -->

      <!-- Perex - Hlavní obsah -->
      <?php if ( get_the_content() ) : ?>
        <div class="produktDetail__content-intro"><?php the_content(); ?></div>
      <?php endif; ?>
      
      <!-- Zkrácený popis s odkazem -->
      <?php 
      $charakteristika = get_field('produkty_charakteristika');
      if ( $charakteristika ) : 
        $short_text = mb_substr( strip_tags($charakteristika), 0, 150 );
      ?>
        <div class="produktDetail__short-desc">
          <?php echo esc_html($short_text); ?><?php if (mb_strlen(strip_tags($charakteristika)) > 100) : ?>...<?php endif; ?>
          <a href="#tab-desc" class="more-link">Popis</a>
        </div>
      <?php endif; ?>
      

      <!-- Produktová řada: kategorie produktu -->
      <?php
      $cats = get_the_terms( get_the_ID(), 'produkt_kategorie' );
      $exclude_parents = ['produktove-rady', 'ready-to-eat', 'ovoce-a-zelenina'];
      $ovoce_term = get_term_by('slug', 'ovoce-a-zelenina', 'produkt_kategorie');
      $ovoce_id = $ovoce_term ? $ovoce_term->term_id : 0;
      
      if ( $cats && ! is_wp_error( $cats ) ) {
        $badges = [];
        foreach ( $cats as $cat ) {
          // přeskočit root kategorie
          if ( in_array( $cat->slug, $exclude_parents, true ) ) {
            continue;
          }
          // přeskočit přímé potomky "ovoce-a-zelenina"
          if ( $ovoce_id && $cat->parent === $ovoce_id ) {
            continue;
          }
          
          // Ikona pro produktovou řadu
          $icon_slug = sanitize_title($cat->name);
          $icon_path = get_template_directory_uri() . '/assets/img/produktove-rady/' . $icon_slug . '.png';
          
          // Zkontrolovat zda soubor existuje
          $icon_file = get_template_directory() . '/assets/img/produktove-rady/' . $icon_slug . '.png';
          $has_icon = file_exists($icon_file);
          
          if ($has_icon) {
            // Jen ikona, bez textu
            $badges[] = sprintf(
              '<span class="badge"><a href="%s"><img src="%s" alt="%s" class="badge-icon"></a></span>',
              esc_url( get_term_link( $cat ) ),
              esc_url($icon_path),
              esc_attr($cat->name)
            );
          } else {
            // Jen text, bez ikony
            $badges[] = sprintf(
              '<span class="badge"><a href="%s">%s</a></span>',
              esc_url( get_term_link( $cat ) ),
              esc_html( $cat->name )
            );
          }
        }
        
        if ( $badges ) {
          echo '<h4>Produktová řada</h4>';
          echo '<div class="produktDetail__badges">' . implode( '', $badges ) . '</div>';
        }
      }
      ?>

      
      <?php 
      $baleni = get_field('produkty_baleni');
      if ( $baleni && is_array($baleni) ) : 
      ?>
        <div class="produktDetail__baleni">
          <h4>Balení</h4>
          <ul>
            <?php foreach ( $baleni as $baleni_item ) : ?>
              <li><?php echo esc_html( $baleni_item ); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php 
      $dostupnost_text = get_field('produkty_dostupnost');
      if ( $dostupnost_text ) :
      ?>
        <div class="produktDetail__dostupnost">
          <h4>Dostupnost v měsících</h4>
          <div class="produktDetail__dostupnost__main">
            <ul class="produktDetail__dostupnost__mesice">
              <?php
              // Rozdělit čísla měsíců (1, 2, 3, 12)
              $dostupne_mesice = $dostupnost_text ? 
                array_map('trim', explode(',', $dostupnost_text)) : [];
              
              for ($cislo = 1; $cislo <= 12; $cislo++) {
                $je_dostupny = in_array($cislo, $dostupne_mesice);
                $class = $je_dostupny ? 'is-available' : '';
                printf('<li class="%s">%s</li>', esc_attr($class), $cislo);
              }
              ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <!-- Kde koupit -->
      <?php 
      $loga_retezcu = get_field('produkty_loga-retezcu');
      if ( $loga_retezcu && is_array($loga_retezcu) ) : 
      ?>
        <div class="produktDetail__section">
          <h4>Koupíte v obchodech</h4>
          <div class="produktDetail__whereToBuy">
            <ul class="produktDetail__whereToBuy__logos">
              <?php foreach ( $loga_retezcu as $logo_nazev ) : 
                // Vytvoříme slug z názvu (např. "Košík" -> "kosik")
                $slug = sanitize_title($logo_nazev);
                $logo_path = get_template_directory_uri() . '/assets/img/obchody/logo_' . $slug . '.png';
              ?>
                <li>
                  <img src="<?php echo esc_url($logo_path); ?>" 
                       alt="<?php echo esc_attr($logo_nazev); ?>" 
                       title="<?php echo esc_attr($logo_nazev); ?>">
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

       <?php 
      $eshopy = get_field('produkty_eshopy');
      if ( $eshopy && is_array($eshopy) ) : 
      ?>
        <div class="produktDetail__section">
          <h4>koupíte v e-shopech</h4>
          <div class="produktDetail__whereToBuy">
            <ul class="produktDetail__whereToBuy__logos">
              <?php foreach ( $eshopy as $eshop_nazev ) : 
                $slug = sanitize_title($eshop_nazev);
                $logo_path = get_template_directory_uri() . '/assets/img/icons/logo_' . $slug . '.png';
              ?>
                <li>
                  <img src="<?php echo esc_url($logo_path); ?>" 
                       alt="<?php echo esc_attr($eshop_nazev); ?>" 
                       title="<?php echo esc_attr($eshop_nazev); ?>">
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>
    </div><!-- .produktDetail__info -->
  </div><!-- .produktDetail__main -->

  <?php
  // Zpracováváme v produktech - ACF Relationship field
  $souvisejici = get_field('produkty_souvisejici');
  if ( $souvisejici && is_array($souvisejici) ) :
  ?>
    <div class="produktDetail__related">
      <h4>Zpracováváme v produktech</h4>
      <div class="produktDetail__related__main">
        <ul>
          <?php foreach ( $souvisejici as $product ) : ?>
            <li>
              <a href="<?php echo get_permalink($product->ID); ?>">
                <?php if ( has_post_thumbnail($product->ID) ) : ?>
                  <?php echo get_the_post_thumbnail($product->ID, 'thumbnail'); ?>
                <?php endif; ?>
                <h3><?php echo esc_html(get_the_title($product->ID)); ?></h3>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>
  
  <!-- Lepší varianta s container -->
  
    <div class="grid grid--col-2">
      <div>
        <?php display_spoluprace(11796, 'spoluprace'); ?>
      </div>
      <div>
        <?php display_spoluprace(11797, 'zasobujemerestaurace'); ?>
      </div>
    </div>
  
  
  
  <?php
  // Dynamické záložky: vytvoříme pole dostupných sekcí podle ACF
  $tabs = [];
  $charakteristika = get_field('produkty_charakteristika');
  $vyuziti = get_field('produkty_vyuziti');
  $nutricni = get_field('produkty_nutricni_hodnoty');
  $vyzkousej = get_field('produkty_muzete_vyzkousei');

  if ( $charakteristika ) {
    $tabs[] = [ 'key' => 'desc', 'label' => 'Popis produktu', 'content' => $charakteristika ];
  } else {
    ob_start(); the_content(); $c = ob_get_clean();
    if ( trim( strip_tags( $c ) ) ) {
      $tabs[] = [ 'key' => 'desc', 'label' => 'Popis produktu', 'content' => $c ];
    }
  }
  if ( $vyuziti )   $tabs[] = [ 'key' => 'usage', 'label' => 'Využití, konzumace', 'content' => $vyuziti ];
  if ( $nutricni )  $tabs[] = [ 'key' => 'nutri',  'label' => 'Nutriční hodnoty, původ, zajímavosti', 'content' => $nutricni ];
  if ( $vyzkousej ) $tabs[] = [ 'key' => 'try',   'label' => 'Můžete vyzkoušet', 'content' => $vyzkousej ];

  if ( ! empty( $tabs ) ) :
  ?>
    <div class="produktDetail__sections" data-tabs>
      <nav class="produktDetail__nav" role="tablist" aria-label="Produktové informace">
        <?php foreach ( $tabs as $i => $t ) :
          $active = ( $i === 0 ) ? ' is-active' : '';
          $sel = ( $i === 0 ) ? 'true' : 'false';
        ?>
          <a href="#tab-<?php echo esc_attr( $t['key'] ); ?>"
             role="tab"
             aria-selected="<?php echo $sel; ?>"
             aria-controls="tab-<?php echo esc_attr( $t['key'] ); ?>"
             id="tab-btn-<?php echo esc_attr( $t['key'] ); ?>"
             data-tab="tab-<?php echo esc_attr( $t['key'] ); ?>"
             class="produktDetail__nav__btn<?php echo $active; ?>">
            <?php echo esc_html( $t['label'] ); ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="produktDetail__nav__panels">
        <?php foreach ( $tabs as $i => $t ) :
          $active = ( $i === 0 ) ? ' is-active' : '';
          $hidden = ( $i === 0 ) ? '' : ' hidden';
        ?>
          <section id="tab-<?php echo esc_attr( $t['key'] ); ?>"
                   role="tabpanel"
                   aria-labelledby="tab-btn-<?php echo esc_attr( $t['key'] ); ?>"
                   class="produktDetail__nav__panel<?php echo $active; ?>"
                   <?php echo $hidden ? 'hidden' : ''; ?>>
            <?php echo wpautop( $t['content'] ); ?>
          </section>
        <?php endforeach; ?>
      </div>
    </div>

    <?php endif; ?>

  <?php
  // Získat slug produktu pro hledání tagů
  $slug = get_post_field('post_name', get_the_ID());
  
  // Články s tagem produktu
  $argsClanky = [
    'post_type' => 'post',
    'posts_per_page' => 8,
    'tag' => $slug,
    'category_name' => 'Články'
  ];
  $loopClanky = new WP_Query($argsClanky);
  
  // Recepty s tagem produktu
  $argsRecepty = [
    'post_type' => 'post',
    'posts_per_page' => 8,
    'tag' => $slug,
    'category_name' => 'Recepty'
  ];
  $loopRecepty = new WP_Query($argsRecepty);
  
  // Zobrazit pouze pokud existují příspěvky
  if ($loopRecepty->have_posts() || $loopClanky->have_posts()) :
  ?>
    <div class="produktDetail__posts" data-tabs>
      <nav class="produktDetail__nav" role="tablist" aria-label="Související obsah">
        <?php if ($loopRecepty->have_posts()) : ?>
          <a href="#recepty"
             role="tab"
             aria-selected="true"
             aria-controls="recepty"
             id="tab-btn-recepty"
             data-tab="recepty"
             class="produktDetail__nav__btn is-active">
            Recepty (<?php echo $loopRecepty->found_posts; ?>)
          </a>
        <?php endif; ?>
        <?php if ($loopClanky->have_posts()) : ?>
          <a href="#clanky"
             role="tab"
             aria-selected="<?php echo !$loopRecepty->have_posts() ? 'true' : 'false'; ?>"
             aria-controls="clanky"
             id="tab-btn-clanky"
             data-tab="clanky"
             class="produktDetail__nav__btn<?php echo !$loopRecepty->have_posts() ? ' is-active' : ''; ?>">
            Články (<?php echo $loopClanky->found_posts; ?>)
          </a>
        <?php endif; ?>
      </nav>

      <div class="produktDetail__nav__panels">
        <?php if ($loopRecepty->have_posts()) : ?>
          <section id="recepty"
                   role="tabpanel"
                   aria-labelledby="tab-btn-recepty"
                   class="produktDetail__nav__panel is-active">
            <div class="listArticleSmall listArticleSmall--noBg listArticleSmall--col4">
              <?php while ($loopRecepty->have_posts()) : $loopRecepty->the_post(); ?>
                <article class="article-item">
                  <a href="<?php the_permalink(); ?>">
                    <?php if (has_post_thumbnail()) : ?>
                      <?php the_post_thumbnail('thumbnail'); ?>
                    <?php endif; ?>
                    <h3><?php the_title(); ?></h3>
                  </a>
                </article>
              <?php endwhile; wp_reset_postdata(); ?>
            </div>
          </section>
        <?php endif; ?>
        
        <?php if ($loopClanky->have_posts()) : ?>
          <section id="clanky"
                   role="tabpanel"
                   aria-labelledby="tab-btn-clanky"
                   class="produktDetail__nav__panel<?php echo !$loopRecepty->have_posts() ? ' is-active' : ''; ?>"
                   <?php echo $loopRecepty->have_posts() ? 'hidden' : ''; ?>>
            <div class="listArticleSmall">
              <?php while ($loopClanky->have_posts()) : $loopClanky->the_post(); ?>
                <article class="article-item">
                  <a href="<?php the_permalink(); ?>">
                    <?php if (has_post_thumbnail()) : ?>
                      <?php the_post_thumbnail('thumbnail'); ?>
                    <?php endif; ?>
                    <h3><?php the_title(); ?></h3>
                  </a>
                </article>
              <?php endwhile; wp_reset_postdata(); ?>
            </div>
          </section>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
  
  <script>
  // Univerzální funkce pro všechny záložky na stránce
  (function(){
    document.querySelectorAll('[data-tabs]').forEach(function(tabContainer){
      var tabs = tabContainer.querySelectorAll('.produktDetail__nav__btn');
      var panels = tabContainer.querySelectorAll('.produktDetail__nav__panel');
      
      if (!tabs.length || !panels.length) return;
      
      tabs.forEach(function(btn){
        btn.addEventListener('click', function(e){
          if (e && e.preventDefault) e.preventDefault();
          var target = btn.getAttribute('data-tab');
          
          tabs.forEach(function(b){ 
            b.classList.remove('is-active'); 
            b.setAttribute('aria-selected','false'); 
          });
          panels.forEach(function(p){ 
            p.classList.remove('is-active'); 
            p.setAttribute('hidden','true'); 
          });
          
          btn.classList.add('is-active');
          btn.setAttribute('aria-selected','true');
          var panel = document.getElementById(target);
          if(panel){ 
            panel.classList.add('is-active'); 
            panel.removeAttribute('hidden'); 
          }
        });
      });
      
      panels.forEach(function(p){ 
        if(!p.classList.contains('is-active')) p.setAttribute('hidden','true'); 
      });
    });
  })();
  </script>
</article>