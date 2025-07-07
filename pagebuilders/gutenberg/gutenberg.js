console.log('Bandfront Player block JS loaded');

window.wp = window.wp || {};
window.wp.domReady = window.wp.domReady || function(cb){cb();};

window.wp.domReady(function() {
	var blocks = window.wp.blocks;
	var element = window.wp.element;
	console.log('Registering block:', blocks, element);
	console.log('bfp_gutenberg_editor_config:', typeof bfp_gutenberg_editor_config !== 'undefined' ? bfp_gutenberg_editor_config : 'NOT SET');
	if (!blocks || !element) {
		console.error('wp.blocks or wp.element not available!');
		return;
	}
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
					el('iframe',
						{
							key: 'bfp_iframe',
							src: bfp_gutenberg_editor_config.url + '?bfp-preview=' + encodeURIComponent(props.attributes.shortcode),
							height: 400,
							width: '100%',
							scrolling: 'no',
							style: { border: 'none', display: 'block' },
							onLoad: function(e) {
								// Auto-resize iframe based on content
								try {
									var iframe = e.target;
									var doc = iframe.contentDocument || iframe.contentWindow.document;
									var height = doc.body.scrollHeight;
									if (height > 0) {
										iframe.style.height = height + 'px';
									}
								} catch(err) {
									console.log('Could not auto-resize iframe:', err);
								}
							}
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
									el('b', { key: 'bfp_inspector_help_ids_attr_b' }, 'product_tags: '),
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
});