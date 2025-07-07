( function( blocks, element ) {
	var el = element.createElement,
		InspectorControls = ('blockEditor' in wp) ? wp.blockEditor.InspectorControls : wp.editor.InspectorControls;

	blocks.registerBlockType( 'bfp/bandfront-player-playlist', {
		edit: function( props ) {
			var children = [], focus = props.isSelected;

			children.push(
				el('textarea',
					{
						key : 'bfp_playlist_shortcode',
						value: props.attributes.shortcode,
						onChange: function(evt){
							props.setAttributes({shortcode: evt.target.value});
						},
						className: 'bfp-playlist-shortcode-input'
					}
				)
			);

			children.push(
				el(
					'div', {className: 'bfp-iframe-container', key:'bfp_iframe_container'},
					el('div', {className: 'bfp-iframe-overlay', key:'bfp_iframe_overlay'}),
					el('iframe',
						{
							key: 'bfp_iframe',
							src: bfp_gutenberg_editor_config.url+encodeURIComponent(props.attributes.shortcode),
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
						{ key : 'bfp_playlist' },
						el(
							'div',
							{ key: 'cp_inspector_container' },
							[
								el(
									'b',
									{ key: 'bfp_inspector_help_main_attributes', style: { 'textTransform': 'uppercase' } },
									'Main playlist attributes',
									el('hr', { key: 'bfp_inspector_help_separator' })
								),
								el(
									'p',
									{ key: 'bfp_inspector_help_ids_attr' },
									el('b', { key: 'bfp_inspector_help_ids_attr_b' }, 'products_ids: '),
									bfp_gutenberg_editor_config.ids_attr_description
								),
								el(
									'p',
									{ key: 'categories_attr_description_cat_attr' },
									el('b', { key: 'categories_attr_description_cat_attr_b' }, 'product_categories: '),
									bfp_gutenberg_editor_config.categories_attr_description
								),
								el(
									'p',
									{ key: 'tags_attr_description_tag_attr' },
									el('b', { key: 'tags_attr_description_tag_attr_b' }, 'product_tags: '),
									bfp_gutenberg_editor_config.tags_attr_description
								),
								el(
									'p',
									{ key   : 'bfp_inspector_more_help', style : {fontWeight: 'bold'} },
									bfp_gutenberg_editor_config.more_details
								),
								el(
									'a',
									{
										key		: 'bfp_inspector_help_link',
										href	: 'https://therob.lol/shortcodes',
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