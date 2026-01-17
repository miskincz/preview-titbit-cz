<?php
/**
 * Template: 404 Not Found
 * 
 * Zobrazí chybovou stránku 404 - Stránka nenalezena
 * Načte se automaticky, když WordPress nenajde požadovaný obsah
 */

get_header(); ?>
<div class="container">
	<main class="main">
		<!-- Obsah chybové stránky 404 -->
		<article class="article">
        <p></p>
			<h1 class="title--h1 text-center">Stránka, kterou hledáte bohužel neexistuje.</h1>
            <div class="text-center"><img src="<?php echo get_template_directory_uri(); ?>/assets/i/ico/ico_nophoto.svg" width="50%;"></div>
		</article>
	</main>
</div><!-- /container -->	
<?php get_footer(); ?>
