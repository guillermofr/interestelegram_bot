var io = require('socket.io-client');
var socket = io.connect('http://localhost:30000');

socket.on('connect', function(){
    console.log('connected');

    var i = 0;

    setInterval(function(){
        i++;
        var message = { message : 'New message emited [' + i + ']' };
        console.log('Emitting', message, '...');
        socket.emit('create_message', message);
    }, 1000);

});