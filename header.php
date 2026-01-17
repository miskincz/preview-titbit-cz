<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php bloginfo('name'); ?></title>
  <?php wp_head(); ?>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&family=Trocchi&display=swap" rel="stylesheet">

</head>
<body <?php body_class(); ?>>
  <div class="siteContainer">
    <header class="header">
      <div class="header__container">
        <!-- Vyhledávání -->
        <div class="header__search">
          <?php echo do_shortcode('[wpdreams_ajaxsearchlite]'); ?>
        </div><!-- .header__search -->

        <!-- Logo webu -->
        <div class="header__logo">
          <div class="site-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
              <img src="<?php echo get_template_directory_uri(); ?>/assets/img/logo-titbit.svg" alt="<?php bloginfo('name'); ?>">
              <span>fruits and vegetables from all around the world</span>
            </a>
          </div>
        </div><!-- .header__logo -->
        
        <!-- Horní navigace a e-shop tlačítko -->
        <div class="header__nav">
          <?php
            // Horní menu (header-menu)
            wp_nav_menu([
              'theme_location' => 'header-menu',
              'container' => false,
              'menu_class' => 'header-menu',
              'fallback_cb' => false
            ]);
          ?>
          <div class="header__nav__eshop">
            <a href="https://obchod.titbit.cz/" class="btn--eshop">E-Shop</a>
          </div>
          jazyky
        </div><!-- .header__nav -->
      </div><!-- .header__container -->
    </header>
  </div><!-- .siteContainer -->

  <!-- Hlavní navigace -->
  <nav class="mainNav">
    <div class="siteContainer">
      <?php
        // Hlavní menu (main-menu)
        wp_nav_menu([
          'theme_location' => 'main-menu',
          'container' => false,
          'menu_class' => 'main-menu',
          'fallback_cb' => false
        ]);
      ?>
    </div><!-- .siteContainer -->
  </nav><!-- .main-nav -->

