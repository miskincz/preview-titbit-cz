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
  ?>
  <div id="product-gallery-container">
    <ul id="product-gallery-list" class="product-gallery-list">
      <?php foreach ($gallery_ids as $image_id) : 
        if ($image_id) :
          $image = wp_get_attachment_image_src($image_id, 'thumbnail');
      ?>
        <li data-id="<?php echo esc_attr($image_id); ?>">
          <img src="<?php echo esc_url($image[0]); ?>" alt="">
          <button type="button" class="remove-image">×</button>
        </li>
      <?php endif; endforeach; ?>
    </ul>
    <input type="hidden" name="product_gallery_ids" id="product_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>">
    <button type="button" class="button button-primary" id="add-gallery-images">Přidat obrázky</button>
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
    }
    .product-gallery-list img {
      width: 100%;
      height: 100px;
      object-fit: cover;
      display: block;
    }
    .product-gallery-list .remove-image {
      position: absolute;
      top: 0;
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
  </style>
  
  <script>
  jQuery(document).ready(function($) {
    var galleryFrame;
    
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
        multiple: true
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
              '<li data-id="' + attachment.id + '">' +
                '<img src="' + attachment.sizes.thumbnail.url + '" alt="">' +
                '<button type="button" class="remove-image">×</button>' +
              '</li>'
            );
          }
        });
        
        $('#product_gallery_ids').val(ids.join(','));
      });
      
      galleryFrame.open();
    });
    
    // Odstranění obrázku z galerie
    $('#product-gallery-list').on('click', '.remove-image', function() {
      var li = $(this).closest('li');
      var id = li.data('id');
      var ids = $('#product_gallery_ids').val().split(',').filter(function(val) {
        return val != id;
      });
      li.remove();
      $('#product_gallery_ids').val(ids.join(','));
    });
    
    // jQuery Sortable pro změnu pořadí obrázků
    $('#product-gallery-list').sortable({
      update: function() {
        var ids = [];
        $('#product-gallery-list li').each(function() {
          ids.push($(this).data('id'));
        });
        $('#product_gallery_ids').val(ids.join(','));
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
});
