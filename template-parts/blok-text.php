<?php
$nadpis = get_sub_field('nadpis');
$text = get_sub_field('text');
?>
<div class="block block--text">
  <?php if ($nadpis) : ?>
    <h2><?php echo esc_html($nadpis); ?></h2>
  <?php endif; ?>
  <?php if ($text) : ?>
    <div class="block__content"><?php echo wpautop($text); ?></div>
  <?php endif; ?>
</div>
