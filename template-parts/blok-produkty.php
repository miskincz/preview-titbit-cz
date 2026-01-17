<?php
$produkty = get_sub_field('produkty');

if ($produkty && is_array($produkty)) :
?>
<div class="block block--products">
  <ul class="products-list">
    <?php foreach ($produkty as $product) : ?>
      <li>
        <a href="<?php echo get_permalink($product->ID); ?>">
          <?php if (has_post_thumbnail($product->ID)) : ?>
            <?php echo get_the_post_thumbnail($product->ID, 'thumbnail'); ?>
          <?php endif; ?>
          <span><?php echo esc_html(get_the_title($product->ID)); ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
