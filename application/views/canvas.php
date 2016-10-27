<html>
<head>
    <script src="//code.jquery.com/jquery-2.x-git.min.js" type="text/javascript"></script>
    <script src="/assets/js/ocanvas-2.8.6.min.js" type="text/javascript"></script>
</head>
<body style="background:#454545">
    <canvas id="canvas" width="300" height="300" style="margin-left:40%;" ></canvas>
    <div style="width:500px; margin: 20px auto; text-align: center;">
        <button class="control" value="izquierda" data-action="/action/move/left" >Izquierda</button>
        <button class="control" value="recto" data-action="/action/move/forward" >Recto</button>
        <button class="control" value="derecha" data-action="/action/move/right" >Derecha</button>
        <button class="control" value="giro" data-action="/action/move/turn" >Giro</button>
        <button class="control" value="attack" data-action="/action/attack" >Atacar</button>
    </div>
    <div id="target" style="width:500px; margin: 20px auto; text-align: center;">
        
    </div>
    <div id="log" style="width:500px; margin: 20px auto; text-align: left; font-family: monospace; font-size: 12px; color: #FFF;">

    </div>
    <script type="text/javascript">
        var INTER = {};
        INTER.canvas = {
            canvas : oCanvas.create({
                canvas: "#canvas",
                background: "#222",
                fps: 60
            }),
            content : {
                data: [],
                cache: {},
                loaded: 0,
                direction: 0,
                moved: false
            },
            mainShipId: null,
            mainShip: null,
            bind_controls: function() {
                $('.control').unbind('click').click(function(){
                    if ($(this).attr('data-action').indexOf('move')>0) {
                        INTER.canvas.content.direction = 0;
                        if ($(this).attr('data-action').indexOf('left')>0) INTER.canvas.content.direction = -1;
                        if ($(this).attr('data-action').indexOf('right')>0) INTER.canvas.content.direction = 1;
                    }
                    $.post($(this).attr('data-action'),
                        {},
                        function(response){
                            INTER.canvas.content.moved = (INTER.canvas.mainShip.x != response.map.ms.x || INTER.canvas.mainShip.y != response.map.ms.y);
                            INTER.canvas.mainShip = response.map.ms;
                            INTER.canvas.update(response.map.content);
                            INTER.canvas.log(typeof response.data.messages != 'undefined' ? response.data.messages : []);
                            INTER.canvas.targets(typeof response.map.os != 'undefined' ? response.map.os : []);
                            INTER.canvas.bind_controls();
                        }
                    );
                });
            },
            update : function(data) {
                INTER.canvas.content.data = data;
                for (var i in INTER.canvas.content.cache) {
                    INTER.canvas.content.cache[i].updated = false;
                }
                for (var i = 0; i < INTER.canvas.content.data.length; i++) {
                    INTER.canvas.load(i);
                }
                for (var i = 0; i < INTER.canvas.content.data.length; i++) {
                    INTER.canvas.draw(i);
                }
                if (INTER.canvas.content.moved) INTER.canvas.dash();
                for (var i in INTER.canvas.content.cache) {
                    if (!INTER.canvas.content.cache[i].updated) {
                        delete INTER.canvas.content.cache[i];
                    }
                }
            },
            load : function(i) {
                if (typeof INTER.canvas.content.cache[INTER.canvas.content.data[i].id] != 'undefined') {
                    INTER.canvas.content.data[i].prev = INTER.canvas.content.cache[INTER.canvas.content.data[i].id];
                }

                if (typeof INTER.canvas.content.data[i].prev != 'undefined') {
                    INTER.canvas.content.data[i].current_i = INTER.canvas.content.data[i].prev.i;
                    INTER.canvas.content.data[i].current_a = INTER.canvas.content.data[i].prev.a;
                    INTER.canvas.content.data[i].current_x = INTER.canvas.content.data[i].prev.x;
                    INTER.canvas.content.data[i].current_y = INTER.canvas.content.data[i].prev.y;
                    INTER.canvas.content.data[i].new_x = INTER.canvas.content.data[i].x;
                    INTER.canvas.content.data[i].new_y = INTER.canvas.content.data[i].y;
                } else {
                    INTER.canvas.content.data[i].current_i = INTER.canvas.content.data[i].i;
                    INTER.canvas.content.data[i].current_a = INTER.canvas.content.data[i].a;
                    INTER.canvas.content.data[i].current_x = INTER.canvas.content.data[i].x;
                    INTER.canvas.content.data[i].current_y = INTER.canvas.content.data[i].y;
                    INTER.canvas.content.data[i].new_x = INTER.canvas.content.data[i].x;
                    INTER.canvas.content.data[i].new_y = INTER.canvas.content.data[i].y;
                }
                
                INTER.canvas.content.data[i].o = INTER.canvas.canvas.display.image({
                    x: INTER.canvas.content.data[i].current_x,
                    y: INTER.canvas.content.data[i].current_y,
                    origin: { x: "center", y: "center" },
                    image: INTER.canvas.content.data[i].i
                });

                INTER.canvas.content.cache[INTER.canvas.content.data[i].id] = INTER.canvas.content.data[i];

                INTER.canvas.content.cache[INTER.canvas.content.data[i].id].updated = true;
            },
            draw : function(i) {
                if (INTER.canvas.content.data[i].current_x != INTER.canvas.content.data[i].new_x || INTER.canvas.content.data[i].current_y != INTER.canvas.content.data[i].new_y) {
                    if (INTER.canvas.content.data[i].a > 0) {
                        INTER.canvas.content.data[i].o.rotate(INTER.canvas.content.data[i].a);
                    }
                    INTER.canvas.canvas.addChild(INTER.canvas.content.data[i].o);
                    INTER.canvas.content.data[i].o.animate({
                        x: INTER.canvas.content.data[i].new_x,
                        y: INTER.canvas.content.data[i].new_y
                    }, {
                        duration: "long",
                        easing: "ease-in-out-cubic"
                    });
                } else {
                    if (INTER.canvas.content.data[i].id == INTER.canvas.mainShipId || INTER.canvas.content.data[i].id == (INTER.canvas.mainShipId+'s')) {
                        if (INTER.canvas.content.data[i].i != INTER.canvas.content.data[i].current_i) {
                            var rotation = (INTER.canvas.content.direction > 0) ? parseInt(INTER.canvas.content.data[i].a)-45 : parseInt(INTER.canvas.content.data[i].a)+45;
                            INTER.canvas.content.data[i].o.rotate(rotation);
                            INTER.canvas.canvas.addChild(INTER.canvas.content.data[i].o);
                            INTER.canvas.content.data[i].o.animate({
                                rotation: INTER.canvas.content.data[i].a
                            }, {
                                duration: "long",
                                easing: "ease-in-out-cubic"
                            });
                            INTER.canvas.content.data[i].current_a = INTER.canvas.content.data[i].a;
                        } else {
                            INTER.canvas.content.data[i].o.rotate(INTER.canvas.content.data[i].a);
                            INTER.canvas.canvas.addChild(INTER.canvas.content.data[i].o);
                            INTER.canvas.content.data[i].current_a = INTER.canvas.content.data[i].a;
                        }
                    } else {
                        if (INTER.canvas.content.data[i].a > 0) {
                            INTER.canvas.content.data[i].o.rotate(INTER.canvas.content.data[i].a);
                        }
                        INTER.canvas.canvas.addChild(INTER.canvas.content.data[i].o);
                    }
                }
            },
            dash: function() {
                var angle = INTER.canvas.content.cache[INTER.canvas.mainShipId].a;
                var rotated = (INTER.canvas.content.cache[INTER.canvas.mainShipId].i.indexOf('rotated') > 0);
                var exit_angle = rotated ? angle+45 : angle;
                var image = rotated ? '/imgs/map/dash_rotated.png' : '/imgs/map/dash.png'
                var dash = INTER.canvas.canvas.display.image({
                    x: 150,
                    y: 150,
                    origin: { x: "center", y: "center" },
                    image: image,
                    opacity: 0.5
                });
                dash.rotate(angle);
                INTER.canvas.canvas.addChild(dash);
                var exitPoint = INTER.canvas.findPoint(INTER.canvas.content.cache[INTER.canvas.mainShipId].current_x, INTER.canvas.content.cache[INTER.canvas.mainShipId].current_y, INTER.canvas.mainShip.angle, -100);
                dash.animate({
                    x: exitPoint.x,
                    y: exitPoint.y,
                    opacity: 0
                }, {
                    duration: 1500,
                    easing: "ease-in-out-cubic"
                });
            },
            log: function(messages) {
                $('#log').html('');
                if (messages == null) return;
                for (var i = 0; i < messages.length; i++) {
                    $('#log').append('<p>' + messages[i] + '</p>');
                }
            },
            targets: function(ships) {
                $('#target').html('');
                for (var i = 0; i < ships.length; i++) {
                    $('#target').append('<button class="control" data-action="/action/target/' + ships[i].id + '" >Fijar a ' + ships[i].name + '</button>');
                }
            },
            findPoint: function(x, y, angle, distance) {
                return {
                    x: x + (distance * Math.round(Math.cos((angle-90)*(Math.PI / 180)))),
                    y: y + (distance * Math.round(Math.sin((angle-90)*(Math.PI / 180))))
                };
            },
            // not used
            findEntrance: function(obj, angle) {
                console.log(angle);
                switch(angle) {
                    case 0:
                        obj.o.moveTo(obj.new_x, obj.new_y + 100);
                        break;
                    case 45:
                        obj.o.moveTo(obj.new_x + 100, obj.new_y + 100);
                        break;
                    case 90:
                        obj.o.moveTo(obj.new_x + 100, obj.new_y);
                        break;
                    case 135:
                        obj.o.moveTo(obj.new_x + 100, obj.new_y - 100);
                        break;
                    case 180:
                        obj.o.moveTo(obj.new_x, obj.new_y - 100);
                        break;
                    case 225:
                        obj.o.moveTo(obj.new_x - 100, obj.new_y - 100);
                        break;
                    case 270:
                        obj.o.moveTo(obj.new_x - 100, obj.new_y);
                        break;
                    case 315:
                        obj.o.moveTo(obj.new_x - 100, obj.new_y + 100);
                        break;
                }
            }
        };

        var data = <?php echo json_encode($data); ?>;
        INTER.canvas.mainShipId = 's'+data.ms.id;
        INTER.canvas.mainShip = data.ms;
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