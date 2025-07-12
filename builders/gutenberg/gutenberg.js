console.log('Bandfront Player block JS loaded');

window.wp = window.wp || {};
window.wp.domReady = window.wp.domReady || function(cb){cb();};

window.wp.domReady(function() {
    // Get dependencies
    const { registerBlockType } = wp.blocks;
    const { createElement: el } = wp.element;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { PanelBody } = wp.components;
    const { __ } = wp.i18n;
    
    // Register the block
    registerBlockType('bfp/bandfront-player-playlist', {
        edit: function(props) {
            const { attributes, setAttributes, isSelected } = props;
            const { shortcode } = attributes;
            
            // Get preview URL from localized config
            const config = window.bfp_gutenberg_editor_config || {};
            const previewUrl = config.url ? config.url + '?bfp-preview=' + encodeURIComponent(shortcode) : '';
            
            // Build the editor interface
            const editorElements = [];
            
            // Main editor area
            editorElements.push(
                el('div', { key: 'editor-main', className: 'bfp-block-editor' },
                    el('textarea', {
                        key: 'shortcode-input',
                        value: shortcode,
                        onChange: function(event) {
                            setAttributes({ shortcode: event.target.value });
                        },
                        className: 'bfp-playlist-shortcode-input',
                        rows: 3,
                        placeholder: __('Enter shortcode...', 'bandfront-player')
                    }),
                    previewUrl && el('div', { key: 'preview-container', className: 'bfp-iframe-container' },
                        el('iframe', {
                            src: previewUrl,
                            width: '100%',
                            height: '400',
                            scrolling: 'no',
                            style: { border: 'none', display: 'block' }
                        })
                    )
                )
            );
            
            // Inspector controls (sidebar)
            if (isSelected) {
                editorElements.push(
                    el(InspectorControls, { key: 'inspector' },
                        el(PanelBody, { 
                            title: __('Playlist Settings', 'bandfront-player'),
                            initialOpen: true 
                        },
                            el('div', { className: 'bfp-inspector-help' },
                                el('p', {},
                                    el('strong', {}, 'products_ids: '),
                                    config.ids_attr_description || __('Comma-separated product IDs.', 'bandfront-player')
                                ),
                                el('p', {},
                                    el('strong', {}, 'product_categories: '),
                                    config.categories_attr_description || __('Comma-separated product category slugs.', 'bandfront-player')
                                ),
                                el('p', {},
                                    el('strong', {}, 'product_tags: '),
                                    config.tags_attr_description || __('Comma-separated product tag slugs.', 'bandfront-player')
                                ),
                                el('p', { style: { marginTop: '20px' } },
                                    el('a', {
                                        href: 'https://therob.lol/shortcodes',
                                        target: '_blank',
                                        rel: 'noopener noreferrer'
                                    }, __('View Documentation', 'bandfront-player'))
                                )
                            )
                        )
                    );
                }
                
                return editorElements;
            },
            
            save: function() {
                // Server-side rendering, so return null
                return null;
            }
        });
    });
})();