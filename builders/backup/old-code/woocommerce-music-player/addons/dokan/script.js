jQuery(
	function(){
		var $ = jQuery;
		// Add button
		$( document ).on(
			'click',
			'.wcmp-dokan-add',
			function(evt)
			{
				evt.preventDefault();
				var row = '<tr><td><input type="text" class="wcmp-file-name" placeholder="'+wcmp['File Name']+'" name="_wcmp_file_names[]" value="" /></td><td><input type="text" class="wcmp-file-url" placeholder="http://" name="_wcmp_file_urls[]" value="" /><a href="#" class="wcmp-select-file dokan-btn dokan-btn-sm dokan-btn-default">'+wcmp['Choose file']+'</a></td><td><a href="#" class="wcmp-delete dokan-btn dokan-btn-sm dokan-btn-danger">'+wcmp['Delete']+'</a></td></tr>';
				$(this).closest('table').find('tbody').append(row);
			}
		);
	}
);