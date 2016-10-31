if ( 'undefined' === typeof INTER ) INTER = {}; 

INTER.canvas = (function($, oCanvas){

    "use strict";

    var game_canvas = {
            dom : {
                hud : document.getElementById('hud'),
                hud_controls : document.getElementById('hud_controls'),
                canvas : document.getElementById('canvas')
            },
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
            init : function( data ) {

                game_canvas.mainShipId = 's'+data.ms.id;
                game_canvas.mainShip = data.ms;
                game_canvas.update(data.content);
                game_canvas.targets(typeof data.os != 'undefined' ? data.os : []);
                game_canvas.bind_controls();

            },
            bind_controls: function() {
                $('.control').unbind('click').click(function(){
                    if ($(this).attr('data-action').indexOf('move')>0) {
                        game_canvas.content.direction = 0;
                        if ($(this).attr('data-action').indexOf('left')>0) game_canvas.content.direction = -1;
                        if ($(this).attr('data-action').indexOf('right')>0) game_canvas.content.direction = 1;
                    }
                    $.post($(this).attr('data-action'),
                        {},
                        function(response){
                            game_canvas.content.moved = (game_canvas.mainShip.x != response.map.ms.x || game_canvas.mainShip.y != response.map.ms.y);
                            game_canvas.mainShip = response.map.ms;
                            game_canvas.update(response.map.content);
                            game_canvas.log(typeof response.data.messages != 'undefined' ? response.data.messages : []);
                            game_canvas.targets(typeof response.map.os != 'undefined' ? response.map.os : []);
                            game_canvas.bind_controls();
                        }
                    );
                });
            },
            update : function(data) {
                game_canvas.content.data = data;
                for (var i in game_canvas.content.cache) {
                    game_canvas.content.cache[i].updated = false;
                }
                for (var i = 0; i < game_canvas.content.data.length; i++) {
                    game_canvas.load(i);
                }
                for (var i = 0; i < game_canvas.content.data.length; i++) {
                    game_canvas.draw(i);
                }
                if (game_canvas.content.moved) game_canvas.dash();
                for (var i in game_canvas.content.cache) {
                    if (!game_canvas.content.cache[i].updated) {
                        delete game_canvas.content.cache[i];
                    }
                }
            },
            load : function(i) {
                if (typeof game_canvas.content.cache[game_canvas.content.data[i].id] != 'undefined') {
                    game_canvas.content.data[i].prev = game_canvas.content.cache[game_canvas.content.data[i].id];
                }

                if (typeof game_canvas.content.data[i].prev != 'undefined') {
                    game_canvas.content.data[i].current_i = game_canvas.content.data[i].prev.i;
                    game_canvas.content.data[i].current_a = game_canvas.content.data[i].prev.a;
                    game_canvas.content.data[i].current_x = game_canvas.content.data[i].prev.x;
                    game_canvas.content.data[i].current_y = game_canvas.content.data[i].prev.y;
                    game_canvas.content.data[i].new_x = game_canvas.content.data[i].x;
                    game_canvas.content.data[i].new_y = game_canvas.content.data[i].y;
                } else {
                    game_canvas.content.data[i].current_i = game_canvas.content.data[i].i;
                    game_canvas.content.data[i].current_a = game_canvas.content.data[i].a;
                    game_canvas.content.data[i].current_x = game_canvas.content.data[i].x;
                    game_canvas.content.data[i].current_y = game_canvas.content.data[i].y;
                    game_canvas.content.data[i].new_x = game_canvas.content.data[i].x;
                    game_canvas.content.data[i].new_y = game_canvas.content.data[i].y;
                }
                
                game_canvas.content.data[i].o = game_canvas.canvas.display.image({
                    x: game_canvas.content.data[i].current_x,
                    y: game_canvas.content.data[i].current_y,
                    origin: { x: "center", y: "center" },
                    image: game_canvas.content.data[i].i
                });

                game_canvas.content.cache[game_canvas.content.data[i].id] = game_canvas.content.data[i];

                game_canvas.content.cache[game_canvas.content.data[i].id].updated = true;
            },
            draw : function(i) {
                if (game_canvas.content.data[i].current_x != game_canvas.content.data[i].new_x || game_canvas.content.data[i].current_y != game_canvas.content.data[i].new_y) {
                    if (game_canvas.content.data[i].a > 0) {
                        game_canvas.content.data[i].o.rotate(game_canvas.content.data[i].a);
                    }
                    game_canvas.canvas.addChild(game_canvas.content.data[i].o);
                    game_canvas.content.data[i].o.animate({
                        x: game_canvas.content.data[i].new_x,
                        y: game_canvas.content.data[i].new_y
                    }, {
                        duration: "long",
                        easing: "ease-in-out-cubic"
                    });
                } else {
                    if (game_canvas.content.data[i].id == game_canvas.mainShipId || game_canvas.content.data[i].id == (game_canvas.mainShipId+'s')) {
                        if (game_canvas.content.data[i].i != game_canvas.content.data[i].current_i) {
                            var rotation = (game_canvas.content.direction > 0) ? parseInt(game_canvas.content.data[i].a)-45 : parseInt(game_canvas.content.data[i].a)+45;
                            game_canvas.content.data[i].o.rotate(rotation);
                            game_canvas.canvas.addChild(game_canvas.content.data[i].o);
                            game_canvas.content.data[i].o.animate({
                                rotation: game_canvas.content.data[i].a
                            }, {
                                duration: "long",
                                easing: "ease-in-out-cubic"
                            });
                            game_canvas.content.data[i].current_a = game_canvas.content.data[i].a;
                        } else {
                            game_canvas.content.data[i].o.rotate(game_canvas.content.data[i].a);
                            game_canvas.canvas.addChild(game_canvas.content.data[i].o);
                            game_canvas.content.data[i].current_a = game_canvas.content.data[i].a;
                        }
                    } else {
                        if (game_canvas.content.data[i].a > 0) {
                            game_canvas.content.data[i].o.rotate(game_canvas.content.data[i].a);
                        }
                        game_canvas.canvas.addChild(game_canvas.content.data[i].o);
                    }
                }
            },
            dash: function() {
                var angle = game_canvas.content.cache[game_canvas.mainShipId].a;
                var rotated = (game_canvas.content.cache[game_canvas.mainShipId].i.indexOf('rotated') > 0);
                var exit_angle = rotated ? angle+45 : angle;
                var image = rotated ? '/imgs/map/dash_rotated.png' : '/imgs/map/dash.png'
                var dash = game_canvas.canvas.display.image({
                    x: 150,
                    y: 150,
                    origin: { x: "center", y: "center" },
                    image: image,
                    opacity: 0.5
                });
                dash.rotate(angle);
                game_canvas.canvas.addChild(dash);
                var exitPoint = game_canvas.findPoint(game_canvas.content.cache[game_canvas.mainShipId].current_x, game_canvas.content.cache[game_canvas.mainShipId].current_y, game_canvas.mainShip.angle, -100);
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

    return {
        init : game_canvas.init,
        dom : game_canvas.dom
    };

})(jQuery, oCanvas);
