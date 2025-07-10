/**
 * Bandfront Player Public JavaScript
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

(function() {
    // Global variables
    var bfp_players = [];
    var bfp_player_counter = 0;

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
            
            // MediaElement options
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
                                bfp_players[playerNum].updateDuration = function() {
                                    $(this.media)
                                        .closest(".bfp-player")
                                        .find(".mejs-duration")
                                        .html(dur);
                                };
                                bfp_players[playerNum].updateDuration();
                            };
                        })(playerNumber, duration), 50);
                    }
                    
                    // Set initial volume
                    if ($node.attr("volume")) {
                        mediaElement.setVolume(parseFloat($node.attr("volume")));
                        if (mediaElement.volume == 0) {
                            mediaElement.setMuted(true);
                        }
                    }
                    
                    // Playing event handler
                    mediaElement.addEventListener("playing", function(event) {
                        var $player = $(mediaElement);
                        var $singlePlayer = $player.closest(".bfp-single-player");
                        
                        // Track play event
                        try {
                            var productId = $(event.detail.target).attr("data-product");
                            if (typeof productId !== 'undefined') {
                                var trackUrl = window.location.protocol + "//" + 
                                    window.location.host + "/" +
                                    window.location.pathname.replace(/^\//g, '').replace(/\/$/g, '') +
                                    "?bfp-action=play&bfp-product=" + productId;
                                $.get(trackUrl);
                            }
                        } catch (e) {}
                        
                        // Update playing state in UI
                        if ($singlePlayer.length) {
                            var playerId = $player
                                .closest(".bfp-player-container")
                                .attr("data-player-id");
                            $singlePlayer
                                .find('.bfp-player-title[data-player-id="' + playerId + '"]')
                                .addClass("bfp-playing");
                        }
                    });
                    
                    // Time update handler for fade out
                    mediaElement.addEventListener("timeupdate", function() {
                        var currentDuration = mediaElement.getDuration();
                        if (!isNaN(mediaElement.currentTime) && !isNaN(currentDuration)) {
                            // Fade out near end
                            if (fadeOut && (currentDuration - mediaElement.currentTime) < 4) {
                                mediaElement.setVolume(mediaElement.volume - mediaElement.volume / 3);
                            } else {
                                // Restore volume
                                if (mediaElement.currentTime) {
                                    if (typeof mediaElement.bkVolume === 'undefined') {
                                        mediaElement.bkVolume = parseFloat(
                                            $(mediaElement).find("audio,video").attr("volume") || mediaElement.volume
                                        );
                                    }
                                    mediaElement.setVolume(mediaElement.bkVolume);
                                    if (mediaElement.bkVolume == 0) {
                                        mediaElement.setMuted(true);
                                    }
                                }
                            }
                        }
                    });
                    
                    // Volume change handler
                    mediaElement.addEventListener("volumechange", function() {
                        var currentDuration = mediaElement.getDuration();
                        if (!isNaN(mediaElement.currentTime) && 
                            !isNaN(currentDuration) && 
                            ((currentDuration - mediaElement.currentTime) > 4 || !fadeOut) && 
                            mediaElement.currentTime) {
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
                }
            };

            // Grouped products selector variations
            var groupedSelector = '.product-type-grouped :regex(name,quantity\\[\\d+\\])';
            
            // Initialize full players
            fullPlayers.each(function() {
                var $player = $(this);
                $player.find("source").attr("src");
                $player.attr("playernumber", bfp_player_counter);
                mejsOptions.audioVolume = "vertical";
                
                try {
                    bfp_players[bfp_player_counter] = new MediaElementPlayer($player[0], mejsOptions);
                } catch (error) {
                    if ('console' in window) {
                        console.log(error);
                    }
                }
                bfp_player_counter++;
            });
            
            // Initialize button players
            buttonPlayers.each(function() {
                var $player = $(this);
                $player.find("source").attr("src");
                $player.attr("playernumber", bfp_player_counter);
                mejsOptions.features = ["playpause"];
                
                try {
                    bfp_players[bfp_player_counter] = new MediaElementPlayer($player[0], mejsOptions);
                } catch (error) {
                    if ('console' in window) {
                        console.log(error);
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
