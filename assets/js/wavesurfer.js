/**
 * WaveSurfer.js Integration for Bandfront Player
 * With built-in player controls
 */

(function($) {
    'use strict';
    
    // Only proceed if WaveSurfer is available
    if (typeof WaveSurfer === 'undefined') {
        console.warn('WaveSurfer.js not available, falling back to MediaElement.js');
        return;
    }
    
    // Extend the global initWaveSurferPlayer function
    if (typeof window.initWaveSurferPlayer === 'undefined') {
        window.initWaveSurferPlayer = function(container, audioUrl, options) {
            options = options || {};
            
            var $audio = $(container).find('audio');
            var audioId = $audio.attr('id') || 'wavesurfer-' + Math.random().toString(36).substr(2, 9);
            var volume = parseFloat($audio.attr('volume')) || 1;
            
            // Hide the original audio element
            $audio.hide();
            
            // Create controls container
            var controlsId = 'controls-' + audioId;
            var $controls = $('<div id="' + controlsId + '" class="bfp-wavesurfer-controls"></div>');
            
            // Create control buttons and sliders
            var controlsHtml = `
                <div class="bfp-ws-controls-wrapper">
                    <button class="bfp-ws-play-pause" title="Play/Pause">
                        <span class="bfp-ws-play-icon">▶</span>
                        <span class="bfp-ws-pause-icon" style="display:none;">⏸</span>
                    </button>
                    <div class="bfp-ws-time">
                        <span class="bfp-ws-current-time">0:00</span> / 
                        <span class="bfp-ws-total-time">0:00</span>
                    </div>
                    <div class="bfp-ws-volume-wrapper">
                        <span class="bfp-ws-volume-icon">🔊</span>
                        <input type="range" class="bfp-ws-volume-slider" min="0" max="100" value="${volume * 100}">
                    </div>
                </div>
            `;
            $controls.html(controlsHtml);
            $(container).append($controls);
            
            // Create waveform container
            var waveformId = 'waveform-' + audioId;
            var $waveform = $('<div id="' + waveformId + '" class="bfp-waveform"></div>');
            $(container).append($waveform);
            
            // Get skin from player classes or default to dark
            var skin = 'dark';
            var $wrapper = $(container).closest('.bfp-player-wrapper');
            if ($wrapper.hasClass('light')) skin = 'light';
            else if ($wrapper.hasClass('custom')) skin = 'custom';
            
            // Set colors based on skin
            var colors = {
                dark: {
                    waveColor: '#666',
                    progressColor: '#fff',
                    cursorColor: '#fff'
                },
                light: {
                    waveColor: '#ddd',
                    progressColor: '#333',
                    cursorColor: '#333'
                },
                custom: {
                    waveColor: '#ffd93d',
                    progressColor: '#ff6b6b',
                    cursorColor: '#4ecdc4'
                }
            };
            
            // Initialize WaveSurfer
            var wavesurfer = WaveSurfer.create({
                container: '#' + waveformId,
                waveColor: colors[skin].waveColor,
                progressColor: colors[skin].progressColor,
                cursorColor: colors[skin].cursorColor,
                backend: 'WebAudio',
                normalize: true,
                responsive: true,
                height: options.height || 80,
                barWidth: options.barWidth || 3,
                barRadius: 3,
                barGap: options.barGap || 1,
                volume: volume
            });

            // Load the audio
            wavesurfer.load(audioUrl);
            wavesurfer.bkVolume = volume;
            
            // Get control elements
            var $playPause = $controls.find('.bfp-ws-play-pause');
            var $playIcon = $controls.find('.bfp-ws-play-icon');
            var $pauseIcon = $controls.find('.bfp-ws-pause-icon');
            var $currentTime = $controls.find('.bfp-ws-current-time');
            var $totalTime = $controls.find('.bfp-ws-total-time');
            var $volumeSlider = $controls.find('.bfp-ws-volume-slider');
            
            // Format time helper
            function formatTime(seconds) {
                var min = Math.floor(seconds / 60);
                var sec = Math.floor(seconds % 60);
                return min + ':' + (sec < 10 ? '0' : '') + sec;
            }
            
            // Play/pause button
            $playPause.on('click', function() {
                wavesurfer.playPause();
            });
            
            // Volume slider
            $volumeSlider.on('input', function() {
                var vol = $(this).val() / 100;
                wavesurfer.setVolume(vol);
                wavesurfer.bkVolume = vol;
            });
            
            // WaveSurfer events
            wavesurfer.on('ready', function() {
                $totalTime.text(formatTime(wavesurfer.getDuration()));
            });
            
            wavesurfer.on('audioprocess', function() {
                $currentTime.text(formatTime(wavesurfer.getCurrentTime()));
            });
            
            wavesurfer.on('play', function() {
                $playIcon.hide();
                $pauseIcon.show();
                $audio.trigger('play');
                
                // Pause other players
                if (typeof pauseAllExcept === 'function') {
                    pauseAllExcept(wavesurfer);
                }
            });
            
            wavesurfer.on('pause', function() {
                $playIcon.show();
                $pauseIcon.hide();
                $audio.trigger('pause');
            });
            
            wavesurfer.on('finish', function() {
                $playIcon.show();
                $pauseIcon.hide();
                $audio.trigger('ended');
            });
            
            // Click on waveform to seek
            wavesurfer.on('seek', function(progress) {
                $audio.trigger('timeupdate');
            });
            
            // Add CSS styles
            if (!$('#bfp-wavesurfer-styles').length) {
                var styles = `
                    <style id="bfp-wavesurfer-styles">
                        .bfp-wavesurfer-controls {
                            padding: 10px;
                            background: rgba(0,0,0,0.1);
                            border-radius: 5px;
                            margin-bottom: 10px;
                        }
                        .bfp-ws-controls-wrapper {
                            display: flex;
                            align-items: center;
                            gap: 15px;
                        }
                        .bfp-ws-play-pause {
                            background: none;
                            border: 2px solid currentColor;
                            border-radius: 50%;
                            width: 40px;
                            height: 40px;
                            cursor: pointer;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-size: 16px;
                        }
                        .bfp-ws-play-pause:hover {
                            background: rgba(255,255,255,0.1);
                        }
                        .bfp-ws-time {
                            font-size: 14px;
                            min-width: 100px;
                        }
                        .bfp-ws-volume-wrapper {
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            margin-left: auto;
                        }
                        .bfp-ws-volume-slider {
                            width: 80px;
                        }
                        .bfp-waveform {
                            cursor: pointer;
                        }
                        
                        /* Dark theme */
                        .dark .bfp-wavesurfer-controls {
                            background: rgba(255,255,255,0.1);
                            color: #fff;
                        }
                        .dark .bfp-ws-play-pause {
                            color: #fff;
                        }
                        
                        /* Light theme */
                        .light .bfp-wavesurfer-controls {
                            background: rgba(0,0,0,0.05);
                            color: #333;
                        }
                        .light .bfp-ws-play-pause {
                            color: #333;
                        }
                        
                        /* Custom/rainbow theme */
                        .custom .bfp-wavesurfer-controls {
                            background: linear-gradient(45deg, rgba(255,107,107,0.2), rgba(78,205,196,0.2));
                            color: #333;
                        }
                        .custom .bfp-ws-play-pause {
                            color: #ff6b6b;
                            border-color: #ff6b6b;
                        }
                        .custom .bfp-ws-play-pause:hover {
                            background: rgba(255,107,107,0.2);
                        }
                    </style>
                `;
                $('head').append(styles);
            }
            
            return wavesurfer;
        };
    }
    
})(jQuery);
