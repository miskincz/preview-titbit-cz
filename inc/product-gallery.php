<?php
/**
 * Vlastní galerie pro produkty (bez ACF PRO)
 * 
 * Přidává metabox pro správu galerie obrázků produktu
 * Umožňuje výběr více obrázků, změnu pořadí a smazání
 * 
 * Funkce:
 * - Custom metabox s media uploader
 * - Drag & drop pro změnu pořadí
 * - Uložení pořadí obrázků
 * - jQuery sortable pro změnu pořadí
 */

// Přidat meta box pro galerii
add_action('add_meta_boxes', function() {
  add_meta_box(
    'produkt_gallery',
    'Foto galerie produktu',
    'render_product_gallery_metabox',
    'produkt',
    'normal',
    'high'
  );
});

// Vykreslení meta boxu s galerií
function render_product_gallery_metabox($post) {
  wp_nonce_field('save_product_gallery', 'product_gallery_nonce');
  $gallery_ids = get_post_meta($post->ID, '_product_gallery', true);
  $gallery_ids = $gallery_ids ? explode(',', $gallery_ids) : [];
  $video_ids = get_post_meta($post->ID, '_product_videos', true);
  $video_ids = $video_ids ? explode(',', $video_ids) : array();
  ?>
  <div id="product-gallery-container">
    <ul id="product-gallery-list" class="product-gallery-list">
      <!-- Obrázky -->
      <?php foreach ($gallery_ids as $image_id) : 
        if ($image_id) :
          $image = wp_get_attachment_image_src($image_id, 'thumbnail');
      ?>
        <li data-id="<?php echo esc_attr($image_id); ?>" class="gallery-item-image">
          <div class="gallery-item-type">Foto</div>
          <img src="<?php echo esc_url($image[0]); ?>" alt="">
          <button type="button" class="remove-item">×</button>
        </li>
      <?php endif; endforeach; ?>
      
      <!-- Videa -->
      <?php foreach ($video_ids as $video_id) : 
        if ($video_id) :
          $video_url = wp_get_attachment_url($video_id);
          $video_mime = get_post_mime_type($video_id);
          $thumb_id = get_post_thumbnail_id($video_id);
          if ($thumb_id) {
            $thumb = wp_get_attachment_image_src($thumb_id, 'thumbnail');
            $thumb_url = $thumb[0];
          } else {
            $thumb_url = wp_get_attachment_image_src($video_id, 'thumbnail')[0] ?? '';
          }
      ?>
        <li data-video-id="<?php echo esc_attr($video_id); ?>" class="gallery-item-video gallery-item-editable">
          <div class="gallery-item-type">Video</div>
          <?php if ($thumb_url) : ?>
            <img src="<?php echo esc_url($thumb_url); ?>" alt="">
          <?php else : ?>
            <div class="gallery-item-video-placeholder">▶ Video</div>
          <?php endif; ?>
          <button type="button" class="remove-item">×</button>
          <button type="button" class="edit-video-thumb" title="Nastavit náhled">🖼️</button>
        </li>
      <?php endif; endforeach; ?>
    </ul>
    
    <input type="hidden" name="product_gallery_ids" id="product_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>">
    <input type="hidden" name="product_video_ids" id="product_video_ids" value="<?php echo esc_attr(implode(',', $video_ids)); ?>">
    <input type="hidden" name="product_video_thumbs" id="product_video_thumbs" value="<?php echo esc_attr(json_encode(get_post_meta($post->ID, '_product_video_thumbs', true) ?: [])); ?>">
    
    <div style="margin-top: 15px;">
      <button type="button" class="button button-primary" id="add-gallery-images" style="margin-right: 10px;">+ Přidat obrázky</button>
      <button type="button" class="button button-secondary" id="add-gallery-videos">+ Přidat videa</button>
    </div>
  </div>
  
  <style>
    /* Grid layout pro galerii v administraci */
    .product-gallery-list {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
      gap: 10px;
      list-style: none;
      padding: 0;
      margin: 15px 0;
    }
    .product-gallery-list li {
      position: relative;
      border: 1px solid #ddd;
      padding: 5px;
      cursor: move;
      background: white;
    }
    .product-gallery-list img {
      width: 100%;
      height: 100px;
      object-fit: cover;
      display: block;
    }
    .gallery-item-video-placeholder {
      width: 100%;
      height: 100px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #000;
      color: #fff;
      font-size: 40px;
    }
    .gallery-item-type {
      position: absolute;
      top: 2px;
      left: 2px;
      background: rgba(0,0,0,0.7);
      color: white;
      padding: 2px 6px;
      font-size: 11px;
      border-radius: 2px;
      z-index: 1;
    }
    .product-gallery-list .remove-item {
      position: absolute;
      bottom: 0;
      right: 0;
      background: red;
      color: white;
      border: none;
      width: 24px;
      height: 24px;
      cursor: pointer;
      font-size: 18px;
      line-height: 1;
    }
    .gallery-item-editable {
      position: relative;
    }
    .gallery-item-editable .edit-video-thumb {
      position: absolute;
      bottom: 24px;
      right: 0;
      background: rgba(0,0,0,0.8);
      color: white;
      border: none;
      width: 24px;
      height: 24px;
      cursor: pointer;
      font-size: 14px;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.2s;
    }
    .gallery-item-editable:hover .edit-video-thumb {
      opacity: 1;
    }
  </style>
  
  <script>
  jQuery(document).ready(function($) {
    var galleryFrame;
    var videoFrame;
    var thumbFrame;
    var currentVideoId = null;
    
    // Otevření media uploaderu pro výběr obrázků
    $('#add-gallery-images').on('click', function(e) {
      e.preventDefault();
      
      if (galleryFrame) {
        galleryFrame.open();
        return;
      }
      
      galleryFrame = wp.media({
        title: 'Vyberte obrázky pro galerii',
        button: { text: 'Přidat do galerie' },
        multiple: true,
        library: { type: 'image' }
      });
      
      // Přidání vybraných obrázků do galerie
      galleryFrame.on('select', function() {
        var selection = galleryFrame.state().get('selection');
        var ids = $('#product_gallery_ids').val().split(',').filter(Boolean);
        
        selection.map(function(attachment) {
          attachment = attachment.toJSON();
          if (ids.indexOf(String(attachment.id)) === -1) {
            ids.push(attachment.id);
            $('#product-gallery-list').append(
              '<li data-id="' + attachment.id + '" class="gallery-item-image">' +
                '<div class="gallery-item-type">Foto</div>' +
                '<img src="' + attachment.sizes.thumbnail.url + '" alt="">' +
                '<button type="button" class="remove-item">×</button>' +
              '</li>'
            );
          }
        });
        
        $('#product_gallery_ids').val(ids.join(','));
      });
      
      galleryFrame.open();
    });
    
    // Otevření media uploaderu pro výběr videí
    $('#add-gallery-videos').on('click', function(e) {
      e.preventDefault();
      
      if (videoFrame) {
        videoFrame.open();
        return;
      }
      
      videoFrame = wp.media({
        title: 'Vyberte videa pro galerii',
        button: { text: 'Přidat videa do galerie' },
        multiple: true,
        library: { type: 'video' }
      });
      
      // Přidání vybraných videí do galerie
      videoFrame.on('select', function() {
        var selection = videoFrame.state().get('selection');
        var ids = $('#product_video_ids').val().split(',').filter(Boolean);
        
        selection.map(function(attachment) {
          attachment = attachment.toJSON();
          if (ids.indexOf(String(attachment.id)) === -1) {
            ids.push(attachment.id);
            var thumbUrl = attachment.image && attachment.image.src ? attachment.image.src : '';
            var thumbHtml = thumbUrl 
              ? '<img src="' + thumbUrl + '" alt="">' 
              : '<div class="gallery-item-video-placeholder">▶ Video</div>';
            
            $('#product-gallery-list').append(
              '<li data-video-id="' + attachment.id + '" class="gallery-item-video gallery-item-editable">' +
                '<div class="gallery-item-type">Video</div>' +
                thumbHtml +
                '<button type="button" class="remove-item">×</button>' +
                '<button type="button" class="edit-video-thumb" title="Nastavit náhled">🖼️</button>' +
              '</li>'
            );
          }
        });
        
        $('#product_video_ids').val(ids.join(','));
      });
      
      videoFrame.open();
    });
    
    // Otevření uploaderu pro thumbnail videa
    $(document).on('click', '.edit-video-thumb', function(e) {
      e.preventDefault();
      const $li = $(this).closest('li');
      currentVideoId = $li.data('video-id');
      
      if (thumbFrame) {
        thumbFrame.open();
        return;
      }
      
      thumbFrame = wp.media({
        title: 'Vyberte náhled pro video',
        button: { text: 'Vybrat náhled' },
        multiple: false,
        library: { type: 'image' }
      });
      
      thumbFrame.on('select', function() {
        const attachment = thumbFrame.state().get('selection').first().toJSON();
        
        // Uložit mapping video ID -> thumbnail URL
        let thumbs = {};
        const thumbsJson = $('#product_video_thumbs').val();
        if (thumbsJson) {
          try {
            thumbs = JSON.parse(thumbsJson);
          } catch(e) {}
        }
        
        thumbs[currentVideoId] = attachment.sizes.thumbnail.url;
        $('#product_video_thumbs').val(JSON.stringify(thumbs));
        
        // Aktualizovat obrázek v galerii
        const $li = $('li[data-video-id="' + currentVideoId + '"]');
        $li.find('img, .gallery-item-video-placeholder').remove();
        $li.prepend('<img src="' + attachment.sizes.thumbnail.url + '" alt="">');
      });
      
      thumbFrame.open();
    });
    
    // Smazání obrázku nebo videa z galerie
    $(document).on('click', '.remove-item', function() {
      const $li = $(this).closest('li');
      
      if ($li.hasClass('gallery-item-image')) {
        // Smazání obrázku
        const id = $li.data('id');
        let ids = $('#product_gallery_ids').val().split(',').filter(function(val) {
          return val != id && val.trim() !== '';
        });
        $li.remove();
        $('#product_gallery_ids').val(ids.join(','));
      } else if ($li.hasClass('gallery-item-video')) {
        // Smazání videa
        const id = $li.data('video-id');
        let ids = $('#product_video_ids').val().split(',').filter(function(val) {
          return val != id && val.trim() !== '';
        });
        
        // Smazat i thumbnail mapping
        let thumbs = {};
        const thumbsJson = $('#product_video_thumbs').val();
        if (thumbsJson) {
          try {
            thumbs = JSON.parse(thumbsJson);
            delete thumbs[id];
          } catch(e) {}
        }
        $('#product_video_thumbs').val(JSON.stringify(thumbs));
        
        $li.remove();
        $('#product_video_ids').val(ids.join(','));
      }
    });
    
    // jQuery Sortable pro změnu pořadí
    $('#product-gallery-list').sortable({
      update: function() {
        var imageIds = [];
        var videoIds = [];
        
        $('#product-gallery-list li.gallery-item-image').each(function() {
          imageIds.push($(this).data('id'));
        });
        
        $('#product-gallery-list li.gallery-item-video').each(function() {
          videoIds.push($(this).data('video-id'));
        });
        
        $('#product_gallery_ids').val(imageIds.join(','));
        $('#product_video_ids').val(videoIds.join(','));
      }
    });
  });
  </script>
  <?php
}

// Uložení galerie při uložení produktu
add_action('save_post_produkt', function($post_id) {
  // Kontrola nonce a oprávnění
  if (!isset($_POST['product_gallery_nonce']) || 
      !wp_verify_nonce($_POST['product_gallery_nonce'], 'save_product_gallery')) {
    return;
  }
  
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!current_user_can('edit_post', $post_id)) return;
  
  // Uložení pořadí obrázků
  $gallery_ids = isset($_POST['product_gallery_ids']) ? sanitize_text_field($_POST['product_gallery_ids']) : '';
  update_post_meta($post_id, '_product_gallery', $gallery_ids);
  
  // Uložení videí
  $video_ids = isset($_POST['product_video_ids']) ? sanitize_text_field($_POST['product_video_ids']) : '';
  update_post_meta($post_id, '_product_videos', $video_ids);
  
  // Uložení thumbnail mappingu pro videa
  $video_thumbs_json = isset($_POST['product_video_thumbs']) ? wp_unslash($_POST['product_video_thumbs']) : '{}';
  $video_thumbs = json_decode($video_thumbs_json, true);
  if (is_array($video_thumbs)) {
    // Sanitizovat URLs
    $sanitized_thumbs = array();
    foreach ($video_thumbs as $video_id => $thumb_url) {
      $sanitized_thumbs[sanitize_text_field($video_id)] = esc_url_raw($thumb_url);
    }
    update_post_meta($post_id, '_product_video_thumbs', $sanitized_thumbs);
  }
});
