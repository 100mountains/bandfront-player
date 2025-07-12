( function( blocks, element ) {
	var el 					= element.createElement,
		InspectorControls 	= ('blockEditor' in wp) ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;

	/* Plugin Category */
	blocks.getCategories().push({slug: 'wcmp', title: 'WooCommerce Music Player'});

	/* ICONS */
	const iconWCMPP = el('img', { width: 20, height: 20, src:  "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAALEgAACxIB0t1+/AAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNui8sowAAAChSURBVDiNY/z//z8DNQETVU0bEgaywBhLU2eQFJgxczIZYWzkeKC6CxlhpjMyMhJQihvQ1IXwMFySMp3sMEQGo2GIG8DCbEnK9C0MDAw5MXMyH2BTR44LvRkYGK4vSZnesCRlOju6JMlhiOaTBwwMDBnRszN2UuJCdMCKzCHXwB8MDAyNDAwMGjFzMrcgS7BgV48X7GRgYMjAFSmMg77EBgAc+TshcDQUDgAAAABJRU5ErkJggg==" } );

	/* Sell Downloads Shortcode */
	blocks.registerBlockType( 'wcmp/woocommerce-music-player-playlist', {
		title: 'WooCommerce Music Player Playlist',
		icon: iconWCMPP,
		category: 'wcmp',
		customClassName: false,
		supports:{
			customClassName: false,
			className: false
		},
		attributes: {
			shortcode : {
				type : 'string',
				source : 'text',
				default: '[wcmp-playlist products_ids="*" controls="track" download_links="1"]'
			}
		},

		edit: function( props ) {
			var children = [], focus = props.isSelected;

			children.push(
				el('textarea',
					{
						key : 'wcmp_playlist_shortcode',
						value: props.attributes.shortcode,
						onChange: function(evt){
							props.setAttributes({shortcode: evt.target.value});
						},
						style: {width:"100%", resize: "vertical"}
					}
				)
			);

			children.push(
				el(
					'div', {className: 'wcmp-iframe-container', key:'wcmp_iframe_container', style:{position:'relative'}},
					el('div', {className: 'wcmp-iframe-overlay', key:'wcmp_iframe_overlay', style:{position:'absolute',top:0,right:0,bottom:0,left:0}}),
					el('iframe',
						{
							key: 'wcmp_iframe',
							src: wcmp_gutenberg_editor_config.url+encodeURIComponent(props.attributes.shortcode),
							height: 0,
							width: 500,
							scrolling: 'no'
						}
					)
				)
			);

			if(!!focus)
			{
				children.push(
					el(
						InspectorControls,
						{
							key : 'wcmp_playlist'
						},
                        el(
                            'div',
                            {
                                key: 'cp_inspector_container',
                                style:{paddingLeft:'15px',paddingRight:'15px'}
                            },
                            [
                                el(
                                    'b',
                                    {
                                        key: 'wcmp_inspector_help_main_attributes',
										style: { 'textTransform': 'uppercase' }
                                    },
									'Main playlist attributes',
									el(
										'hr',
										{
											key: 'wcmp_inspector_help_separator'
										}
									)
                                ),
                                el(
                                    'p',
                                    {
                                        key: 'wcmp_inspector_help_ids_attr'

                                    },
									el(
										'b',
										{
											key: 'wcmp_inspector_help_ids_attr_b'
										},
										'products_ids: '
									),
									wcmp_gutenberg_editor_config.ids_attr_description
                                ),
                                el(
                                    'p',
                                    {
                                        key: 'categories_attr_description_cat_attr'

                                    },
									el(
										'b',
										{
											key: 'categories_attr_description_cat_attr_b'
										},
										'product_categories: '
									),
									wcmp_gutenberg_editor_config.categories_attr_description
                                ),
                                el(
                                    'p',
                                    {
                                        key: 'tags_attr_description_tag_attr'

                                    },
									el(
										'b',
										{
											key: 'tags_attr_description_tag_attr_b'
										},
										'product_tags: '
									),
									wcmp_gutenberg_editor_config.tags_attr_description
                                ),
                                el(
                                    'p',
                                    {
                                        key   : 'wcmp_inspector_more_help',
                                        style : {fontWeight: 'bold'}
                                    },
                                    wcmp_gutenberg_editor_config.more_details
                                ),
                                el(
                                    'a',
                                    {
                                        key		: 'wcmp_inspector_help_link',
                                        href	: 'https://wcmp.dwbooster.com/documentation#playlist-shortcode',
                                        target	: '_blank',
										style   : {'marginBottom' : '20px', 'display' : 'block'}
                                    },
                                    'CLICK HERE'
                                ),
                            ]
                        )
					)
				);
			}
			return children;
		},

		save: function( props ) {
			return props.attributes.shortcode;
		}
	});
} )(
	window.wp.blocks,
	window.wp.element
);