jQuery(window).on('load', function () {
    // Cookie management helpers
    function setCookie(value) {
        var expires = "expires=" + cookieExpireTime;
        document.cookie = cookieName + "=" + value + "; " + expires;
    }

    function deleteCookie() {
        document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC";
    }

    function getCookie() {
        var name = cookieName + "=";
        var cookiesArray = document.cookie.split(';');
        for (var i = 0; i < cookiesArray.length; i++) {
            var cookie = cookiesArray[i];
            while (cookie.charAt(0) == ' ') {
                cookie = cookie.substring(1);
            }
            if (cookie.indexOf(name) == 0) {
                return cookie.substring(name.length, cookie.length);
            }
        }
        return "";
    }

    // Initialize variables
    var $ = jQuery,
        cookieName = 'bfp_playing',
        cookieExpireTime = 0,
        continuePlaying = false,
        cookieValue = getCookie(),
        cookieParts,
        audioPlayer;

    // Check if continue playing is enabled in settings
    if (typeof bfp_widget_settings != 'undefined') {
        if ('continue_playing' in bfp_widget_settings) {
            continuePlaying = bfp_widget_settings['continue_playing'];
        }
    }

    if (continuePlaying) {
        if (!/^\s*$/.test(cookieValue)) {
            cookieParts = cookieValue.split('||');
            if (cookieParts.length == 2) {
                audioPlayer = $('#' + cookieParts[0]);
                if (audioPlayer.length) {
                    audioPlayer[0].currentTime = cookieParts[1];
                    audioPlayer[0].play();
                }
            }
        }

        // Attach events to all audio players
        $('.bfp-player audio')
            .on('timeupdate', function () {
                if (!isNaN(this.currentTime) && this.currentTime) {
                    var id = $(this).attr('id');
                    setCookie(id + '||' + this.currentTime);
                }
            })
            .on('ended pause', function () {
                deleteCookie();
            });
    }

    // Download multiple files handler
    $(document).on('click', '.bfp-download-link', function (evt) {
        let $target = $(evt.target);
        let files = $target.attr('data-download-links');

        if (files) {
            files = JSON.parse(files);
            if (Array.isArray(files)) {
                for (let i in files) {
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
});