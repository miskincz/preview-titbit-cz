<?php
/**
 * Template Part: Page Content
 * 
 * Základní zobrazení obsahu statické stránky
 * Používá se v page.php
 */
?>
<div class="container">
    <article class="siteArticle">
        <?php
            // Nadpis a obsah stránky
            the_title('<h1>','</h1>');
            the_content();
        ?>
    </article>
</div>
