var app = require('express');
var server = require('http').createServer(app);
var io = require('socket.io')(server);
var amqp = require('amqp');
var rabbitconfig = require('./config/rabbit.js');
var connection = amqp.createConnection(rabbitconfig);
var routeKey = '#';
var server_port = 30000;
var users = {};
var sockets = {};

var METHODS = {

    rabbitRead : function ( payload, headers, deliveryInfo, message ) {

        var msg = payload.data.toString('utf-8'),
            data = null;

        try { data = JSON.parse(msg); } 
        catch (e) { console.log(e, msg); }

        message.acknowledge(false);

        if (data == null) return;

        /* Check the message data information to check what to do */
        if ( 'undefined' !== typeof data.to ) {
            if ( 'undefined' !== typeof users[data.to] ) {
                var socket_ids = Object.keys(users[data.to]);
                var i = 0;
                for (i = 0; i < socket_ids.length; i++){
                    io.to(socket_ids[i]).emit('client_message', data.data);
                }
            } else {
                console.log('Trying to send a message to a closed or unknown socket');
                console.log(data, users);
            }
        } else {
            console.log('A message for everyone');
            io.sockets.emit('client_message', data);
        }

    }

};

/* WebSocket implementation */
io.on('connection', function ( socket ) {

    console.log('client connected', socket.id);
    sockets[socket.id] = true;

    socket.on( 'identify', function ( data ) {
        if ( 'undefined' === typeof users[data.id] ) users[data.id] = {};
        // Double binding (one socket one user, one user multiple sockets)
        users[data.id][socket.id] = true;
        sockets[socket.id] = data.id;
        console.log('user identified', data.id, socket.id, Object.keys(users[data.id]).length);
    });

    socket.on('disconnect', function(){
        console.log('client disconnected');
        console.log('- remove the socket and user relation to socket');
        var userid = sockets[socket.id];
        if ( 'undefined' !== typeof users[userid] ) delete(users[userid][socket.id]);
        delete(sockets[socket.id]);
        userid = null;
    });

});

/* Server Start and Rabbitmq Connection Handling */
server.listen(server_port, function(){
    console.log('Server listening on port', server_port);

    connection.on('ready', function(){

        connection.exchange('inter_comunication', {type: 'direct', durable: true, autoDelete: false}, function(exchange) {

            connection.queue('inter_comunication', {autoDelete: false, durable: true, passive: false}, function(queue){

                queue.bind(exchange, routeKey, function(){

                    queue.subscribe({ack: true, prefetchCount: 1}, METHODS.rabbitRead);

                });

            });

        });

    });
});
