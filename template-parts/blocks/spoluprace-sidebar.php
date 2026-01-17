<?php
$block = get_query_var('block_post');
if (!$block) return;
?>
<aside class="block block--spoluprace-sidebar">
  <?php if (has_post_thumbnail($block->ID)) : ?>
    <div class="block__icon">
      <?php echo get_the_post_thumbnail($block->ID, 'thumbnail'); ?>
    </div>
  <?php endif; ?>
  
  <div class="block__content">
    <h3><?php echo esc_html($block->post_title); ?></h3>
    <?php echo apply_filters('the_content', $block->post_content); ?>
  </div>
</aside>
