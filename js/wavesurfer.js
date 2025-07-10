/**
 * WaveSurfer.js Integration for Bandfront Player
 * Extends the main engine.js with WaveSurfer functionality
 */

(function($) {
    'use strict';
    
    // Only proceed if WaveSurfer is available
    if (typeof WaveSurfer === 'undefined') {
        console.warn('WaveSurfer.js not available, falling back to MediaElement.js');
        return;
    }
    
    // Extend the global initWaveSurferPlayer function if it doesn't exist
    if (typeof window.initWaveSurferPlayer === 'undefined') {
        window.initWaveSurferPlayer = function(container, audioUrl, options) {
            options = options || {};
            
            var $audio = $(container).find('audio');
            var audioId = $audio.attr('id') || 'wavesurfer-' + Math.random().toString(36).substr(2, 9);
            var volume = parseFloat($audio.attr('volume')) || 1;
            
            // Hide the original audio element
            $audio.hide();
            
            // Create waveform container
            var waveformId = 'waveform-' + audioId;
            var $waveform = $('<div id="' + waveformId + '" class="bfp-waveform"></div>');
            $(container).append($waveform);
            
            // Initialize WaveSurfer
            var wavesurfer = WaveSurfer.create({
                container: '#' + waveformId,
                waveColor: options.waveColor || '#999',
                progressColor: options.progressColor || '#000',
                cursorColor: options.cursorColor || '#333',
                backend: 'WebAudio',
                normalize: true,
                responsive: true,
                height: options.height || 60,
                barWidth: options.barWidth || 2,
                barGap: options.barGap || 1,
                volume: volume
            });

            // Load the audio
            wavesurfer.load(audioUrl);
            wavesurfer.bkVolume = volume;

            // Add MediaElement-compatible methods
            wavesurfer.play = function() { 
                return wavesurfer.play(); 
            };
            wavesurfer.pause = function() { 
                return wavesurfer.pause(); 
            };
            wavesurfer.setVolume = function(v) { 
                return wavesurfer.setVolume(v); 
            };
            wavesurfer.setMuted = function(m) { 
                return wavesurfer.setMute(m); 
            };
            
            // Handle fade out effect
            wavesurfer.on('audioprocess', function() {
                if (wavesurfer.isPlaying() && window.fadeOut) {
                    var remaining = wavesurfer.getDuration() - wavesurfer.getCurrentTime();
                    
                    if (remaining < 4 && remaining > 0 && !wavesurfer.fadeStarted) {
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

            // Reset fade on seek/play
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
            
            // Mark container as ready
            $(container).addClass('wavesurfer-ready');
            
            return wavesurfer;
        };
    }
    
    // Auto-initialize WaveSurfer players when engine.js runs
    $(document).ready(function() {
        // This will be called by the main engine.js
        console.log('WaveSurfer.js integration ready');
    });
    
})(jQuery);
