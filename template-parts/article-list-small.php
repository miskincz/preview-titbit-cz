<?php
/**
 * Template Part: Article List Small
 * 
 * Malá položka článku pro výpisy a seznamy
 * Zobrazuje náhledový obrázek a nadpis s odkazem
 */
?>
<article>
	<a href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
            <!-- Náhledový obrázek -->
            <?php echo get_the_post_thumbnail( $page->ID, array( 300, 250) ); ?>
        <?php else: ?>
        <?php endif; ?>
		<span class="text">
			<h2><?php the_title(); ?></h2>
		</span>
	</a>
</article>								