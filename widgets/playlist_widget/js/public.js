jQuery( window ).on(
	'load',
	function()
	{
		function setCookie( value )
		{
			var expires = "expires="+ctime;
			document.cookie = cname + "=" + value + "; " + expires;
		}

		function deleteCookie()
		{
			document.cookie = cname+"=; expires=Thu, 01 Jan 1970 00:00:00 UTC";
		}

		function getCookie()
		{
			var name = cname + "=";
			var ca = document.cookie.split(';');
			for(var i = 0; i < ca.length; i++) {
				var c = ca[i];
				while (c.charAt(0) == ' ') {
					c = c.substring(1);
				}
				if (c.indexOf(name) == 0) {
					return c.substring(name.length, c.length);
				}
			}
			return "";
		}

		// Get history
		var $ 		= jQuery,
			cname 	= 'bfp_playing',
			ctime 	= 0,
			continue_playing = false,
			cookie 	= getCookie(),
			parts,
			player;

		if(typeof bfp_widget_settings != 'undefined')
			if('continue_playing' in bfp_widget_settings)
				continue_playing = bfp_widget_settings['continue_playing'];

		if(continue_playing)
		{
			if( !/^\s*$/.test( cookie ) )
			{
				parts  = cookie.split( '||' );
				if( parts.length == 2 )
				{
					player = $( '#'+parts[ 0 ] );
					if( player.length )
					{
						player[0].currentTime = parts[1];
						player[0].play();
					}
				}
			}

			// Set events
			$( '.bfp-player audio' )
			.on(
				'timeupdate',
				function()
				{
					if(!isNaN( this.currentTime ) && this.currentTime)
					{
						var id = $( this ).attr( 'id' );
						setCookie( id+'||'+this.currentTime );
					}
				}
			)
			.on(
				'ended pause',
				function()
				{
					deleteCookie();
				}
			);
		}

		// Download multiple.
		$( document ).on( 'click', '.bfp-download-link', function( evt ) {
			let e = $( evt.target );
			let files = e.attr( 'data-download-links' );

			if ( files ) {
				files = JSON.parse( files );
				if ( Array.isArray( files ) ) {
					for ( let i in files ) {
						let link = document.createElement('a');
						link.href = files[i];
						link.download = files[i];
						document.body.appendChild(link);
						link.click();
						document.body.removeChild(link);
					}
				}
			}
		});
	}
);