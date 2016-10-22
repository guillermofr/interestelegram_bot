<html>
<head>
    <script src="//code.jquery.com/jquery-2.x-git.min.js" type="text/javascript"></script>
</head>
<body style="background:#454545">
    <canvas id="canvas" width="300" height="300" style="margin-left:40%;" ></canvas>
    <div style="width:500px; margin: 20px auto; text-align: center;">
        <button class="control" value="izquierda" data-action="/action/move/left" >Izquierda</button>
        <button class="control" value="recto" data-action="/action/move/forward" >Recto</button>
        <button class="control" value="derecha" data-action="/action/move/right" >Derecha</button>
        <button class="control" value="giro" data-action="/action/move/turn" >Giro</button>
    </div>
    <div id="target" style="width:500px; margin: 20px auto; text-align: center;">
        
    </div>
    <div id="log" style="width:500px; margin: 20px auto; text-align: left; font-family: monospace; font-size: 12px; color: #FFF;">

    </div>
    <script type="text/javascript">
        var INTER = {};
        INTER.canvas = {
            canvas : document.getElementById('canvas'),
            context : canvas.getContext('2d'),
            content : {
                data: [],
                loaded: 0
            },
            bind_controls: function() {
                $('.control').unbind('click').click(function(){
                    $.post($(this).attr('data-action'),
                        {},
                        function(response){
                            INTER.canvas.update(response.map.content);
                            INTER.canvas.log(typeof response.data.messages != 'undefined' ? response.data.messages : []);
                            INTER.canvas.targets(typeof response.map.os != 'undefined' ? response.map.os : []);
                            INTER.canvas.bind_controls();
                        }
                    );
                });
            },
            loader : function(imgs, callback) {
                INTER.canvas.content.loaded = 0;
                for (var i = imgs.length - 1; i >= 0; i--) {
                    imgs[i].o = new Image();
                    imgs[i].o.src = imgs[i].i;
                    imgs[i].o.onload = callback;
                }
            },
            update : function(data) {
                INTER.canvas.content.data = data;
                INTER.canvas.loader(INTER.canvas.content.data, INTER.canvas.draw);
            },
            draw : function(path, x, y) {
                INTER.canvas.content.loaded++;

                if (INTER.canvas.content.loaded == INTER.canvas.content.data.length) {
                    var data = INTER.canvas.content.data;
                    for (var i = 0; i < data.length; i++) {
                        if (data[i].a != 0) {
                            INTER.canvas.drawRotated(INTER.canvas.context, data[i].o, data[i].a*Math.PI/180, data[i].x, data[i].y);
                        } else {
                            INTER.canvas.context.drawImage(data[i].o, data[i].x, data[i].y);
                        }
                    }
                }
            },
            drawRotated: function(context, image, angleInRad , positionX, positionY) {
                INTER.canvas.context.translate( positionX + image.width/2, positionY + image.height/2 );
                INTER.canvas.context.rotate( angleInRad );
                INTER.canvas.context.drawImage( image, -image.width/2, -image.height/2 );
                INTER.canvas.context.rotate( -angleInRad );
                INTER.canvas.context.translate( - (positionX + image.width/2), - (positionY + image.height/2) );
            },
            log: function(messages) {
                $('#log').html('');
                for (var i = 0; i < messages.length; i++) {
                    $('#log').append('<p>' + messages[i] + '</p>');
                }
            },
            targets: function(ships) {
                $('#target').html('');
                for (var i = 0; i < ships.length; i++) {
                    $('#target').append('<button class="control" data-action="/action/target/' + ships[i].id + '" >Fijar a ' + ships[i].name + '</button>');
                }
            }
        };

        var data = <?php echo json_encode($data); ?>;
        INTER.canvas.update(data.content);
        INTER.canvas.targets(typeof data.os != 'undefined' ? data.os : []);

    </script>
    <script>
    $.fn.ready(function(){
        INTER.canvas.bind_controls();
    });
    </script>
</body>
</html>