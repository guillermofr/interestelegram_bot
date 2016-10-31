if ( 'undefined' === typeof INTER ) INTER = {};
INTER.communications = (function($, io, IMP){
    
    "use strict";

    var user_in = null;
    var dom = null;

    var communications = {
        socket : null,
        dom : null,
        init : function(options){

            user_id = options.user_id || null;
            dom = options.dom || {
                hud : null,
                hud_controls : null,
                canvas : null
            };

            communications.socket = io('http://www.inter.es:30000');
            communications.socket.on('connect', communications.connect);
            communications.socket.on('client_message', communications.message_recieved);
        },
        connect : function(){
            console.log('Connected', communications.socket.id, user_id);
            communications.socket.emit('identify', { id : user_id });
        },
        message_recieved : function(data){
            console.log('message recived', data);

            if ( 'undefined' !== typeof data.action ) {
                if ( 'undefined' !== typeof responseActions[data.action] ) {
                    responseActions[data.action](data);
                } else {
                    console.log('undefined action');
                    alert('message recieved, '+ data.action +' action is undefined');
                }
            } else {
                alert('message recieved that is not an action');
            }
        }
    };
    /* Communication responses actions layer */
    var responseActions = {
        under_attack : function(data){
            dom.canvas.classList.add('under_attack');
            dom.canvas.classList.add('shake-chunk');
            dom.canvas.classList.add('impact');
            setTimeout(function(){
                dom.canvas.classList.remove('shake-chunk');
                dom.canvas.classList.remove('under_attack');
                dom.canvas.classList.remove('impact');
            }, 2000);
            IMP.playAudio('/assets/sounds/under_attack.mp3', function(){
                IMP.playAudio('/assets/sounds/impact.mp3');
            });
        },
        destroyed : function(data){
            dom.canvas.classList.add('shake-hard');
            dom.canvas.classList.add('destroyed');
            IMP.playAudio('/assets/sounds/destroyed.mp3');
            setTimeout(function(){
                dom.canvas.classList.remove('shake-little');
                alert('Hemos sido destruidos por ' + data.from);
                document.location.href = 'http://www.inter.es/jugar';
            }, 3000);
        },
        locked_as_target : function(data){
            dom.canvas.classList.add('shake-little');
            dom.canvas.classList.add('targeted');
            IMP.playAudio('/assets/sounds/targetted.mp3');
            setTimeout(function(){
                dom.canvas.classList.remove('shake-little');
                dom.canvas.classList.remove('targeted');
            }, 2000);
        },
        dodged_attack : function(data){
            dom.canvas.classList.add('shake-opacity');
            dom.canvas.classList.add('under_attack');
            IMP.playAudio('/assets/sounds/dodge.mp3');
            setTimeout(function(){
                dom.canvas.classList.remove('shake-opacity');
                dom.canvas.classList.remove('under_attack');
            }, 2000);
        },
        announcement : function(data){

        },
        gandalf : function(){
            var gandalf = document.createElement('div');
            gandalf.innerHTML = '<iframe width="560" height="315" src="https://www.youtube.com/embed/FRBh2ftywLM?rel=0&autoplay=1&controls=0&loop=1&playlist=FRBh2ftywLM&showinfo=0" frameborder="0" allowfullscreen></iframe>';
            dom.hud.parentNode.replaceChild(gandalf, dom.hud);
        },
        gandalflocal : function(){
            IMP.playVideo('/assets/videos/gandalfsax.mov', {
                loop : true,
                onStart : function(video){
                    dom.hud.parentNode.replaceChild(video, dom.hud);
                }
            });
        },
        gandalffull : function(){
            IMP.playVideo('/assets/videos/gandalfsax.mov', {
                loop : true,
                onStart : function(video){
                    var body = document.getElementsByTagName("BODY")[0];
                    //body.innerHTML = '';
                    body.appendChild(video);
                    video.style.width = "auto";
                    video.style.height = "auto";
                    video.style.position = "fixed";
                    video.style.right = 0;
                    video.style.bottom = 0;
                    video.style['min-width'] = "100%";
                    video.style['min-height'] = "100%";
                    if ( video.clientWidth > window.innerWidth ) video.style.left = ( ( window.innerWidth - video.clientWidth ) / 2 ) + 'px';
                    window.addEventListener('resize', function(){
                        if ( video.clientWidth > window.innerWidth ) video.style.left = ( ( window.innerWidth - video.clientWidth ) / 2 ) + 'px';
                    });
                }
            });
        }
    };

    return {
        init : communications.init
    };

})(jQuery, io, INTER.mediaPlayer);
