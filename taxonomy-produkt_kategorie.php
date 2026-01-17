<?php 
/**
 * Template: Taxonomy - Produkt Kategorie (Dispatcher)
 * 
 * Hlavní šablona pro kategorie produktů
 * Rozhoduje, která specifická šablona se načte podle hierarchie:
 * - ovoce-a-zelenina
 * - ready-to-eat
 * - produktove-rady
 */

/* Taxonomy template for produkt_kategorie */
$term = get_queried_object();

// Kontrola pro ovoce-a-zelenina
// Kontrola pro ovoce-a-zelenina
$root_ovoce = get_term_by('slug', 'ovoce-a-zelenina', 'produkt_kategorie');
if ($root_ovoce && ($term->term_id === $root_ovoce->term_id || in_array($root_ovoce->term_id, get_ancestors($term->term_id, 'produkt_kategorie')))) {
  include locate_template('taxonomy-produkt_kategorie-ovoce-a-zelenina.php');
  return;
}

// Kontrola pro ready-to-eat
$root_rte = get_term_by('slug', 'ready-to-eat', 'produkt_kategorie');
if ($root_rte && ($term->term_id === $root_rte->term_id || in_array($root_rte->term_id, get_ancestors($term->term_id, 'produkt_kategorie')))) {
  include locate_template('taxonomy-produkt_kategorie-ready-to-eat.php');
  return;
}

// Kontrola pro produktove-rady
$root_produkty = get_term_by('slug', 'produktove-rady', 'produkt_kategorie');
if ($root_produkty && ($term->term_id === $root_produkty->term_id || in_array($root_produkty->term_id, get_ancestors($term->term_id, 'produkt_kategorie')))) {
  include locate_template('taxonomy-produkt_kategorie-produktove-rady.php');
  return;
}

// Fallback - pokud není v žádné z těchto větví
get_header();
?>
<main class="siteContainer">
  <p>Šablona pro tuto kategorii není k dispozici.</p>
</main>
<?php get_footer(); ?>

