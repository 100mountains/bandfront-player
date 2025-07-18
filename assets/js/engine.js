/**
 * Bandfront Player Engine
 * Handles audio player functionality on the frontend
 *
 */
// Polyfill for Array.prototype.find (for older browsers)
if (!Array.prototype.find) {
    Array.prototype.find = function(predicate) {
        if (this == null) {
            throw new TypeError('Array.prototype.find called on null or undefined');
        }
        if (typeof predicate !== 'function') {
            throw new TypeError('predicate must be a function');
        }
        var list = Object(this);
        var length = list.length >>> 0;
        var thisArg = arguments[1];
        var value;

        for (var i = 0; i < length; i++) {
            value = list[i];
            if (predicate.call(thisArg, value, i, list)) {
                return value;
            }
        }
        return undefined;
    };
}

// WaveSurfer.js specific functions
function smoothFadeOut(mediaElement, fadeTime) {
    fadeTime = fadeTime || 2000;
    
    if (!mediaElement.bkVolume) {
        mediaElement.bkVolume = mediaElement.volume;
    }
    
    var startVolume = mediaElement.volume;
    var startTime = Date.now();
    
    function fadeStep() {
        var elapsed = Date.now() - startTime;
        var progress = Math.min(elapsed / fadeTime, 1);
        var easedProgress = 1 - Math.pow(1 - progress, 3);
        var currentVolume = startVolume * (1 - easedProgress);
        
        mediaElement.setVolume(Math.max(currentVolume, 0));
        
        if (progress < 1) {
            requestAnimationFrame(fadeStep);
        }
    }
    
    requestAnimationFrame(fadeStep);
}

function initWaveSurferPlayer(container, audioUrl, options) {
    options = options || {};
    
    var $audio = jQuery(container).find('audio');
    var audioId = $audio.attr('id') || 'wavesurfer-' + Math.random().toString(36).substr(2, 9);
    var volume = parseFloat($audio.attr('volume')) || 1;
    
    $audio.hide();
    
    var waveformId = 'waveform-' + audioId;
    var $waveform = jQuery('<div id="' + waveformId + '" class="bfp-waveform"></div>');
    jQuery(container).append($waveform);
    
    var wavesurfer = WaveSurfer.create({
        container: '#' + waveformId,
        waveColor: '#999',
        progressColor: '#000',
        cursorColor: '#333',
        backend: 'WebAudio',
        normalize: true,
        responsive: true,
        height: 60,
        barWidth: 2,
        barGap: 1,
        volume: volume
    });

    wavesurfer.load(audioUrl);
    wavesurfer.bkVolume = volume;

    // Add MediaElement-compatible interface
    wavesurfer.play = function() { wavesurfer.play(); };
    wavesurfer.pause = function() { wavesurfer.pause(); };
    wavesurfer.setVolume = function(v) { wavesurfer.setVolume(v); };
    wavesurfer.setMuted = function(m) { wavesurfer.setMute(m); };
    
    // Smooth fade for WaveSurfer
    wavesurfer.on('audioprocess', function() {
        if (wavesurfer.isPlaying()) {
            var remaining = wavesurfer.getDuration() - wavesurfer.getCurrentTime();
            
            if (window.fadeOut && remaining < 4 && remaining > 0 && !wavesurfer.fadeStarted) {
                wavesurfer.fadeStarted = true;
                var fadeTime = remaining * 1000;
                var startVolume = wavesurfer.getVolume();
                var startTime = Date.now();
                
                function fade() {
                    var elapsed = Date.now() - startTime;
                    var progress = Math.min(elapsed / fadeTime, 1);
                    var easedProgress = 1 - Math.pow(1 - progress, 3);
                    var currentVolume = startVolume * (1 - easedProgress);
                    
                    wavesurfer.setVolume(Math.max(currentVolume, 0));
                    
                    if (progress < 1 && wavesurfer.isPlaying()) {
                        requestAnimationFrame(fade);
                    }
                }
                
                requestAnimationFrame(fade);
            }
        }
    });

    wavesurfer.on('seek', function() {
        wavesurfer.fadeStarted = false;
        if (wavesurfer.bkVolume) {
            wavesurfer.setVolume(wavesurfer.bkVolume);
        }
    });
    
    wavesurfer.on('play', function() {
        wavesurfer.fadeStarted = false;
        if (wavesurfer.bkVolume && wavesurfer.getCurrentTime() < 1) {
            wavesurfer.setVolume(wavesurfer.bkVolume);
        }
    });
    
    return wavesurfer;
}

(function() {
    // Global variables
    var bfp_players = [];
    var bfp_player_counter = 0;
    var audioEngine = (typeof bfp_global_settings !== 'undefined') ? 
        bfp_global_settings.audio_engine : 'html5';  // Changed default from 'mediaelement' to 'html5'
    window.fadeOut = (typeof bfp_global_settings !== 'undefined') ? 
        (1 * bfp_global_settings.fade_out) : true;
    
    // Define the missing groupedSelector variable
    var groupedSelector = '.group_cart_control_products';

    // FIX: Ensure MediaElement.js compatibility
    function smoothFadeOutMediaElement(mediaElement, fadeTime) {
        fadeTime = fadeTime || 2000;
        
        if (!mediaElement.bkVolume) {
            mediaElement.bkVolume = mediaElement.volume || 1;
        }
        
        var startVolume = mediaElement.volume || mediaElement.bkVolume;
        var startTime = Date.now();
        
        function fadeStep() {
            var elapsed = Date.now() - startTime;
            var progress = Math.min(elapsed / fadeTime, 1);
            var easedProgress = 1 - Math.pow(1 - progress, 3);
            var currentVolume = startVolume * (1 - easedProgress);
            
            // Use setVolume method if available, otherwise direct property
            if (typeof mediaElement.setVolume === 'function') {
                mediaElement.setVolume(Math.max(currentVolume, 0));
            } else {
                mediaElement.volume = Math.max(currentVolume, 0);
            }
            
            if (progress < 1) {
                requestAnimationFrame(fadeStep);
            }
        }
        
        requestAnimationFrame(fadeStep);
    }

    /**
     * Main initialization function for BFP players
     */
    window.generate_the_bfp = function(forceInit) {
        /**
         * Hide/show players and handle positioning for single player mode
         */
        function _hideShowPlayersAndPositioning(playerContainer) {
            var singlePlayerContainer = playerContainer.closest(".bfp-single-player");
            var firstPlayer = singlePlayerContainer.find(".bfp-first-player");
            
            // Pause all audio elements
            jQuery("audio").each(function() {
                this.pause();
                this.currentTime = 0;
            });
            
            // Hide all player containers except the first
            singlePlayerContainer
                .find(".bfp-player-container:not(.bfp-first-player)")
                .hide();
                
            // If not the first player, show and position it
            if (!playerContainer.hasClass(".bfp-first-player")) {
                playerContainer
                    .show()
                    .offset(firstPlayer.offset())
                    .outerWidth(firstPlayer.outerWidth());
                playerContainer.find("audio")[0].play();
            }
        }

        /**
         * Play next player in sequence
         */
        function _playNext(playernumber, loop) {
            console.log('[BFP Debug] _playNext called - Current player: ' + playernumber + ', Loop: ' + loop);
            
            if (playernumber + 1 < bfp_player_counter || loop) {
                var toPlay = playernumber + 1;
                
                // Handle looping
                if (loop && (
                    toPlay == bfp_player_counter ||
                    jQuery('[playernumber="' + toPlay + '"]').closest("[data-loop]").length == 0 ||
                    jQuery('[playernumber="' + toPlay + '"]').closest("[data-loop]")[0] != 
                    jQuery('[playernumber="' + playernumber + '"]').closest("[data-loop]")[0]
                )) {
                    toPlay = jQuery('[playernumber="' + playernumber + '"]')
                        .closest("[data-loop]")
                        .find("[playernumber]:first")
                        .attr("playernumber");
                }

                // Handle button players (track controls)
                if (bfp_players[toPlay] instanceof jQuery && bfp_players[toPlay].is("a")) {
                    if (bfp_players[toPlay].closest(".bfp-single-player").length) {
                        _hideShowPlayersAndPositioning(bfp_players[toPlay].closest(".bfp-player-container"));
                    } else if (bfp_players[toPlay].is(":visible")) {
                        bfp_players[toPlay].trigger("click");
                    } else {
                        _playNext(playernumber + 1, loop);
                    }
                } 
                // Handle full players
                else {
                    var $nextPlayer = jQuery(bfp_players[toPlay].domNode || bfp_players[toPlay]);
                    var nextTitle = $nextPlayer.closest('.bfp-player-container').siblings('.bfp-player-title').text() || 
                                   $nextPlayer.closest('tr').find('.bfp-player-title').text() || 
                                   'Unknown Track';
                    console.log('[BFP Debug] Switching to next track: "' + nextTitle + '" (Player #' + toPlay + ')');
                    
                    if (jQuery(bfp_players[toPlay].domNode).closest('.bfp-single-player').length) {
                        _hideShowPlayersAndPositioning(jQuery(bfp_players[toPlay].domNode).closest('.bfp-player-container'));
                    } else if (jQuery(bfp_players[toPlay].domNode).closest('.bfp-player-container').is(':visible')) {
                        // Handle different player types
                        if (bfp_players[toPlay].domNode && bfp_players[toPlay].domNode.play) {
                            bfp_players[toPlay].domNode.play();
                        } else if (bfp_players[toPlay].play) {
                            bfp_players[toPlay].play();
                        }
                    } else {
                        _playNext(playernumber + 1, loop);
                    }
                }
            }
        }

        /**
         * Play previous player in sequence
         */
        function _playPrev(playernumber, loop) {
            console.log('[BFP Debug] _playPrev called - Current player: ' + playernumber + ', Loop: ' + loop);
            
            if (playernumber - 1 >= 0 || loop) {
                var toPlay = playernumber - 1;

                // Handle looping
                if (loop && (
                    toPlay < 0 ||
                    jQuery('[playernumber="' + toPlay + '"]').closest('[data-loop]').length == 0 ||
                    jQuery('[playernumber="' + toPlay + '"]').closest('[data-loop]')[0] != 
                    jQuery('[playernumber="' + playernumber + '"]').closest('[data-loop]')[0]
                )) {
                    toPlay = jQuery('[playernumber="' + playernumber + '"]')
                        .closest('[data-loop]')
                        .find('[playernumber]:last')
                        .attr('playernumber');
                }

                // Handle button players
                if (bfp_players[toPlay] instanceof jQuery && bfp_players[toPlay].is('a')) {
                    var nextTitle = bfp_players[toPlay].closest('.bfp-player-container').siblings('.bfp-player-title').text() || 
                                   bfp_players[toPlay].closest('tr').find('.bfp-player-title').text() || 
                                   'Unknown Track';
                    console.log('[BFP Debug] Switching to next track: "' + nextTitle + '" (Player #' + toPlay + ')');
                    
                    if (bfp_players[toPlay].closest('.bfp-single-player').length) {
                        _hideShowPlayersAndPositioning(bfp_players[toPlay].closest('.bfp-player-container'));
                    } else if (bfp_players[toPlay].is(':visible')) {
                        bfp_players[toPlay].trigger('click');
                    } else {
                        _playNext(playernumber + 1, loop);
                    }
                }
                // Handle full players
                else {
                    if (jQuery(bfp_players[toPlay].domNode).closest('.bfp-single-player').length) {
                        _hideShowPlayersAndPositioning(jQuery(bfp_players[toPlay].domNode).closest('.bfp-player-container'));
                    } else if (jQuery(bfp_players[toPlay].domNode).closest('.bfp-player-container').is(':visible')) {
                        // Handle different player types
                        if (bfp_players[toPlay].domNode && bfp_players[toPlay].domNode.play) {
                            bfp_players[toPlay].domNode.play();
                        } else if (bfp_players[toPlay].play) {
                            bfp_players[toPlay].play();
                        }
                    } else {
                        _playPrev(playernumber - 1, loop);
                    }
                }
            }
        }

        /**
         * Position player over product image
         */
        function _setOverImage(player) {
            var productId = player.data('product');
            jQuery('[data-product="' + productId + '"]').each(function() {
                var element = jQuery(this);
                var product = element.closest('.product');
                var productImage = product.find('img.product-' + productId);
                
                if (productImage.length && 
                    product.closest('.bfp-player-list').length == 0 &&
                    product.find('.bfp-player-list').length == 0) {
                    
                    var imageOffset = productImage.offset();
                    var playerDiv = product.find('div.bfp-player');

                    if (playerDiv.length) {
                        playerDiv.css({
                            'position': 'absolute', 
                            'z-index': 999999
                        }).offset({
                            'left': imageOffset.left + (productImage.width() - playerDiv.width()) / 2, 
                            'top': imageOffset.top + (productImage.height() - playerDiv.height()) / 2
                        });
                    }
                }
            });
        }

        // Check if already initialized or if we should skip initialization
        if (!(typeof forceInit !== 'boolean' && 
            typeof bfp_global_settings !== 'undefined' && 
            1 * bfp_global_settings.onload) && 
            typeof generated_the_bfp === 'undefined') {
            
            generated_the_bfp = true;
            var $ = jQuery;
            
            // Prevent clicks from bubbling up from player container
            $(".bfp-player-container")
                .on("click", "*", function(event) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    // Send play event to server
                    var playerContainer = $(this).closest('.bfp-player-container');
                    var ajaxData = {
                        action: 'bfp_track_playback',
                        nonce: playerContainer.data('nonce'),
                        product_id: playerContainer.data('product'),
                        file_index: playerContainer.data('file-index') || 0,
                        track_title: playerContainer.siblings('.bfp-player-title').text() || 'Unknown Track',
                        event_type: 'play'
                    };
                    
                    if (typeof ajaxurl !== 'undefined') {
                        $.post(ajaxurl, ajaxData, function(response) {
                            if (response.success) {
                                console.log('[BFP Debug] Play event tracked:', ajaxData);
                            } else {
                                console.error('[BFP Debug] Play event failed to track:', response);
                            }
                        });
                    }
                })
                .parent()
                .removeAttr("title");
                
            // Add regex pseudo selector for jQuery
            $.expr.pseudos.regex = function(elem, index, match) {
                var matchParams = match[3].split(',');
                var validLabels = /^(data|css):/;
                var attr = matchParams[0].match(validLabels) ? 
                    matchParams[0].split(':')[0] : 'attr';
                var property = matchParams.shift().replace(validLabels, '');
                var regexFlags = matchParams.join('').replace(/^\s+|\s+$/g, '');
                var regex = new RegExp(matchParams.join('').replace(/^\s+|\s+$/g, ''), 'ig');
                return regex.test($(elem)[attr](property));
            };

            // Configuration from global settings
            var playAll = (typeof bfp_global_settings !== 'undefined') ? 
                bfp_global_settings.play_all : true;
            var pauseOthers = (typeof bfp_global_settings !== 'undefined') ? 
                !(1 * bfp_global_settings.bfp_allow_concurrent_audio) : true;
            var fadeOut = (typeof bfp_global_settings !== 'undefined') ? 
                (1 * bfp_global_settings.fade_out) : true;
            var iosNativeControls = (typeof bfp_global_settings !== 'undefined' && 
                'ios_controls' in bfp_global_settings && 
                1 * bfp_global_settings.ios_controls) ? true : false;

            // Select players
            var fullPlayers = $("audio.bfp-player:not(.track):not([playernumber])");
            var buttonPlayers = $("audio.bfp-player.track:not([playernumber])");
            
            // ENHANCED: Better engine detection and fallback
            var useWaveSurfer = (audioEngine === 'wavesurfer' && typeof WaveSurfer !== 'undefined');
            var useHTML5 = (audioEngine === 'html5');
            var useMediaElement = (audioEngine === 'mediaelement' && typeof MediaElementPlayer !== 'undefined');
            
            if (!useWaveSurfer && audioEngine === 'wavesurfer') {
                console.warn('WaveSurfer.js not available, falling back to HTML5');
                audioEngine = 'html5';
                useHTML5 = true;
            }
            
            if (!useMediaElement && audioEngine === 'mediaelement') {
                console.warn('MediaElement.js not available, falling back to HTML5');
                audioEngine = 'html5';
                useHTML5 = true;
            }

            if (useHTML5) {
                // HTML5 Native Audio initialization
                fullPlayers.each(function() {
                    var $player = $(this);
                    $player.attr("playernumber", bfp_player_counter);
                    
                    // Ensure native controls are shown
                    $player[0].controls = true;
                    $player[0].style.width = '100%';
                    
                    // Store reference to native audio element
                    bfp_players[bfp_player_counter] = $player[0];
                    
                    // Set initial volume
                    var initialVolume = $player.attr("volume");
                    if (initialVolume) {
                        $player[0].volume = parseFloat(initialVolume);
                    }
                    
                    // Track play events
                    $player.on('play', function() {
                        var $container = $player.closest('.bfp-player-container');
                        var productId = $player.attr("data-product") || $container.data('product');
                        var trackTitle = $container.siblings('.bfp-player-title').text() || 
                                        $player.closest('tr').find('.bfp-player-title').text() || 
                                        'Unknown Track';
                        var fileIndex = $player.attr("data-file-index") || $container.data('file-index') || '0';
                        
                        console.log('[BFP Debug] PLAY pressed - Product ID: ' + productId + ', Track: "' + trackTitle + '", File Index: ' + fileIndex);
                        
                        // Send AJAX request to track playback
                        if (productId && typeof ajaxurl !== 'undefined') {
                            var ajaxData = {
                                action: 'bfp_track_playback',
                                nonce: $container.data('nonce') || bfp_global_settings.nonce,
                                product_id: productId,
                                file_index: fileIndex,
                                track_title: trackTitle,
                                event_type: 'play'
                            };
                            
                            $.post(ajaxurl, ajaxData, function(response) {
                                if (response.success) {
                                    console.log('[BFP Debug] Play event tracked successfully');
                                } else {
                                    console.error('[BFP Debug] Play tracking failed:', response);
                                }
                            });
                        }
                        
                        // Pause other players if needed
                        if (pauseOthers) {
                            for (var i = 0; i < bfp_players.length; i++) {
                                if (i != parseInt($player.attr("playernumber")) && bfp_players[i]) {
                                    if (bfp_players[i].pause) {
                                        bfp_players[i].pause();
                                    } else if (bfp_players[i].domNode && bfp_players[i].domNode.pause) {
                                        bfp_players[i].domNode.pause();
                                    }
                                }
                            }
                        }
                    });
                    
                    // Track pause events
                    $player.on('pause', function() {
                        var $container = $player.closest('.bfp-player-container');
                        var productId = $player.attr("data-product") || $container.data('product');
                        var trackTitle = $container.siblings('.bfp-player-title').text() || 
                                        $player.closest('tr').find('.bfp-player-title').text() || 
                                        'Unknown Track';
                        var fileIndex = $player.attr("data-file-index") || $container.data('file-index') || '0';
                        
                        console.log('[BFP Debug] PAUSE pressed - Product ID: ' + productId + ', Track: "' + trackTitle + '", File Index: ' + fileIndex);
                        
                        // Send AJAX request to track pause
                        if (productId && typeof ajaxurl !== 'undefined') {
                            var ajaxData = {
                                action: 'bfp_track_playback',
                                nonce: $container.data('nonce') || bfp_global_settings.nonce,
                                product_id: productId,
                                file_index: fileIndex,
                                track_title: trackTitle,
                                event_type: 'pause'
                            };
                            
                            $.post(ajaxurl, ajaxData, function(response) {
                                if (response.success) {
                                    console.log('[BFP Debug] Pause event tracked successfully');
                                } else {
                                    console.error('[BFP Debug] Pause tracking failed:', response);
                                }
                            });
                        }
                    });
                    
                    // Handle fade out for HTML5
                    if (fadeOut) {
                        $player.on('timeupdate', function() {
                            var duration = this.duration;
                            var currentTime = this.currentTime;
                            var remaining = duration - currentTime;
                            
                            if (!isNaN(duration) && !isNaN(currentTime) && remaining < 4 && remaining > 0) {
                                if (!this.fadeStarted) {
                                    this.fadeStarted = true;
                                    this.bkVolume = this.volume;
                                    smoothFadeOutMediaElement(this, remaining * 1000);
                                }
                            } else if (currentTime < duration - 4) {
                                this.fadeStarted = false;
                                if (this.bkVolume) {
                                    this.volume = this.bkVolume;
                                }
                            }
                        });
                    }
                    
                    // Handle ended event
                    $player.on('ended', function() {
                        var $container = $player.closest('.bfp-player-container');
                        var productId = $player.attr("data-product") || $container.data('product');
                        var trackTitle = $container.siblings('.bfp-player-title').text() || 
                                        $player.closest('tr').find('.bfp-player-title').text() || 
                                        'Unknown Track';
                        var fileIndex = $player.attr("data-file-index") || $container.data('file-index') || '0';
                        
                        console.log('[BFP Debug] TRACK ENDED - Product ID: ' + productId + ', Track: "' + trackTitle + '", File Index: ' + fileIndex);
                        
                        // Send AJAX request to track ended event
                        if (productId && typeof ajaxurl !== 'undefined') {
                            var ajaxData = {
                                action: 'bfp_track_playback',
                                nonce: $container.data('nonce') || bfp_global_settings.nonce,
                                product_id: productId,
                                file_index: fileIndex,
                                track_title: trackTitle,
                                event_type: 'ended'
                            };
                            
                            $.post(ajaxurl, ajaxData, function(response) {
                                if (response.success) {
                                    console.log('[BFP Debug] Ended event tracked successfully');
                                } else {
                                    console.error('[BFP Debug] Ended tracking failed:', response);
                                }
                            });
                        }
                        
                        this.currentTime = 0;
                        
                        // Play next if enabled
                        if (playAll * 1) {
                            var currentPlayerNumber = parseInt($player.attr("playernumber"));
                            console.log('[BFP Debug] Auto-playing next track...');
                            _playNext(currentPlayerNumber, $player.closest('[data-loop="1"]').length > 0);
                        }
                    });
                    
                    bfp_player_counter++;
                });
            } else if (useWaveSurfer) {
                // WaveSurfer initialization
                fullPlayers.each(function() {
                    var $player = $(this);
                    var audioUrl = $player.find("source").attr("src");
                    var $container = $player.closest('.bfp-player-container');
                    
                    if (audioUrl && !$container.data('wavesurfer-init')) {
                        $player.attr("playernumber", bfp_player_counter);
                        var wavesurfer = initWaveSurferPlayer($container[0], audioUrl);
                        bfp_players[bfp_player_counter] = wavesurfer;
                        $container.data('wavesurfer-init', true);
                        $container.addClass('wavesurfer-ready');
                        
                        // Enhanced event handling for WaveSurfer
                        wavesurfer.on('play', function() {
                            var productId = $player.attr("data-product") || $container.data('product');
                            var trackTitle = $container.siblings('.bfp-player-title').text() || 
                                            $player.closest('tr').find('.bfp-player-title').text() || 
                                            'Unknown Track';
                            var fileIndex = $player.attr("data-file-index") || $container.data('file-index') || '0';
                            
                            console.log('[BFP Debug] WAVESURFER PLAY - Product ID: ' + productId + ', Track: "' + trackTitle + '", File Index: ' + fileIndex);
                            
                            // Send AJAX request to track playback
                            if (productId && typeof ajaxurl !== 'undefined') {
                                var ajaxData = {
                                    action: 'bfp_track_playback',
                                    nonce: $container.data('nonce') || bfp_global_settings.nonce,
                                    product_id: productId,
                                    file_index: fileIndex,
                                    track_title: trackTitle,
                                    event_type: 'play'
                                };
                                
                                $.post(ajaxurl, ajaxData, function(response) {
                                    if (response.success) {
                                        console.log('[BFP Debug] WaveSurfer play event tracked successfully');
                                    } else {
                                        console.error('[BFP Debug] WaveSurfer play tracking failed:', response);
                                    }
                                });
                            }
                            
                            if (pauseOthers) {
                                for (var i = 0; i < bfp_players.length; i++) {
                                    if (i != bfp_player_counter && bfp_players[i]) {
                                        if (bfp_players[i].pause) {
                                            bfp_players[i].pause();
                                        } else if (bfp_players[i].domNode) {
                                            bfp_players[i].domNode.pause();
                                        }
                                    }
                                }
                            }
                        });
                        
                        wavesurfer.on('pause', function() {
                            var productId = $player.attr("data-product") || $container.data('product');
                            var trackTitle = $container.siblings('.bfp-player-title').text() || 
                                            $player.closest('tr').find('.bfp-player-title').text() || 
                                            'Unknown Track';
                            var fileIndex = $player.attr("data-file-index") || $container.data('file-index') || '0';
                            
                            console.log('[BFP Debug] WAVESURFER PAUSE - Product ID: ' + productId + ', Track: "' + trackTitle + '", File Index: ' + fileIndex);
                            
                            // Send AJAX request to track pause
                            if (productId && typeof ajaxurl !== 'undefined') {
                                var ajaxData = {
                                    action: 'bfp_track_playback',
                                    nonce: $container.data('nonce') || bfp_global_settings.nonce,
                                    product_id: productId,
                                    file_index: fileIndex,
                                    track_title: trackTitle,
                                    event_type: 'pause'
                                };
                                
                                $.post(ajaxurl, ajaxData, function(response) {
                                    if (response.success) {
                                        console.log('[BFP Debug] WaveSurfer pause event tracked successfully');
                                    } else {
                                        console.error('[BFP Debug] WaveSurfer pause tracking failed:', response);
                                    }
                                });
                            }
                        });
                        
                        wavesurfer.on('finish', function() {
                            var productId = $player.attr("data-product") || $container.data('product');
                            var trackTitle = $container.siblings('.bfp-player-title').text() || 
                                            $player.closest('tr').find('.bfp-player-title').text() || 
                                            'Unknown Track';
                            var fileIndex = $player.attr("data-file-index") || $container.data('file-index') || '0';
                            
                            console.log('[BFP Debug] WAVESURFER TRACK FINISHED - Product ID: ' + productId + ', Track: "' + trackTitle + '", File Index: ' + fileIndex);
                            
                            // Send AJAX request to track ended event
                            if (productId && typeof ajaxurl !== 'undefined') {
                                var ajaxData = {
                                    action: 'bfp_track_playback',
                                    nonce: $container.data('nonce') || bfp_global_settings.nonce,
                                    product_id: productId,
                                    file_index: fileIndex,
                                    track_title: trackTitle,
                                    event_type: 'ended'
                                };
                                
                                $.post(ajaxurl, ajaxData, function(response) {
                                    if (response.success) {
                                        console.log('[BFP Debug] WaveSurfer ended event tracked successfully');
                                    } else {
                                        console.error('[BFP Debug] WaveSurfer ended tracking failed:', response);
                                    }
                                });
                            }
                            
                            wavesurfer.seekTo(0);
                            if (playAll * 1) {
                                console.log('[BFP Debug] Auto-playing next track (WaveSurfer)...');
                                _playNext(parseInt($player.attr("playernumber")), false);
                            }
                        });
                        
                        bfp_player_counter++;
                    }
                });
            } else {
                // FIXED: Enhanced MediaElement.js initialization
                var mejsOptions = {
                    pauseOtherPlayers: pauseOthers,
                    iPadUseNativeControls: iosNativeControls,
                    iPhoneUseNativeControls: iosNativeControls,
                    
                    // Add source chooser to handle multiple sources
                    features: ['playpause', 'current', 'progress', 'duration', 'volume', 'sourcechooser'],
                    
                    // Improved error handling
                    error: function(media, originalNode) {
                        console.log('[BFP] Media error handler triggered');
                        
                        var sources = $(originalNode).find('source');
                        
                        if (sources.length === 0) {
                            console.error('No source elements found in audio tag');
                        } else {
                            sources.each(function() {
                                var src = $(this).attr('src');
                                console.log('[BFP] Checking source:', src);
                                
                                // Check REST API URLs
                                if (src && src.indexOf('/wp-json/bandfront-player/') !== -1) {
                                    console.log('[BFP] REST API URL detected, checking availability');
                                    $.ajax({
                                        url: src,
                                        type: 'HEAD',
                                        success: function() {
                                            console.log('[BFP] REST API URL is accessible');
                                        },
                                        error: function(xhr) {
                                            console.error('[BFP] REST API URL failed:', xhr.status, xhr.statusText);
                                        }
                                    });
                                }
                                
                                // Convert old action URLs to REST API URLs
                                if (src && src.indexOf('bfp-action=play') !== -1) {
                                    console.log('[BFP] Converting legacy action URL to REST API');
                                    var matches = src.match(/bfp-product=(\d+).*?bfp-file=([^&]+)/);
                                    if (matches && matches.length >= 3) {
                                        var productId = matches[1];
                                        var fileId = decodeURIComponent(matches[2]);
                                        
                                        // Check if we have wpApiSettings
                                        var restRoot = (typeof wpApiSettings !== 'undefined' && wpApiSettings.root) ? 
                                                      wpApiSettings.root : '/wp-json/';
                                        
                                        var restUrl = restRoot + 'bandfront-player/v1/stream/' + 
                                                     productId + '/' + fileId;
                                        
                                        console.log('[BFP] Converted to REST API URL:', restUrl);
                                        
                                        // Update the source
                                        $(this).attr('src', restUrl);
                                        
                                        // Try to reload the media element
                                        if (media.setSrc) {
                                            console.log('[BFP] Attempting to reload with new URL');
                                            media.setSrc(restUrl);
                                            media.load();
                                            media.play();
                                        }
                                    }
                                }
                            });
                        }
                        
                        // Try fallback to native HTML5 audio
                        try {
                            console.log('[BFP] Attempting fallback to native HTML5 audio');
                            originalNode.controls = true;
                            originalNode.style.display = 'block';
                            originalNode.style.width = '100%';
                            
                            // Force reload
                            originalNode.load();
                        } catch (e) {
                            console.error('[BFP] Fallback failed:', e);
                        }
                    },
                    
                    success: function(mediaElement, originalNode) {
                        console.log('[BFP] MediaElement initialized successfully');
                        var $node = $(originalNode);
                        var duration = $node.data("duration");
                        var estimatedDuration = $node.data("estimated_duration");
                        var playerNumber = $node.attr("playernumber");
                        
                        // Debug: Log source loading
                        console.log('MediaElement initialized:', 
                                   'ID:', originalNode.id, 
                                   'Source:', $node.find('source').attr('src'));
                        
                        // Handle estimated duration
                        if (typeof estimatedDuration !== 'undefined') {
                            mediaElement.getDuration = function() {
                                return estimatedDuration;
                            };
                        }
                        
                        // Handle fixed duration display
                        if (typeof duration !== 'undefined') {
                            setTimeout((function(playerNum, dur) {
                                return function() {
                                    if (bfp_players[playerNum] && bfp_players[playerNum].updateDuration) {
                                        bfp_players[playerNum].updateDuration = function() {
                                            $(this.media)
                                                .closest(".bfp-player")
                                                .find(".mejs-duration")
                                                .html(dur);
                                        };
                                        bfp_players[playerNum].updateDuration();
                                    }
                                };
                            })(playerNumber, duration), 50);
                        }
                        
                        // Set initial volume - FIXED compatibility
                        var initialVolume = $node.attr("volume");
                        if (initialVolume) {
                            var vol = parseFloat(initialVolume);
                            if (typeof mediaElement.setVolume === 'function') {
                                mediaElement.setVolume(vol);
                            } else {
                                mediaElement.volume = vol;
                            }
                            if (vol == 0) {
                                if (typeof mediaElement.setMuted === 'function') {
                                    mediaElement.setMuted(true);
                                } else {
                                    mediaElement.muted = true;
                                }
                            }
                        }
                        
                        // ENHANCED: Time update handler with better fade support
                        mediaElement.addEventListener("timeupdate", function() {
                            var currentDuration = mediaElement.getDuration ? mediaElement.getDuration() : mediaElement.duration;
                            var currentTime = mediaElement.currentTime;
                            
                            if (!isNaN(currentTime) && !isNaN(currentDuration)) {
                                // Fade out near end
                                if (fadeOut && (currentDuration - currentTime) < 4 && (currentDuration - currentTime) > 0) {
                                    if (!mediaElement.fadeStarted) {
                                        mediaElement.fadeStarted = true;
                                        smoothFadeOutMediaElement(mediaElement, (currentDuration - currentTime) * 1000);
                                    }
                                } else {
                                    // Restore volume
                                    mediaElement.fadeStarted = false;
                                    if (currentTime) {
                                        if (typeof mediaElement.bkVolume === 'undefined') {
                                            mediaElement.bkVolume = parseFloat(
                                                $(mediaElement).find("audio,video").attr("volume") || 
                                                mediaElement.volume || 1
                                            );
                                        }
                                        if (typeof mediaElement.setVolume === 'function') {
                                            mediaElement.setVolume(mediaElement.bkVolume);
                                        } else {
                                            mediaElement.volume = mediaElement.bkVolume;
                                        }
                                        if (mediaElement.bkVolume == 0) {
                                            if (typeof mediaElement.setMuted === 'function') {
                                                mediaElement.setMuted(true);
                                            } else {
                                                mediaElement.muted = true;
                                            }
                                        }
                                    }
                                }
                            }
                        });
                        
                        // Volume change handler - FIXED
                        mediaElement.addEventListener("volumechange", function() {
                            var currentDuration = mediaElement.getDuration ? mediaElement.getDuration() : mediaElement.duration;
                            var currentTime = mediaElement.currentTime;
                            
                            if (!isNaN(currentTime) && 
                                !isNaN(currentDuration) && 
                                ((currentDuration - currentTime) > 4 || !fadeOut) && 
                                currentTime) {
                                mediaElement.bkVolume = mediaElement.volume;
                            }
                        });
                        
                        // Ended event handler
                        mediaElement.addEventListener("ended", function() {
                            var $element = $(mediaElement);
                            var $loopElement = $element.closest('[data-loop="1"]');
                            
                            // Reset to beginning
                            $element[0].currentTime = 0;
                            
                            // Remove playing class
                            if ($element.closest(".bfp-single-player").length) {
                                $element
                                    .closest(".bfp-single-player")
                                    .find(".bfp-playing")
                                    .removeClass("bfp-playing");
                            }
                            
                            // Play next if enabled
                            if (playAll * 1 || $loopElement.length) {
                                var currentPlayerNumber = parseInt($element.attr("playernumber"));
                                if (isNaN(currentPlayerNumber)) {
                                    currentPlayerNumber = parseInt($element.find("[playernumber]").attr("playernumber"));
                                }
                                _playNext(currentPlayerNumber, $loopElement.length > 0);
                            }
                        });
                        
                        // Improved source handling
                        mediaElement.addEventListener('error', function(e) {
                            console.error('Media error:', e);
                            var $node = $(originalNode);
                            var sources = $node.find('source');
                            
                            if (sources.length > 0) {
                                sources.each(function() {
                                    var src = $(this).attr('src');
                                    
                                    // Convert action URL to streaming URL if needed
                                    if (src && src.indexOf('bfp-action=play') !== -1) {
                                        var matches = src.match(/bfp-product=(\d+).*?bfp-file=([^&]+)/);
                                        if (matches && matches.length >= 3) {
                                            var productId = matches[1];
                                            var fileId = matches[2];
                                            var fixedUrl = window.location.protocol + '//' + 
                                                window.location.host + '/?bfp-stream=1&bfp-product=' + 
                                                productId + '&bfp-file=' + fileId;
                                            
                                            console.log('Converting action URL to streaming URL on error:', fixedUrl);
                                            
                                            // Update source and reload
                                            $(this).attr('src', fixedUrl);
                                            if (mediaElement.setSrc) {
                                                mediaElement.setSrc(fixedUrl);
                                                mediaElement.load();
                                            }
                                        }
                                    }
                                });
                            }
                        });
                    }
                };

                // Initialize full players with MediaElement.js
                fullPlayers.each(function() {
                    var $player = $(this);
                    $player.attr("playernumber", bfp_player_counter);
                    mejsOptions.audioVolume = "vertical";
                    
                    // Verify source before initialization
                    var $source = $player.find('source');
                    if ($source.length === 0 || !$source.attr('src')) {
                        console.error('No valid source found for player:', $player.attr('id'));
                    }
                    
                    try {
                        bfp_players[bfp_player_counter] = new MediaElementPlayer($player[0], mejsOptions);
                    } catch (error) {
                        console.error('MediaElement.js player creation failed:', error);
                        // Fallback to basic HTML5 audio
                        bfp_players[bfp_player_counter] = $player[0];
                        $player[0].controls = true;
                    }
                    bfp_player_counter++;
                });
            }
            
            // FIXED: Button players initialization (track controls)
            buttonPlayers.each(function() {
                var $player = $(this);
                $player.attr("playernumber", bfp_player_counter);
                
                if (useHTML5) {
                    // Simple HTML5 button player
                    $player[0].controls = false;
                    var $button = $('<button class="bfp-play-button">▶</button>');
                    $player.after($button);
                    $player.hide();
                    
                    $button.on('click', function() {
                        if ($player[0].paused) {
                            $player[0].play();
                            $button.html('❚❚');
                        } else {
                            $player[0].pause();
                            $button.html('▶');
                        }
                    });
                    
                    $player.on('play', function() {
                        $button.html('❚❚');
                    }).on('pause ended', function() {
                        $button.html('▶');
                    });
                    
                    bfp_players[bfp_player_counter] = $player;
                } else if (useWaveSurfer) {
                    // Simple play button for WaveSurfer
                    var $button = $('<button class="bfp-play-button">▶</button>');
                    $player.after($button);
                    $player.hide();
                    
                    $button.on('click', function() {
                        // Find corresponding full player
                        var playerNum = parseInt($player.attr("playernumber"));
                       var correspondingPlayer = null;
                       
                       // Look for the main player with same product ID
                       var productId = $player.attr("data-product");
                       for (var i = 0; i < bfp_players.length; i++) {
                           if (bfp_players[i] && bfp_players[i].container) {
                               var $container = $(bfp_players[i].container);
                               if ($container.find('[data-product="' + productId + '"]').length) {
                                   correspondingPlayer = bfp_players[i];
                                   break;
                               }
                           }
                       }
                       
                       if (correspondingPlayer) {
                           if (correspondingPlayer.isPlaying && correspondingPlayer.isPlaying()) {
                               correspondingPlayer.pause();
                               $button.html('▶');
                           } else {
                               correspondingPlayer.play();
                               $button.html('❚❚');
                           }
                       }
                   });
                   
                   bfp_players[bfp_player_counter] = $button;
                } else {
                   // MediaElement.js track controls
                   mejsOptions.features = ["playpause"];
                   
                   try {
                       bfp_players[bfp_player_counter] = new MediaElementPlayer($player[0], mejsOptions);
                   } catch (error) {
                       console.error('MediaElement.js track player creation failed:', error);
                       bfp_players[bfp_player_counter] = $player;
                   }
               }
               
               bfp_player_counter++;
               
               // Position over image
               _setOverImage($player);
               $(window).on("resize", function() {
                   _setOverImage($player);
               });
           });
           
           // Handle grouped products - try different selectors
           if (!$(groupedSelector).length) {
               groupedSelector = ".product-type-grouped [data-product_id]";
           }
           if (!$(groupedSelector).length) {
               groupedSelector = ".woocommerce-grouped-product-list [data-product_id]";
           }
           if (!$(groupedSelector).length) {
               groupedSelector = '.woocommerce-grouped-product-list [id*="product-"]';
           }
           
           // Process grouped products
           $(groupedSelector).each(function() {
               try {
                   var $element = $(this);
                   var productId = ($element.data("product_id") || 
                                   $element.attr("name") || 
                                   $element.attr("id")).replace(/[^\d]/g, "");
                   var $playerTitle = $(".bfp-player-list.group_cart_control_products .product-" + 
                                      productId + ":first .bfp-player-title");
                   var $table = $("<table></table>");
                   
                   if ($playerTitle.length && !$playerTitle.closest(".bfp-first-in-product").length) {
                       $playerTitle.closest("tr").addClass("bfp-first-in-product");
                       
                       if ($playerTitle.closest("form").length == 0) {
                           $playerTitle.closest(".bfp-player-list").prependTo($element.closest("form"));
                       }
                       
                       $table.append($element.closest("tr").prepend("<td>" + $playerTitle.html() + "</td>"));
                       $playerTitle.html("").append($table);
                   }
               } catch (error) {}
           });
           
           // Handle click on player titles in single player mode
           $(document).on("click", "[data-player-id]", function() {
               var $element = $(this);
               var $singlePlayer = $element.closest(".bfp-single-player");
               
               if ($singlePlayer.length) {
                   $(".bfp-player-title").removeClass("bfp-playing");
                   var playerId = $element.attr("data-player-id");
                   $element.addClass("bfp-playing");
                   _hideShowPlayersAndPositioning($singlePlayer.find('.bfp-player-container[data-player-id="' + playerId + '"]'));
               }
           });
       }
   };

   /**
    * Force re-initialization of players
    */
   window.bfp_force_init = function() {
       delete window.generated_the_bfp;
       generate_the_bfp(true);
   };

   // Initialize on document ready
   jQuery(generate_the_bfp);
   
   // Initialize on window load and handle iOS
   jQuery(window).on("load", function() {
       generate_the_bfp(true);
       
       var $ = jQuery;
       var userAgent = window.navigator.userAgent;
       
       // Handle lazy loading
       $("[data-lazyloading]").each(function() {
           var $element = $(this);
           $element.attr("preload", $element.data("lazyloading"));
       });
       
       // iOS specific handling
       if (userAgent.match(/iPad/i) || userAgent.match(/iPhone/i)) {
           var shouldPlayAll = (typeof bfp_global_settings !== 'undefined') ? 
               bfp_global_settings.play_all : 1;
               
           if (shouldPlayAll) {
               $(".bfp-player .mejs-play button").one("click", function() {
                   if (typeof bfp_preprocessed_players === 'undefined') {
                       bfp_preprocessed_players = true;
                       var $button = $(this);
                       
                       // Pre-process all audio elements
                       $(".bfp-player audio").each(function() {
                           this.play();
                           this.pause();
                       });
                       
                       // Re-trigger the click after preprocessing
                       setTimeout(function() {
                           $button.trigger("click");
                       }, 500);
                   }
               });
           }
       }
   }).on("popstate", function() {
       // Re-initialize if there are unprocessed players
       if (jQuery("audio[data-product]:not([playernumber])").length) {
           bfp_force_init();
       }
   });
   
   // Re-initialize on various AJAX events from other plugins
   jQuery(document).on(
       "scroll wpfAjaxSuccess woof_ajax_done yith-wcan-ajax-filtered " +
       "wpf_ajax_success berocket_ajax_products_loaded " + 
       "berocket_ajax_products_infinite_loaded lazyload.wcpt", 
       bfp_force_init
   );
   
   // Play button on cover functionality
   jQuery(document).ready(function($) {
       // Use localized settings if available
       if (typeof bfp_global_settings !== 'undefined') {
           var audioEngine = bfp_global_settings.audio_engine;
           var playSim = bfp_global_settings.bfp_allow_concurrent_audio;
           var onCover = bfp_global_settings.on_cover;
           
           // Play button on cover functionality
           if (onCover == '1') {
               $(document).on('click', '.bfp-play-on-cover', function(e) {
                   e.preventDefault();
                   e.stopPropagation();
                   
                   var $button = $(this);
                   var productId = $button.data('product-id');
                   var $container = $button.siblings('.bfp-hidden-player-container');
                   var $audio = $container.find('audio').first();
                   
                   if ($audio.length) {
                       // Pause all other audio elements
                       $('audio').each(function() {
                           if (this !== $audio[0]) {
                               this.pause();
                           }
                       });
                       
                       // Toggle play/pause
                       if ($audio[0].paused) {
                           $audio[0].play();
                           $button.addClass('playing');
                       } else {
                           $audio[0].pause();
                           $button.removeClass('playing');
                       }
                       
                       // Update button state based on audio events
                       $audio.on('play', function() {
                           $button.addClass('playing');
                       }).on('pause ended', function() {
                           $button.removeClass('playing');
                       });
                   }
               });
           }
       }
   });
})();