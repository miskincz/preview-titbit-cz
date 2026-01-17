<?php
$image = get_sub_field('obrazek');
$popisek = get_sub_field('popisek');

if ($image) :
?>
<div class="block block--image">
  <?php if (is_numeric($image)) : ?>
    <?php echo wp_get_attachment_image($image, 'large'); ?>
  <?php elseif (is_array($image)) : ?>
    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
  <?php endif; ?>
  
  <?php if ($popisek) : ?>
    <p class="block__caption"><?php echo esc_html($popisek); ?></p>
  <?php endif; ?>
</div>
<?php endif; ?>
