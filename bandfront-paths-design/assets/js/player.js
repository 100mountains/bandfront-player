// This file contains JavaScript for the audio player functionality.
// Initialize the audio player when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    const audioPlayers = document.querySelectorAll('.bfp-audio-player');

    audioPlayers.forEach(player => {
        const audioElement = player.querySelector('audio');
        const playButton = player.querySelector('.play-button');
        const pauseButton = player.querySelector('.pause-button');

        // Play button event listener
        playButton.addEventListener('click', function() {
            audioElement.play();
            playButton.style.display = 'none';
            pauseButton.style.display = 'inline-block';
        });

        // Pause button event listener
        pauseButton.addEventListener('click', function() {
            audioElement.pause();
            pauseButton.style.display = 'none';
            playButton.style.display = 'inline-block';
        });

        // Update button visibility based on audio state
        audioElement.addEventListener('ended', function() {
            pauseButton.style.display = 'none';
            playButton.style.display = 'inline-block';
        });
    });
});