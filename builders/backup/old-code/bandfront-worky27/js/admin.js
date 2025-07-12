function bfp_admin()
{
	if(typeof bfp_admin_evaluated != 'undefined') return;
	bfp_admin_evaluated = true;

	var $ = jQuery;

    // Special Radio
    $( document ).on(
        'mousedown',
        '.bfp_radio',
        function()
        {
            $(this).data('status', this.checked);
        }
    );

    $( document ).on(
        'click',
        '.bfp_radio',
        function()
        {
            this.checked = !$(this).data('status');
        }
    );

	// Delete buttons
	$( document ).on(
		'click',
		'.bfp-delete',
		function(evt)
		{
			evt.preventDefault();
			$(this).closest('tr').remove();
		}
	);

	// Add button
	$( document ).on(
		'click',
		'.bfp-add',
		function(evt)
		{
			evt.preventDefault();
			var row = '<tr><td><input type="text" class="bfp-file-name" placeholder="'+bfp['File Name']+'" name="_bfp_file_names[]" value="" /></td><td><input type="text" class="bfp-file-url" placeholder="http://" name="_bfp_file_urls[]" value="" /></td><td width="1%"><a href="#" class="btn btn-default button bfp-select-file">'+bfp['Choose file']+'</a></td><td width="1%"><a href="#" class="bfp-delete">'+bfp['Delete']+'</a></td></tr>';
			$(this).closest('table').find('tbody').append(row);
		}
	);

	//
	$( document ).on(
		'change',
		'[name="_bfp_own_demos"]',
		function()
		{
			$('.bfp-demo-files')[ ( this.checked ) ? 'show' : 'hide' ]();
		}
	);

	$('[name="_bfp_own_demos"]').trigger('change');

	// Select file button
	$( document ).on(
		'click',
		'.bfp-select-file',
		function(evt)
		{
			evt.preventDefault();
			var field = $(this).closest('tr').find('.bfp-file-url'),
				media = wp.media(
							{
								title: bfp['Select audio file'],
								library:{ type: 'audio' },
								button: { text: bfp['Select Item'] },
								multiple: false
							}
						).on(
							'select',
							(function( field ){
								return function() {
									var attachment = media.state().get('selection').first().toJSON(),
										url = attachment.url;
									field.val( url );
								};
							})( field )
						).open();
		}
	);

	// Main code
	$('.bfp-add').trigger('click');
}

jQuery(bfp_admin);
jQuery(window).on('load', bfp_admin);