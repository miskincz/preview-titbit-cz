<?php
/**
 * Instagram Auto Import Functions
 * 
 * Automatický import příspěvků z Instagramu přes Instagram Graph API
 * 
 * Funkce:
 * - Importuje příspěvky z Instagram účtu
 * - Vytváří WordPress příspěvky v kategorii "Instagram"
 * - Stahuje a nastavuje featured images
 * - Plánovaný import každou hodinu
 * - Možnost manuálního spuštění importu z administrace
 */

// Hlavní funkce pro import Instagram příspěvků
function import_instagram_posts() {
  $access_token = get_field('instagram_access_token', 'option') ?: 'VÁŠ_INSTAGRAM_ACCESS_TOKEN';
  
  if (!$access_token || $access_token === 'VÁŠ_INSTAGRAM_ACCESS_TOKEN') {
    return; // Pokud není nastaven token, nic nedělej
  }
  
  $instagram_api = "https://graph.instagram.com/me/media?fields=id,caption,media_type,media_url,thumbnail_url,permalink,timestamp&access_token={$access_token}";
  
  $response = wp_remote_get($instagram_api);
  
  if (!is_wp_error($response)) {
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($data['data'])) {
      foreach ($data['data'] as $post) {
        // Zkontrolovat, zda příspěvek už neexistuje
        $existing = get_posts([
          'post_type' => 'post',
          'meta_key' => 'instagram_id',
          'meta_value' => $post['id'],
          'numberposts' => 1
        ]);
        
        if (empty($existing)) {
          // Vytvořit nový příspěvek
          $post_data = [
            'post_title' => wp_trim_words($post['caption'] ?? 'Instagram post', 10),
            'post_content' => $post['caption'] ?? '',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_category' => [get_cat_ID('Instagram')] // kategorie Instagram
          ];
          
          $post_id = wp_insert_post($post_data);
          
          if ($post_id) {
            // Uložit Instagram metadata
            update_post_meta($post_id, 'instagram_id', $post['id']);
            update_post_meta($post_id, 'instagram_url', $post['permalink']);
            update_post_meta($post_id, 'instagram_timestamp', $post['timestamp']);
            
            // Stáhnout a nastavit featured image
            if ($post['media_type'] === 'IMAGE' && !empty($post['media_url'])) {
              $image_url = $post['media_url'];
              $upload = media_sideload_image($image_url, $post_id, '', 'id');
              if (!is_wp_error($upload)) {
                set_post_thumbnail($post_id, $upload);
              }
            }
          }
        }
      }
    }
  }
}

// Naplánovat automatický import každou hodinu
add_action('wp', function() {
  if (!wp_next_scheduled('instagram_import_hook')) {
    wp_schedule_event(time(), 'hourly', 'instagram_import_hook');
  }
});
add_action('instagram_import_hook', 'import_instagram_posts');

// AJAX endpoint pro manuální spuštění importu (pro adminy)
add_action('wp_ajax_manual_instagram_import', function() {
  if (current_user_can('manage_options')) {
    import_instagram_posts();
    wp_die('Import dokončen');
  }
});

// Funkce pro zobrazení Instagram příspěvků v šabloně
function display_instagram_posts($limit = 6) {
  $posts = get_posts([
    'post_type' => 'post',
    'category_name' => 'instagram',
    'numberposts' => $limit,
    'meta_key' => 'instagram_id',
    'orderby' => 'date',
    'order' => 'DESC'
  ]);
  
  if ($posts) {
    echo '<div class="instagram-posts">';
    foreach ($posts as $post) {
      setup_postdata($post);
      $instagram_url = get_post_meta($post->ID, 'instagram_url', true);
      ?>
      <div class="instagram-post">
        <?php if (has_post_thumbnail($post->ID)) : ?>
          <div class="instagram-image">
            <a href="<?php echo esc_url($instagram_url); ?>" target="_blank">
              <?php echo get_the_post_thumbnail($post->ID, 'medium'); ?>
            </a>
          </div>
        <?php endif; ?>
        <div class="instagram-content">
          <h3><a href="<?php echo esc_url($instagram_url); ?>" target="_blank"><?php echo get_the_title($post->ID); ?></a></h3>
          <p><?php echo wp_trim_words(get_the_content($post->ID), 20); ?></p>
        </div>
      </div>
      <?php
    }
    echo '</div>';
    wp_reset_postdata();
  }
}

// Shortcode pro snadné vložení Instagram příspěvků do stránek
add_shortcode('instagram_posts', function($atts) {
  $atts = shortcode_atts([
    'limit' => 6
  ], $atts);
  
  ob_start();
  display_instagram_posts($atts['limit']);
  return ob_get_clean();
});
