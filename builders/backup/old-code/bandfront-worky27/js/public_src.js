(function(){
	var bfp_players = [],
		bfp_player_counter = 0;

	window['generate_the_bfp'] = function(isOnLoadEvent)
	{
		if(
			typeof isOnLoadEvent !== 'boolean' &&
			typeof bfp_global_settings != 'undefined' &&
			bfp_global_settings['onload']*1
		) return;

		if('undefined' !== typeof generated_the_bfp) return;
		generated_the_bfp = true;

		var $ = jQuery;
        $('.bfp-player-container').on('click', '*', function(evt){evt.preventDefault();evt.stopPropagation();return false;}).parent().removeAttr('title');

		function _hideShowPlayersAndPositioning( player ){
			let single = player.closest('.bfp-single-player'),
				first  = single.find('.bfp-first-player');

			$('audio').each( function(){
				this.pause();
				this.currentTime = 0;
			});

			single.find('.bfp-player-container:not(.bfp-first-player)').hide();
			if(! player.hasClass('.bfp-first-player')) {
				player.show().offset(first.offset()).outerWidth(first.outerWidth());
				player.find('audio')[0].play();
			}
		}

		/**
		 * Play next player
		 */
		function _playNext( playernumber, loop )
		{
			if( playernumber+1 < bfp_player_counter || loop)
			{

				var toPlay = playernumber+1;
                if(
                    loop &&
                    (
                        toPlay == bfp_player_counter ||
                        $('[playernumber="'+toPlay+'"]').closest('[data-loop]').length == 0 ||
                        $('[playernumber="'+toPlay+'"]').closest('[data-loop]')[0] != $('[playernumber="'+playernumber+'"]').closest('[data-loop]')[0]
                    )
                )
                {
                    toPlay = $('[playernumber="'+playernumber+'"]').closest('[data-loop]').find('[playernumber]:first').attr('playernumber');
                }

				if( bfp_players[ toPlay ] instanceof $ && bfp_players[ toPlay ].is( 'a' ) ) {
					if(bfp_players[ toPlay ].closest('.bfp-single-player').length) {
						_hideShowPlayersAndPositioning( bfp_players[ toPlay ].closest('.bfp-player-container') );
					} else if(bfp_players[ toPlay ].is(':visible')) bfp_players[ toPlay ].trigger('click');
					else _playNext(playernumber+1, loop);
				} else {
					if($(bfp_players[ toPlay ].domNode).closest('.bfp-single-player').length) {
						_hideShowPlayersAndPositioning( $(bfp_players[ toPlay ].domNode).closest('.bfp-player-container') );
					} else if($(bfp_players[ toPlay ].domNode).closest('.bfp-player-container').is(':visible')) bfp_players[ toPlay ].domNode.play();
					else _playNext(playernumber+1, loop);
				}
			}
		};

		function _setOverImage(p)
		{
			var i = p.data('product');
			$('[data-product="'+i+'"]').each(function(){
				var e = $(this),
					p = e.closest('.product'),
					t = p.find('img.product-'+i);
				if(
					t.length &&
					p.closest('.bfp-player-list').length == 0 &&
					p.find('.bfp-player-list').length == 0
				)
				{
					var o = t.offset(),
						c = p.find('div.bfp-player');

					if(c.length){
						c.css({'position': 'absolute', 'z-index': 999999})
						 .offset({'left': o.left+(t.width()-c.width())/2, 'top': o.top+(t.height()-c.height())/2});
					}
				}
			});
		};

		$.expr.pseudos.regex = function(elem, index, match) {
			var matchParams = match[3].split(','),
				validLabels = /^(data|css):/,
				attr = {
					method: matchParams[0].match(validLabels) ?
								matchParams[0].split(':')[0] : 'attr',
					property: matchParams.shift().replace(validLabels,'')
				},
				regexFlags = 'ig',
				regex = new RegExp(matchParams.join('').replace(/^\s+|\s+$/g,''), regexFlags);
			return regex.test($(elem)[attr.method](attr.property));
		}

		//------------------------ MAIN CODE ------------------------
		var play_all = (typeof bfp_global_settings != 'undefined') ? bfp_global_settings[ 'play_all' ] : true, // Play all songs
			pause_others = (typeof bfp_global_settings != 'undefined') ? !(bfp_global_settings['play_simultaneously']*1) : true,
			fade_out = (typeof bfp_global_settings != 'undefined') ? bfp_global_settings['fade_out']*1 : true,
			ios_controls = (
				typeof bfp_global_settings != 'undefined' &&
				('ios_controls' in bfp_global_settings) &&
				bfp_global_settings['ios_controls']*1
			) ? true : false,
			s = $('audio.bfp-player:not(.track):not([playernumber])'),
			m = $('audio.bfp-player.track:not([playernumber])'),
			c = {
					pauseOtherPlayers: pause_others,
					iPadUseNativeControls: ios_controls,
					iPhoneUseNativeControls: ios_controls,
					success: function( media, dom ){
                        var duration = $(dom).data('duration'),
                            estimated_duration = $(dom).data('estimated_duration'),
                            player_index = $(dom).attr('playernumber');

                        if(typeof estimated_duration != 'undefined')
                        {
                            media.getDuration = function(){
                                return estimated_duration;
                            };
                        }

						if(typeof duration != 'undefined')
                        {
                            setTimeout((function(player_index, duration){
                                return function(){
                                    bfp_players[ player_index ].updateDuration = function(){
                                        $(this.media).closest('.bfp-player')
                                         .find('.mejs-duration')
                                         .html(duration);
                                    };
                                    bfp_players[ player_index ].updateDuration();
                                };
                            })(player_index, duration), 50);
                        }

						if($(dom).attr('volume'))
                        {
                            media.setVolume(parseFloat($(dom).attr('volume')));
                            if(media.volume == 0) media.setMuted(true);
                        }

						media.addEventListener( 'playing', function( evt ){
							var e = $(media), s = e.closest( '.bfp-single-player' ), c;
							try{
								let t = evt.detail.target,
									p = $(t).attr('data-product');

								if( typeof p != 'undefined' ) {
									let url = window.location.protocol + '//' +
										window.location.host + '/'+
										window.location.pathname.replace(new RegExp('^\/', 'g'), '').replace(new RegExp('\/$','g'), '')+
										'?bfp-action=play&bfp-product='+p;
									$.get(url);
								}
							}catch(err){}
							if( s.length ) {
								c = e.closest('.bfp-player-container').attr('data-player-id');
								s.find('.bfp-player-title[data-player-id="'+c+'"]').addClass('bfp-playing');
							}
						});

						media.addEventListener( 'timeupdate', function( evt ){
							var e = media, duration = e.getDuration();
							if(!isNaN( e.currentTime ) && !isNaN( duration ))
							{
								if( fade_out && duration - e.currentTime < 4 )
								{
									e.setVolume( e.volume - e.volume / 3 );
								}
								else
								{
									if(e.currentTime)
                                    {
                                        if(typeof e[ 'bkVolume' ] == 'undefined' )
                                            e[ 'bkVolume' ] = parseFloat( $(e).find('audio,video').attr('volume') || e.volume);
                                        e.setVolume( e.bkVolume );
                                        if(e.bkVolume == 0) e.setMuted(true);
                                    }
								}

							}
						});

						media.addEventListener( 'volumechange', function( evt ){
							var e = media, duration = e.getDuration();
							if(!isNaN( e.currentTime ) && !isNaN(duration ))
							{
								if( ( duration - e.currentTime > 4 || !fade_out) && e.currentTime )  e[ 'bkVolume' ] = e.volume;
							}
						});

						media.addEventListener( 'ended', function( evt ){
							var e = $(media),
                                c = e.closest('[data-loop="1"]');

                             e[0].currentTime = 0;

							if( e.closest( '.bfp-single-player' ).length ) {
								e.closest( '.bfp-single-player' ).find('.bfp-playing').removeClass('bfp-playing');
							}

							if( play_all*1 || c.length)
							{
								var playernumber = e.attr('playernumber')*1;
                                if(isNaN(playernumber))
                                    playernumber = e.find('[playernumber]').attr('playernumber')*1;
                                _playNext( playernumber, c.length);
							}
						});
					}
				},
			selector = '.product-type-grouped :regex(name,quantity\\[\\d+\\])';
		s.each(function(){
			var e 	= $(this),
				src = e.find( 'source' ).attr( 'src' );

			e.attr('playernumber', bfp_player_counter);

			c['audioVolume'] = 'vertical';
			try{
				bfp_players[ bfp_player_counter ] = new MediaElementPlayer(e[0], c);
			}
			catch(err)
			{
				if('console' in window) console.log(err);
			}

			bfp_player_counter++;
			/* _setOverImage(e); */
		});


		m.each(function(){
			var e = $(this),
				src = e.find( 'source' ).attr( 'src' );

			e.attr('playernumber', bfp_player_counter);

			c['features'] = ['playpause'];
			try{
				bfp_players[ bfp_player_counter ] = new MediaElementPlayer(e[0], c);
			}
			catch(err)
			{
				if('console' in window) console.log(err);
			}

			bfp_player_counter++;
			_setOverImage(e);
			$(window).on('resize', function(){_setOverImage(e);});
		});

		if(!$(selector).length) selector = '.product-type-grouped [data-product_id]';
		if(!$(selector).length) selector = '.woocommerce-grouped-product-list [data-product_id]';
		if(!$(selector).length) selector = '.woocommerce-grouped-product-list [id*="product-"]';

		$(selector).each(function(){
			try
			{
				var e = $(this),
					i = (e.data( 'product_id' )||e.attr('name')||e.attr('id')).replace(/[^\d]/g,''),
					c = $( '.bfp-player-list.merge_in_grouped_products .product-'+i+':first .bfp-player-title' ), /* Replaced :last with :first 2018.06.12 */
					t = $('<table></table>');

				if(c.length && !c.closest('.bfp-first-in-product').length)
				{
					c.closest('tr').addClass('bfp-first-in-product'); /* To identify the firs element in the product */
					if(c.closest('form').length == 0)
					{
						c.closest('.bfp-player-list').prependTo(e.closest('form'));
					}
					t.append(e.closest('tr').prepend('<td>'+c.html()+'</td>'));
					c.html('').append(t);
				}
			}
			catch(err){}
		});

		// Single-player
		$(document).on('click', '[data-player-id]', function(){
			let e = $(this), s = e.closest('.bfp-single-player'), c;
			if(s.length){
				$('.bfp-player-title').removeClass('bfp-playing');
				c = e.attr('data-player-id');
				e.addClass('bfp-playing');
				_hideShowPlayersAndPositioning( s.find( '.bfp-player-container[data-player-id="'+c+'"]' ) );
			}
		});
	}

	window['bfp_force_init'] = function()
	{
		delete window.generated_the_bfp;
		generate_the_bfp(true);
	}

	jQuery(generate_the_bfp);
	jQuery(window).on('load', function(){
		generate_the_bfp(true);
		var $ = jQuery,
			ua = window.navigator.userAgent;

		$('[data-lazyloading]').each(function(){ var e = $(this); e.attr('preload', e.data('lazyloading'));});
		if(ua.match(/iPad/i) || ua.match(/iPhone/i))
		{
			var p = (typeof bfp_global_settings != 'undefined') ? bfp_global_settings[ 'play_all' ] : true;
			if(p) // Solution to the play all in Safari iOS
			{
				$('.bfp-player .mejs-play button').one('click', function(){

					if('undefined' != typeof bfp_preprocessed_players) return;
					bfp_preprocessed_players = true;

					var e = $(this);
					$('.bfp-player audio').each(function(){
						this.play();
						this.pause();
					});
					setTimeout(function(){e.trigger('click');}, 500);
				});
			}
		}
	}).on('popstate', function(){
		if(jQuery('audio[data-product]:not([playernumber])').length) bfp_force_init();
	});

	jQuery(document).on('scroll wpfAjaxSuccess woof_ajax_done yith-wcan-ajax-filtered wpf_ajax_success berocket_ajax_products_loaded berocket_ajax_products_infinite_loaded lazyload.wcpt', bfp_force_init);
})()