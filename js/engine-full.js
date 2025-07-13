/**
 * Bandfront Player Engine - Minimal Implementation
 */

(function() {
    'use strict';
    
    // Global state
    var bfp_initialized = false;
    var bfp_players = [];
    var bfp_player_counter = 0;
    
    // Define missing variable
    var groupedSelector = '.merge_in_grouped_products';
    
    /**
     * Get audio source from element
     */
    function getAudioSource($audio) {
        // Check for source element first
        var $source = $audio.find('source').first();
        if ($source.length && $source.attr('src')) {
            return $source.attr('src');
        }
        
        // Check audio element src
        if ($audio.attr('src')) {
            return $audio.attr('src');
        }
        
        return null;
    }
    
    /**
     * Initialize single player mode
     */
    function initSinglePlayerMode() {
        jQuery(document).on('click', '.bfp-single-player .bfp-player-title', function(e) {
            e.preventDefault();
            var $title = jQuery(this);
            var $singlePlayer = $title.closest('.bfp-single-player');
            
            // Stop all players
            jQuery('audio').each(function() {
                if (!this.paused) {
                    this.pause();
                }
            });
            
            // Remove all playing classes
            $singlePlayer.find('.bfp-player-title').removeClass('bfp-playing');
            
            // Add playing class to clicked title
            $title.addClass('bfp-playing');
            
            // Find corresponding player container
            var targetPlayerId = $title.attr('data-player-id');
            var $targetContainer = $singlePlayer.find('.bfp-player-container[data-player-id="' + targetPlayerId + '"]');
            
            if ($targetContainer.length === 0) {
                // Fallback to index
                var index = $singlePlayer.find('.bfp-player-title').index($title);
                $targetContainer = $singlePlayer.find('.bfp-player-container').eq(index);
            }
            
            // Hide all containers and show target
            $singlePlayer.find('.bfp-player-container').hide();
            $targetContainer.show();
            
            // Play the audio
            var audio = $targetContainer.find('audio')[0];
            if (audio) {
                var src = getAudioSource(jQuery(audio));
                if (src) {
                    audio.play().catch(function(err) {
                        console.warn('BFP: Could not play audio:', err);
                    });
                }
            }
        });
    }
    
    /**
     * Main initialization function
     */
    window.generate_the_bfp = function(forceInit) {
        // Prevent duplicate initialization
        if (bfp_initialized && !forceInit) {
            return;
        }
        
        console.log('BFP: Initializing players...');
        
        // Reset state
        bfp_initialized = true;
        bfp_players = [];
        bfp_player_counter = 0;
        
        // Initialize track buttons
        jQuery('.bfp-player.track:not([data-bfp-initialized]), audio.track:not([data-bfp-initialized])').each(function() {
            var $this = jQuery(this);
            var $audio = $this.is('audio') ? $this : $this.find('audio').first();
            
            if (!$audio.length) return;
            
            // Mark as initialized
            $audio.attr('data-bfp-initialized', 'true');
            $audio.attr('playernumber', bfp_player_counter);
            
            // Check if audio has valid source
            var audioUrl = getAudioSource($audio);
            if (!audioUrl) {
                console.warn('BFP: No audio source found for track button');
                return;
            }
            
            // Store reference
            bfp_players[bfp_player_counter] = $audio[0];
            
            // Click handler for track buttons
            var $clickTarget = $this.is('audio') ? $this.parent() : $this;
            $clickTarget.off('click.bfp').on('click.bfp', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var audio = $audio[0];
                if (audio.paused) {
                    // Pause others if needed
                    if (typeof bfp_global_settings !== 'undefined' && !bfp_global_settings.play_simultaneously) {
                        jQuery('audio').each(function() {
                            if (this !== audio && !this.paused) {
                                this.pause();
                            }
                        });
                    }
                    
                    audio.play().catch(function(err) {
                        console.warn('BFP: Error playing audio:', err);
                    });
                    $clickTarget.addClass('bfp-playing');
                } else {
                    audio.pause();
                    $clickTarget.removeClass('bfp-playing');
                }
            });
            
            // Audio events
            $audio[0].addEventListener('play', function() {
                $clickTarget.addClass('bfp-playing');
            });
            
            $audio[0].addEventListener('pause', function() {
                $clickTarget.removeClass('bfp-playing');
            });
            
            $audio[0].addEventListener('ended', function() {
                $clickTarget.removeClass('bfp-playing');
            });
            
            bfp_player_counter++;
        });
        
        // Initialize full players (not track style)
        jQuery('audio.bfp-player:not(.track):not([data-bfp-initialized])').each(function() {
            var $player = jQuery(this);
            var playerNumber = bfp_player_counter;
            
            // Mark as initialized
            $player.attr('data-bfp-initialized', 'true');
            $player.attr('playernumber', playerNumber);
            
            // Check for valid source
            var audioUrl = getAudioSource($player);
            if (!audioUrl) {
                console.warn('BFP: No audio source found for player', $player[0]);
                return;
            }
            
            // Check if it's a button-style player
            var controls = $player.attr('data-controls');
            if (controls === 'track') {
                // Simple player without MediaElement
                bfp_players[playerNumber] = $player[0];
                
                $player[0].addEventListener('play', function() {
                    $player.addClass('bfp-playing');
                    $player.closest('.bfp-player-container').addClass('bfp-playing');
                });
                
                $player[0].addEventListener('pause', function() {
                    $player.removeClass('bfp-playing');
                    $player.closest('.bfp-player-container').removeClass('bfp-playing');
                });
                
                $player[0].addEventListener('ended', function() {
                    $player.removeClass('bfp-playing');
                    $player.closest('.bfp-player-container').removeClass('bfp-playing');
                });
            } else if (typeof MediaElementPlayer !== 'undefined') {
                // Initialize MediaElement player
                try {
                    var mePlayer = new MediaElementPlayer($player[0], {
                        pauseOtherPlayers: !(typeof bfp_global_settings !== 'undefined' && bfp_global_settings.play_simultaneously),
                        features: ['playpause', 'progress', 'current', 'duration', 'volume'],
                        audioVolume: 'horizontal',
                        startVolume: parseFloat($player.attr('data-volume')) || 0.7,
                        success: function(mediaElement, domObject) {
                            console.log('BFP: MediaElement initialized for player', playerNumber);
                            
                            bfp_players[playerNumber] = mediaElement;
                            
                            // Set volume
                            var volume = parseFloat($player.attr('data-volume')) || 0.7;
                            mediaElement.setVolume(volume);
                            
                            // Events
                            mediaElement.addEventListener('play', function() {
                                $player.addClass('bfp-playing');
                                $player.closest('.bfp-player-container').addClass('bfp-playing');
                                
                                // Pause others if needed
                                if (typeof bfp_global_settings !== 'undefined' && !bfp_global_settings.play_simultaneously) {
                                    for (var i = 0; i < bfp_players.length; i++) {
                                        if (i !== playerNumber && bfp_players[i]) {
                                            if (typeof bfp_players[i].pause === 'function') {
                                                bfp_players[i].pause();
                                            } else if (bfp_players[i].pause) {
                                                bfp_players[i].pause();
                                            }
                                        }
                                    }
                                }
                            });
                            
                            mediaElement.addEventListener('pause', function() {
                                $player.removeClass('bfp-playing');
                                $player.closest('.bfp-player-container').removeClass('bfp-playing');
                            });
                            
                            mediaElement.addEventListener('ended', function() {
                                $player.removeClass('bfp-playing');
                                $player.closest('.bfp-player-container').removeClass('bfp-playing');
                            });
                        },
                        error: function(e) {
                            console.error('BFP: MediaElement error:', e);
                            // Fallback to native controls
                            $player.attr('controls', 'controls');
                        }
                    });
                } catch (e) {
                    console.error('BFP: Failed to initialize MediaElement:', e);
                    $player.attr('controls', 'controls');
                }
            } else {
                // No MediaElement.js, use native controls
                $player.attr('controls', 'controls');
                bfp_players[playerNumber] = $player[0];
            }
            
            bfp_player_counter++;
        });
        
        // Initialize single player mode
        initSinglePlayerMode();
        
        // Initialize play on cover
        jQuery(document).on('click.bfp', '.bfp-play-on-cover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $this = jQuery(this);
            var productId = $this.data('product-id');
            
            if (!productId) return;
            
            // Find player for this product
            var $player = jQuery('audio[data-product-id="' + productId + '"]:first, audio[data-product="' + productId + '"]:first');
            
            if ($player.length) {
                var player = $player[0];
                if (player.paused) {
                    player.play().catch(function(err) {
                        console.warn('BFP: Error playing from cover:', err);
                    });
                } else {
                    player.pause();
                }
            }
        });
        
        console.log('BFP: Initialized ' + bfp_player_counter + ' players');
    };
    
    /**
     * Force re-initialization
     */
    window.bfp_force_init = function() {
        console.log('BFP: Force reinitializing...');
        
        // Clear initialization markers
        jQuery('[data-bfp-initialized]').removeAttr('data-bfp-initialized');
        jQuery('[playernumber]').removeAttr('playernumber');
        
        // Clear click handlers
        jQuery(document).off('click.bfp');
        jQuery('.bfp-player, .track').off('click.bfp');
        
        // Reset state
        bfp_initialized = false;
        
        // Destroy MediaElement players
        if (window.bfp_players) {
            for (var i = 0; i < window.bfp_players.length; i++) {
                if (window.bfp_players[i] && typeof window.bfp_players[i].remove === 'function') {
                    try {
                        window.bfp_players[i].remove();
                    } catch (e) {
                        console.warn('BFP: Error removing player:', e);
                    }
                }
            }
        }
        
        // Reinitialize
        window.generate_the_bfp(true);
    };
    
    // Initialize on document ready
    jQuery(document).ready(function() {
        // Small delay to ensure everything is loaded
        setTimeout(function() {
            window.generate_the_bfp();
        }, 100);
    });
    
    // Handle AJAX content
    jQuery(document).on('wpfAjaxSuccess woof_ajax_done yith-wcan-ajax-filtered', function() {
        setTimeout(function() {
            // Only reinitialize if there are uninitialized players
            if (jQuery('audio.bfp-player:not([data-bfp-initialized])').length) {
                window.bfp_force_init();
            }
        }, 200);
    });

})();
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
                    
                    // Add source chooser to handle multiple sources
                    features: ['playpause', 'current', 'progress', 'duration', 'volume', 'sourcechooser'],
                    
                    // Improved error handling
                    error: function(media, originalNode) {
                        console.error('MediaElement.js initialization failed for:', originalNode);
                        
                        // Log source information for debugging
                        var $node = $(originalNode);
                        var sources = $node.find('source');
                        
                        if (sources.length === 0) {
                            console.error('No source elements found in audio tag');
                        } else {
                            sources.each(function() {
                                var src = $(this).attr('src');
                                var type = $(this).attr('type');
                                console.log('Source:', src, 'Type:', type);
                                
                                // Convert action URL to streaming URL if needed
                                if (src && src.indexOf('bfp-action=play') !== -1) {
                                    var matches = src.match(/bfp-product=(\d+).*?bfp-file=([^&]+)/);
                                    if (matches && matches.length >= 3) {
                                        var productId = matches[1];
                                        var fileId = matches[2];
                                        var fixedUrl = window.location.protocol + '//' + 
                                            window.location.host + '/?bfp-stream=1&bfp-product=' + 
                                            productId + '&bfp-file=' + fileId;
                                        
                                        console.log('Converting action URL to streaming URL:', fixedUrl);
                                        
                                        // Try to update the source
                                        $(this).attr('src', fixedUrl);
                                        if (media.setSrc) {
                                            media.setSrc(fixedUrl);
                                            media.load();
                                        }
                                    }
                                }
                            });
                        }
                        
                        // Try fallback to native HTML5 audio
                        try {
                            console.log('Attempting fallback to native HTML5 audio');
                            originalNode.controls = true;
                            originalNode.style.display = 'block';
                            originalNode.style.width = '100%';
                        } catch (e) {
                            console.error('Fallback failed:', e);
                        }
                    },
                    
                    success: function(mediaElement, originalNode) {
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
    
    jQuery(document).ready(function($) {
        // Use localized settings
        var audioEngine = bfp_global_settings.audio_engine;
        var playSim = bfp_global_settings.play_simultaneously;
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
    });
})();
