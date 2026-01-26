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
        <?php 
          $thumbnail_id = get_post_thumbnail_id();
          $full_image = wp_get_attachment_image_url($thumbnail_id, 'full');
        ?>
        <a href="<?php echo esc_url($full_image); ?>" 
           class="gallery-item"
           data-lightbox="gallery">
          <?php the_post_thumbnail('large'); ?>
        </a>
      <?php endif; ?>

      <!-- Fotogalerie produktu -->
      <?php 
      // Načtení galerie z meta pole
      $galerie_ids = get_post_meta(get_the_ID(), '_product_gallery', true);
      $galerie = $galerie_ids ? explode(',', $galerie_ids) : [];
      
      // Načtení videí z meta pole
      $video_ids = get_post_meta(get_the_ID(), '_product_videos', true);
      $video_ids = $video_ids ? explode(',', $video_ids) : [];
      
      // Načtení custom thumbnailů pro videa
      $video_thumbs = get_post_meta(get_the_ID(), '_product_video_thumbs', true);
      $video_thumbs = $video_thumbs && is_array($video_thumbs) ? $video_thumbs : [];
       
      if ( ($galerie && is_array($galerie)) || ($video_ids && is_array($video_ids)) ) : ?>
        <div class="produktDetail__gallery">          
          <div class="gallery-grid">
            <!-- Obrázky z galerie -->
            <?php foreach ( $galerie as $foto_id ) : 
              $foto_id = trim($foto_id);
              if (!$foto_id) continue;
              $full_image = wp_get_attachment_image_url($foto_id, 'full');
            ?>
              <a href="<?php echo esc_url($full_image); ?>" 
                 class="gallery-item gallery-image"
                 data-lightbox="gallery">
                <?php echo wp_get_attachment_image( $foto_id, 'product-thumb' ); ?>
              </a>
            <?php endforeach; ?>
            
            <!-- Videa -->
            <?php foreach ( $video_ids as $video_id ) : 
              $video_id = trim($video_id);
              if (!$video_id) continue;
              
              $video_url = wp_get_attachment_url($video_id);
              if (!$video_url) continue;
              
              // Zkusit vlastní thumbnail, pak featured image, pak nic
              $thumb_url = '';
              if (isset($video_thumbs[$video_id])) {
                $thumb_url = $video_thumbs[$video_id];
              } else {
                $thumb_id = get_post_thumbnail_id($video_id);
                if ($thumb_id) {
                  $thumb_url = wp_get_attachment_image_url($thumb_id, 'product-thumb');
                }
              }
            ?>
              <a href="<?php echo esc_url($video_url); ?>" 
                 class="gallery-item gallery-video gallery-video--mp4"
                 data-lightbox="gallery"
                 data-video-type="mp4">
                <?php if ($thumb_url) : ?>
                  <img src="<?php echo esc_url($thumb_url); ?>" alt="Video" class="gallery-video__thumb">
                <?php else : ?>
                  <div class="gallery-video__placeholder"></div>
                <?php endif; ?>
                <button type="button" class="gallery-video__button" aria-label="Play video">
                  <svg class="gallery-video__icon" viewBox="0 0 24 24" fill="currentColor">
                    <polygon points="5 3 19 12 5 21"></polygon>
                  </svg>
                </button>
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
      $root_categories = ['produktove-rady', 'ready-to-eat', 'ovoce-a-zelenina'];
      
      // Získat všechny root kategorie jednou
      $ovoce_term = get_term_by('slug', 'ovoce-a-zelenina', 'produkt_kategorie');
      $ovoce_id = $ovoce_term ? $ovoce_term->term_id : 0;
      
      $ready_to_eat_term = get_term_by('slug', 'ready-to-eat', 'produkt_kategorie');
      $ready_to_eat_id = $ready_to_eat_term ? $ready_to_eat_term->term_id : 0;
      
      if ( $cats && ! is_wp_error( $cats ) ) {
        // Seřadit kategorie do dvou skupin podle priority
        $ready_to_eat = [];
        $product_series = [];
        
        foreach ( $cats as $cat ) {
          // Přeskočit root kategorie
          if ( in_array( $cat->slug, $root_categories, true ) ) {
            continue;
          }
          
          // Pokud je přímý potomek ovoce-a-zelenina, přeskočit vše kromě cerstve-chilli
          if ( $ovoce_id && $cat->parent === $ovoce_id && $cat->slug !== 'cerstve-chilli' ) {
            continue;
          }
          
          // Zařadit do skupin podle parent ID
          if ( $ready_to_eat_id && $cat->parent === $ready_to_eat_id ) {
            $ready_to_eat[] = $cat;
          } else {
            $product_series[] = $cat;
          }
        }
        
        // Seřadit: ready-to-eat, produktove-rady
        $sorted_cats = array_merge( $ready_to_eat, $product_series );
        
        $badges = [];
        foreach ( $sorted_cats as $cat ) {
          // Logo z ACF pole kategorie
          $image = get_field('ockat_logo2', 'produkt_kategorie_' . $cat->term_id);
          if (!$image) {
            $image = get_field('ockat_logo', 'produkt_kategorie_' . $cat->term_id);
          }
          
          // Tooltip - nejdřív zkusit ACF pole, pak se vrátit na název
          $tooltip = get_field('ockat_logo_tooltip', 'produkt_kategorie_' . $cat->term_id);
          if (!$tooltip) {
            $tooltip = $cat->name;
          }
          
          if ($image) {
            // S logem
            $badges[] = sprintf(
              '<span class="badge tooltip-wrapper" data-tooltip="%s"><a href="%s"><img src="%s" alt="%s" class="badge-icon"></a></span>',
              esc_attr($tooltip),
              esc_url( get_term_link( $cat ) ),
              esc_url($image),
              esc_attr($cat->name)
            );
          } else {
            // Jen text, bez loga
            $badges[] = sprintf(
              '<span class="badge tooltip-wrapper" data-tooltip="%s"><a href="%s">%s</a></span>',
              esc_attr($tooltip),
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
        // Rozdělit text na jednotlivé prvky
        $casti = array_map('trim', explode(',', $dostupnost_text));
        $dostupne_mesice = [];
        $dalsi_text = [];
        
        // Rozdělit na čísla (měsíce) a ostatní text
        foreach ($casti as $cast) {
          if (is_numeric($cast)) {
            $dostupne_mesice[] = (int)$cast;
          } elseif ($cast !== '') {
            $dalsi_text[] = $cast;
          }
        }
        
        // Pokud jsou číselné hodnoty, zobrazit tabulku
        if (!empty($dostupne_mesice)) :
      ?>
        <div class="produktDetail__dostupnost">
          <h4>Dostupnost v měsících</h4>
          <div class="produktDetail__dostupnost__main">
            <ul class="produktDetail__dostupnost__mesice">
              <?php
              // Vyřadit měsíce
              for ($cislo = 1; $cislo <= 12; $cislo++) {
                $je_dostupny = in_array($cislo, $dostupne_mesice);
                $class = $je_dostupny ? 'is-available' : '';
                printf('<li class="%s">%s</li>', esc_attr($class), $cislo);
              }
              ?>
            </ul>
            <?php if (!empty($dalsi_text)) : ?>
              <div class="produktDetail__dostupnost__text">
                <?php echo esc_html(implode(', ', $dalsi_text)); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php 
        // Pokud jsou jen texty bez čísel, zobrazit jen text
        elseif (!empty($dalsi_text)) :
      ?>
        <div class="produktDetail__dostupnost">
          <h4>Dostupnost</h4>
          <div class="produktDetail__dostupnost__text">
            <?php echo esc_html(implode(', ', $dalsi_text)); ?>
          </div>
        </div>
      <?php 
        endif;
      endif; ?>

      <!-- Kde koupit -->
      <?php if ( $titbit_shop_url = get_field('produkty_titbiteshop') ) : ?>
        <div class="produktDetail__section">
          <h4 class="produktDetail__section__cart">
            <svg width="14" height="20" viewBox="0 0 14 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M13.2899 5.16935H10.9531V4.31449C10.9531 1.93477 9.17926 0 6.99742 0C4.81558 0 3.04168 1.93477 3.04168 4.31449V5.16935H0.7049C0.314995 5.16935 0 5.47434 0 5.85176V17.6103C0 18.9277 1.10769 20 2.46847 20H11.5315C12.8923 20 14 18.9277 14 17.6103V5.85176C14 5.47434 13.6849 5.16935 13.2951 5.16935H13.2899ZM4.44632 4.31449C4.44632 2.68718 5.59018 1.36486 6.99482 1.36486C8.39945 1.36486 9.54332 2.6897 9.54332 4.31449V5.16935H4.44632V4.31449ZM12.585 17.6103C12.585 18.1753 12.1099 18.6352 11.5263 18.6352H2.46327C1.87969 18.6352 1.40459 18.1753 1.40459 17.6103V6.53417H3.03908V8.10899C3.03908 8.48641 3.35407 8.79139 3.74398 8.79139C4.13388 8.79139 4.44888 8.48641 4.44888 8.10899V6.53417H9.54592V8.10899C9.54592 8.48641 9.86091 8.79139 10.2508 8.79139C10.6406 8.79139 10.9557 8.48641 10.9557 8.10899V6.53417H12.5902V17.6103H12.585Z" fill="#095352"/>
            </svg>
            Koupíte u nás
          </h4>
          <a href="<?php echo esc_url($titbit_shop_url); ?>" class="btn btn--primary" target="_blank" >
            Jít nakupovat
          </a>
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
        <?php display_block(11796, 'spoluprace'); ?>
      </div>
      <div>
        <?php display_block(11797, 'zasobujemerestaurace'); ?>
      </div>
    </div>
  
  
  
  <?php
  // Dynamické záložky: vytvoříme pole dostupných sekcí podle ACF
  $tabs = [];
  $charakteristika = get_field('produkty_charakteristika');
  $vyuziti = get_field('produkty_vyuziti');
  $nutricni = get_field('produkty_nutricni_hodnoty');
  $vyzkousej = get_field('produkty_muzete_vyzkousei');
  $slozeni = get_field('produkty_slozeni');

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
  if ( $slozeni )   $tabs[] = [ 'key' => 'composition', 'label' => 'Složení', 'content' => $slozeni ];

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