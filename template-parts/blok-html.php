<?php
$html = get_sub_field('html_kod');

if ($html) :
?>
<div class="block block--html">
  <?php echo $html; // Pozor: používejte pouze důvěryhodný HTML ?>
</div>
<?php endif; ?>
