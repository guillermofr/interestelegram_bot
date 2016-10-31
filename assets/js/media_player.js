
if ( 'undefined' === typeof INTER ) INTER = {};
INTER.mediaPlayer = (function($){
    
    "use strict";
    
    /* Inter media */
    var mediaPlayer = {
        play : function(type, src, opt, cb){
            if ('undefined' === typeof cb && 'function' === typeof opt) { cb = opt; opt = {}; }
            else if ('undefined' === typeof opt) 
                opt = { numTimes : 1,
                        loop : false,
                        onStart : null,
                        onProgress : null };
            /* Test loop playing, play N times and other kind of stuff */
            var times = 0;
            var media = document.createElement(type);
            media.loop = opt.loop;
            media.addEventListener('progress', function(e){
                e.preventDefault();
                if ('function' === typeof opt.onProgress) opt.onProgress(e. media);
            });
            media.addEventListener('canplay', function(e){ 
                e.preventDefault(); 
                media.play(); 
                if ('function' === typeof opt.onStart) opt.onStart(media);
            });
            media.addEventListener('ended', function(e){ 
                e.preventDefault(); 
                times++;
                if ( opt.loop && times < numTimes ) {

                } else {
                    media.src=''; 
                    media = null; 
                    if( 'function' === typeof cb ) cb();
                }
            });
            media.src = src;
            media.load();
        },
        playAudio : function(src, opt, cb){ mediaPlayer.play('audio', src, opt, cb); },
        playVideo : function(src, opt, cb){ mediaPlayer.play('video', src, opt, cb); }
    }

    return {
        playAudio : mediaPlayer.playAudio,
        playVideo : mediaPlayer.playVideo
    };

})(jQuery);