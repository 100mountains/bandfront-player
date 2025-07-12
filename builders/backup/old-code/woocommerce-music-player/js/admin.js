function wcmp_admin()
{
	if(typeof wcmp_admin_evaluated != 'undefined') return;
	wcmp_admin_evaluated = true;

	var $ = jQuery;

    // Special Radio
    $( document ).on(
        'mousedown',
        '.wcmp_radio',
        function()
        {
            $(this).data('status', this.checked);
        }
    );

    $( document ).on(
        'click',
        '.wcmp_radio',
        function()
        {
            this.checked = !$(this).data('status');
        }
    );

	// Delete buttons
	$( document ).on(
		'click',
		'.wcmp-delete',
		function(evt)
		{
			evt.preventDefault();
			$(this).closest('tr').remove();
		}
	);

	// Add button
	$( document ).on(
		'click',
		'.wcmp-add',
		function(evt)
		{
			evt.preventDefault();
			var row = '<tr><td><input type="text" class="wcmp-file-name" placeholder="'+wcmp['File Name']+'" name="_wcmp_file_names[]" value="" /></td><td><input type="text" class="wcmp-file-url" placeholder="http://" name="_wcmp_file_urls[]" value="" /></td><td width="1%"><a href="#" class="btn btn-default button wcmp-select-file">'+wcmp['Choose file']+'</a></td><td width="1%"><a href="#" class="wcmp-delete">'+wcmp['Delete']+'</a></td></tr>';
			$(this).closest('table').find('tbody').append(row);
		}
	);

	//
	$( document ).on(
		'change',
		'[name="_wcmp_own_demos"]',
		function()
		{
			$('.wcmp-demo-files')[ ( this.checked ) ? 'show' : 'hide' ]();
		}
	);

	$('[name="_wcmp_own_demos"]').trigger('change');

	// Select file button
	$( document ).on(
		'click',
		'.wcmp-select-file',
		function(evt)
		{
			evt.preventDefault();
			var field = $(this).closest('tr').find('.wcmp-file-url'),
				media = wp.media(
							{
								title: wcmp['Select audio file'],
								library:{ type: 'audio' },
								button: { text: wcmp['Select Item'] },
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
	$('.wcmp-add').trigger('click');
}

jQuery(wcmp_admin);
jQuery(window).on('load', wcmp_admin);