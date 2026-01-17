jQuery(function($){
  var frame;
  var wrap = $('#footer-logos-wrapper');
  var input = $('#footer-logos-input');

  // CSS pro placeholder
  if(!$('#footer-logos-dnd-style').length){
    $('head').append('<style id="footer-logos-dnd-style">.footer-logo-placeholder{border:2px dashed #999;width:80px;height:80px;box-sizing:border-box;margin:0 8px 8px 0;background:#fafafa}</style>');
  }

  function initSortable(){
    if (typeof wrap.sortable !== 'function') {
      console.warn('jquery-ui-sortable není načteno');
      return;
    }
    if (wrap.data('ui-sortable')) wrap.sortable('destroy');
    wrap.sortable({
      items: '.footer-logo-item',
      placeholder: 'footer-logo-placeholder',
      tolerance: 'pointer',
      forcePlaceholderSize: true,
      update: updateIds
    }).disableSelection();
  }

  $('#add-footer-logos').on('click', function(e){
    e.preventDefault();
    if(frame){frame.open();return;}
    frame = wp.media({
      title: 'Vyberte loga',
      multiple: true,
      library: { type: 'image' }
    });
    frame.on('select', function(){
      var selection = frame.state().get('selection');
      selection.each(function(att){
        var id = att.get('id');
        if(wrap.find('.footer-logo-item[data-id="'+id+'"]').length) return;
        var sizes = att.get('sizes');
        var thumb = sizes && sizes.thumbnail ? sizes.thumbnail.url : att.get('url');
        wrap.append('<div class="footer-logo-item" data-id="'+id+'"><img src="'+thumb+'"/><span class="remove">×</span></div>');
      });
      updateIds();
      initSortable();
    });
    frame.open();
  });

  wrap.on('click', '.remove', function(){
    $(this).parent().remove();
    updateIds();
  });

  function updateIds(){
    var ids = [];
    wrap.find('.footer-logo-item').each(function(){
      ids.push($(this).data('id'));
    });
    input.val(ids.join(','));
  }

  initSortable();
});