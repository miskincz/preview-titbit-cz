<?php
/**
 * Template: Front Page (Homepage)
 * 
 * Hlavní domovská stránka zobrazující:
 * - Kategorie produktů s obrázky z menu "hp-kategorie"
 * - Vlastní produkce z menu "hp-vlastni-produkce"
 * - Bloky spolupráce a restaurace
 */

get_header(); ?>
<main>
  <div class="index__intro">
    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/hpbanner.jpg" />
  </div>

  <div class="siteContainer">
    <h2 class="title--h2 text--center">Do obchodů a restaurací dovážíme z celého světa</h2>
    <?php
    // Načtení menu a obrázků z kategorií produktů
    $locations = get_nav_menu_locations();
    if (isset($locations['hp-kategorie'])) {
      $menu_items = wp_get_nav_menu_items($locations['hp-kategorie']);
      if ($menu_items) {
        echo '<ul class="index__categories">';
        foreach ($menu_items as $item) {

          // Zkusit najít kategorii podle URL
          $category_slug = basename(parse_url($item->url, PHP_URL_PATH));
          $category = get_term_by('slug', $category_slug, 'produkt_kategorie');

          $bg_style = '';
          if ($category) {
            // Načtení obrázku z ACF pole kategorie
            $image = get_field('ockat_hp_obrazek', 'produkt_kategorie_' . $category->term_id);
            if ($image) {
              $bg_style = ' style="background-image: url(' . esc_url($image) . ')"';
            }
          }


          // Výpis položky menu s obrázkem na pozadí
          echo '<li' . $bg_style . '>';
          printf('<a href="%s">', $item->url);
          
          // Načtení a výpis loga
          if ($category) {
            $logo = get_field('ockat_logo', 'produkt_kategorie_' . $category->term_id);
            if ($logo) {
              printf('<img src="%s" alt="" class="index__categories__logo">', esc_url(is_array($logo) ? $logo['url'] : $logo));
            }
          }
          
          printf('<span class="btn btn--block">%s</span>', esc_html($item->title));
          echo '</a>';
          echo '</li>';
        }
        echo '</ul>';
      }
    }
    ?>

    <h2 class="title--h2 text--center">Vlastní produkce</h2>
    <?php
    // Načtení menu a obrázků z kategorií produktů pro vlastní produkci
    $locations = get_nav_menu_locations();
    if (isset($locations['hp-vlastni-produkce'])) {
      $menu_items = wp_get_nav_menu_items($locations['hp-vlastni-produkce']);
      if ($menu_items) {
        echo '<ul class="index__categories">';
        foreach ($menu_items as $item) {

          // Zkusit najít kategorii podle URL
          $category_slug = basename(parse_url($item->url, PHP_URL_PATH));
          $category = get_term_by('slug', $category_slug, 'produkt_kategorie');

          $bg_style = '';
          if ($category) {
            // Načtení obrázku z ACF pole kategorie
            $image = get_field('ockat_hp_obrazek', 'produkt_kategorie_' . $category->term_id);
            if ($image) {
              $bg_style = ' style="background-image: url(' . esc_url($image) . ')"';
            }
          }

          // Výpis položky menu s obrázkem na pozadí
          echo '<li' . $bg_style . '>';
          printf('<a href="%s">', $item->url);
          
          // Načtení a výpis loga
          if ($category) {
            $logo = get_field('ockat_logo', 'produkt_kategorie_' . $category->term_id);
            if ($logo) {
              printf('<img src="%s" alt="" class="index__categories__logo2">', esc_url(is_array($logo) ? $logo['url'] : $logo));
            }
          }
          
          printf('<span class="btn btn--block">%s</span>', esc_html($item->title));
          echo '</a>';
          echo '</li>';
        }
        echo '</ul>';
      }
    }
    ?>

    <!-- Bloky spolupráce -->
    <div class="grid grid--col-2 grid--full-height">
      <div>
        <?php display_block(11796, 'spoluprace'); ?>
      </div>
      <div>
        <?php display_block(11797, 'zasobujemerestaurace'); ?>
      </div>
    </div>


    <div class="indexBanners">
      <h4 class="indexBanners__title title--upper ">najdete v obchodech a e-shopech</h4>
      <div class="indexBanners__main">
      <?php
        $banner_ids = [11863]; // Přidejte další ID, např. [11863, 11864, 11865, 11866]
        foreach ($banner_ids as $banner_id):
          $obrazek = get_field('hp_produktbanner-img', $banner_id);
          $barva = get_field('hp_produktbanner-barva', $banner_id);
          $nadpis = get_field('hp_produktbanner-nadpis', $banner_id);
          $text = get_field('hp_produktbanner-text', $banner_id);
          $odkaz = get_field('hp_produktbanner-odkaz', $banner_id);
      ?>
        <div class="indexBanners__product" style="background-color: <?php echo esc_attr($barva ?: '#DF0C53'); ?>;">
          <a href="<?php echo esc_url($odkaz); ?>">
            <?php if ($obrazek): ?><img src="<?php echo esc_url(is_array($obrazek) ? $obrazek['url'] : $obrazek); ?>" alt=""><?php endif; ?>
            <div class="indexBanners__product--text">
              <h4 class="title--upper ">koupíte v našem e-shopu</h4>
              <?php if ($nadpis): ?>
                <h3 class="title--h1"><?php echo esc_html($nadpis); ?></h3>
              <?php endif; ?>
              <?php if ($text): ?>
                <?php echo wp_kses_post($text); ?>
              <?php endif; ?>
              <?php if ($odkaz): ?>
                <p><span class="btn btn--block">Jít nakupovat</span></p>
              <?php endif; ?>
            </div>
          </a>
        </div>  
        <?php endforeach; ?>
    

      <div class="indexBanners__small">
        <?php
        $banner_ids = [11884]; // Přidejte další ID, např. [11863, 11864, 11865, 11866]
        foreach ($banner_ids as $banner_id):
          $obrazek = get_field('hp_malybanner-img', $banner_id);
          $odkaz = get_field('hp_malybanner-link', $banner_id);
          ?>
            <a href="<?php echo esc_url($odkaz); ?>">
              <?php if ($obrazek): ?>
                <img src="<?php echo esc_url(is_array($obrazek) ? $obrazek['url'] : $obrazek); ?>" alt="">
                <span class="btn btn--block">To mě zajímá</span>
              <?php endif; ?>
            </a>
          
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Bloky spolupráce -->
    <div class="grid grid--col-2 grid--full-height">
      <div>
        <?php display_block(11922, 'jsmecertifikovanouspolecnosti'); ?>
      </div>
      <div>
        <?php display_block(11923, 'karieravtitbitu'); ?>
      </div>
    </div>

</main><!-- .siteContainer -->
<?php get_footer(); ?>