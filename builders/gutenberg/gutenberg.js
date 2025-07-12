console.log('Bandfront Player block JS loaded');

window.wp = window.wp || {};
window.wp.domReady = window.wp.domReady || function(cb) { cb(); };

window.wp.domReady(function() {
    const { registerBlockType } = wp.blocks;
    const { createElement: el } = wp.element;
    const { InspectorControls } = wp.blockEditor || wp.editor;
    const { PanelBody } = wp.components;
    const { __ } = wp.i18n;

    console.log('Registering Bandfront Player block...');

    registerBlockType('bfp/bandfront-player-playlist', {
        title: __('Bandfront Player Playlist', 'bandfront-player'),
        description: __('Display a Bandfront Player playlist.', 'bandfront-player'),
        category: 'media',
        icon: 'playlist-audio',
        supports: {
            html: false,
            customClassName: true,
            align: ['wide', 'full']
        },
        attributes: {
            shortcode: {
                type: 'string',
                default: '[bfp-playlist products_ids="*" controls="track"]'
            }
        },

        edit: function(props) {
            const { attributes, setAttributes, isSelected } = props;
            const { shortcode } = attributes;
            const config = window.bfp_gutenberg_editor_config || {};
            const editorElements = [];

            editorElements.push(
                el('div', {
                    key: 'editor-main',
                    className: 'bfp-block-editor'
                },
                    el('label', {
                        key: 'shortcode-label',
                        className: 'bfp-shortcode-label'
                    }, __('Shortcode:', 'bandfront-player')),
                    el('textarea', {
                        key: 'shortcode-input',
                        value: shortcode,
                        onChange: function(event) {
                            setAttributes({ shortcode: event.target.value });
                        },
                        className: 'bfp-playlist-shortcode-input',
                        rows: 3,
                        placeholder: __('[bfp-playlist products_ids="*" controls="track"]', 'bandfront-player')
                    }),
                    el('div', {
                        key: 'preview-note',
                        className: 'bfp-preview-note'
                    },
                        el('p', {}, __('Preview will be displayed on the frontend.', 'bandfront-player'))
                    )
                )
            );

            if (isSelected) {
                editorElements.push(
                    el(InspectorControls, { key: 'inspector' },
                        el(PanelBody, {
                            title: __('Playlist Settings', 'bandfront-player'),
                            initialOpen: true
                        },
                            el('div', { className: 'bfp-inspector-help' },
                                el('h3', {}, __('Main playlist attributes', 'bandfront-player')),
                                el('hr', {}),
                                el('p', {},
                                    el('strong', {}, 'products_ids: '),
                                    config.ids_attr_description || __('Comma-separated product IDs. Use "*" for all products.', 'bandfront-player')
                                ),
                                el('p', {},
                                    el('strong', {}, 'product_categories: '),
                                    config.categories_attr_description || __('Comma-separated product category slugs.', 'bandfront-player')
                                ),
                                el('p', {},
                                    el('strong', {}, 'product_tags: '),
                                    config.tags_attr_description || __('Comma-separated product tag slugs.', 'bandfront-player')
                                ),
                                el('p', {
                                    style: {
                                        marginTop: '20px',
                                        fontWeight: 'bold'
                                    }
                                }, config.more_details || __('See documentation for more shortcode options.', 'bandfront-player')),
                                el('a', {
                                    href: 'https://therob.lol/shortcodes',
                                    target: '_blank',
                                    rel: 'noopener noreferrer',
                                    className: 'button button-secondary',
                                    style: { marginTop: '10px' }
                                }, __('View Documentation', 'bandfront-player'))
                            )
                        ) // <- FIXED this closing PanelBody
                    ) // <- closes InspectorControls
                );
            }

            return editorElements;
        },

        save: function() {
            return null;
        }
    });

    console.log('Bandfront Player block registered successfully');
});
