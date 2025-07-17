// filepath: /bandfront-player/bandfront-player/assets/js/blocks/audio-player.js
document.addEventListener('DOMContentLoaded', function() {
    const audioPlayer = document.querySelector('.audio-player');

    if (audioPlayer) {
        const playButton = audioPlayer.querySelector('.play-button');
        const audioElement = audioPlayer.querySelector('audio');

        playButton.addEventListener('click', function() {
            if (audioElement.paused) {
                audioElement.play();
                playButton.textContent = 'Pause';
            } else {
                audioElement.pause();
                playButton.textContent = 'Play';
            }
        });

        audioElement.addEventListener('ended', function() {
            playButton.textContent = 'Play';
        });
    }
});