<?php
/**
 * Admin UI přizpůsobení
 * 
 * Obsahuje úpravy pro WordPress administraci:
 * - Přidání sloupce s náhledovým obrázkem do seznamu příspěvků a stránek
 * - Vlastní styly pro admin rozhraní
 */

// Přidání sloupce náhledového obrázku pro příspěvky a stránky
function mytheme_add_thumb_column($cols){
  $new = [];
  $inserted = false;
  foreach($cols as $key=>$label){
    if(!$inserted && $key==='title'){
      $new['thumb'] = __('Obrázek','mytheme');
      $inserted = true;
    }
    $new[$key]=$label;
  }
  if(!$inserted){
    $new['thumb'] = __('Obrázek','mytheme');
  }
  return $new;
}
add_filter('manage_posts_columns','mytheme_add_thumb_column');
add_filter('manage_pages_columns','mytheme_add_thumb_column');

// Vykreslení obsahu sloupce náhledového obrázku
function mytheme_render_thumb_column($col,$post_id){
  if($col==='thumb'){
    if(has_post_thumbnail($post_id)){
      echo get_the_post_thumbnail($post_id,'thumbnail',[ 'style'=>'max-width:50px;height:auto;display:block;' ]);
    } else {
      echo '<span style="opacity:.4;font-size:11px;">—</span>';
    }
  }
}
add_action('manage_posts_custom_column','mytheme_render_thumb_column',10,2);
add_action('manage_pages_custom_column','mytheme_render_thumb_column',10,2);

// Styling pro nový sloupec + budoucí admin úpravy
add_action('admin_head', function(){
  echo '<style>.column-thumb{width:60px}.column-thumb img{border:1px solid #ddd;padding:2px;background:#fff}</style>';
});
