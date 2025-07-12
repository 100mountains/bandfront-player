jQuery(function(){
	jQuery('.wc-block-all-products').each(
		function()
		{
			(new MutationObserver(
				function(mutationsList, observer)
				{
					for(let k in mutationsList)
					{
						let mutation = mutationsList[k];
						if (mutation.type === 'childList')
						{
							if(mutation.addedNodes.length)
							{
								try{
									var l = jQuery('.wc-block-grid__product-title:hidden', '.wc-block-all-products');
									if(l.length)
									{
										l.each(function(){
											var e = jQuery('a',this);
											if(e.length) e.html(e.text()).parent().show();
										});
										wcmp_force_init();
									}
								}catch(err){}
							}
						}
					}
				}
			)).observe(this, { childList: true, subtree: true });
		}
	);
});