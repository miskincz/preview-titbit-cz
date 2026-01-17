<?php
/**
 * Šablona pro blok zasobujemerestaurace
 * 
 * Zobrazuje obsah bloku typu "blok" pro sekci Zásobujeme restaurace
 * Načítá se přes funkci display_spoluprace() z blocks-system.php
 */

$block = get_query_var('block_post');
if (!$block) return;
?>
<div class="block__zasobujemerestaurace"> 
    <?php echo apply_filters('the_content', $block->post_content); ?>
</div>
  