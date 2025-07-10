/**
 * Bandfront Player Engine
 * Handles audio player functionality on the frontend
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
        bfp_global_settings.audio_engine : 'mediaelement';
    window.fadeOut = (typeof bfp_global_settings !== 'undefined') ? 
        (1 * bfp_global_settings.fade_out) : true;

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
                    if (jQuery(bfp_players[toPlay].domNode).closest(".bfp-single-player").length) {
                        _hideShowPlayersAndPositioning(jQuery(bfp_players[toPlay].domNode).closest(".bfp-player-container"));
                    } else if (jQuery(bfp_players[toPlay].domNode).closest(".bfp-player-container").is(":visible")) {
                        bfp_players[toPlay].domNode.play();
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
                    if (bfp_players[toPlay].closest('.bfp-single-player').length) {
                        _hideShowPlayersAndPositioning(bfp_players[toPlay].closest('.bfp-player-container'));
                    } else if (bfp_players[toPlay].is(':visible')) {
                        bfp_players[toPlay].trigger('click');
                    } else {
                        _playPrev(playernumber - 1, loop);
                    }
                }
                // Handle full players
                else {
                    if (jQuery(bfp_players[toPlay].domNode).closest('.bfp-single-player').length) {
                        _hideShowPlayersAndPositioning(jQuery(bfp_players[toPlay].domNode).closest('.bfp-player-container'));
                    } else if (jQuery(bfp_players[toPlay].domNode).closest('.bfp-player-container').is(':visible')) {
                        bfp_players[toPlay].domNode.play();
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
                    return false;
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
                !(1 * bfp_global_settings.play_simultaneously) : true;
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
            
            if (!useWaveSurfer && audioEngine === 'wavesurfer') {
                console.warn('WaveSurfer.js not available, falling back to MediaElement.js');
                audioEngine = 'mediaelement';
            }

            if (useWaveSurfer) {
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
                            var productId = $player.attr("data-product");
                            if (productId) {
                                var trackUrl = window.location.protocol + "//" + 
                                    window.location.host + "/" +
                                    window.location.pathname.replace(/^\//g, '').replace(/\/$/g, '') +
                                    "?bfp-action=play&bfp-product=" + productId;
                                $.get(trackUrl);
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
                        
                        wavesurfer.on('finish', function() {
                            wavesurfer.seekTo(0);
                            if (playAll * 1) {
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
                    success: function(mediaElement, originalNode) {
                        var $node = $(originalNode);
                        var duration = $node.data("duration");
                        var estimatedDuration = $node.data("estimated_duration");
                        var playerNumber = $node.attr("playernumber");
                        
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
                    },
                    error: function(media, originalNode) {
                        console.error('MediaElement.js initialization failed for:', originalNode);
                    }
                };

                // Initialize full players with MediaElement.js
                fullPlayers.each(function() {
                    var $player = $(this);
                    $player.attr("playernumber", bfp_player_counter);
                    mejsOptions.audioVolume = "vertical";
                    
                    try {
                        bfp_players[bfp_player_counter] = new MediaElementPlayer($player[0], mejsOptions);
                    } catch (error) {
                        console.error('MediaElement.js player creation failed:', error);
                        // Fallback to basic HTML5 audio
                        bfp_players[bfp_player_counter] = $player[0];
                    }
                    bfp_player_counter++;
                });
            }
            
            // FIXED: Button players initialization (track controls)
            buttonPlayers.each(function() {
                var $player = $(this);
                $player.attr("playernumber", bfp_player_counter);
                
                if (useWaveSurfer) {
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
                    var $playerTitle = $(".bfp-player-list.merge_in_grouped_products .product-" + 
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
})();
