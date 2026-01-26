/**
 * Přidání slug editoru do Gutenbergu
 * Umožňuje editaci URL/slugu přímo v editoru
 */
(function() {
  const { registerPlugin } = wp.plugins;
  const { useSelect, useDispatch } = wp.data;
  const { PluginDocumentSettingPanel } = wp.editor;
  const { TextControl } = wp.components;
  const { createElement: el } = wp;

  const SlugEditor = function() {
    const { editPost } = useDispatch('core/editor');
    const slug = useSelect(function(select) {
      return select('core/editor').getEditedPostAttribute('slug');
    });

    return el(
      PluginDocumentSettingPanel,
      {
        name: 'slug-editor-panel',
        title: 'URL (Slug)',
      },
      el(
        TextControl,
        {
          label: 'URL adresy (slug)',
          value: slug || '',
          onChange: function(newSlug) {
            editPost({ slug: newSlug });
          },
          help: 'Zadejte adresu URL pro tento příspěvek (bez mezer)',
        }
      )
    );
  };

  registerPlugin('slug-editor-plugin', {
    render: SlugEditor,
    icon: 'link',
  });
})();
