<?php
/**
 * Šablona pro blok spoluprace
 * 
 * Zobrazuje obsah bloku typu "blok" pro sekci spoluprace
 * Načítá se přes funkci display_block() z blocks-system.php
 */

$block = get_query_var('block_post');
if (!$block) return;
?>
<div class="block__karieravtitbitu"> 
    <?php echo apply_filters('the_content', $block->post_content); ?>
</div>
  