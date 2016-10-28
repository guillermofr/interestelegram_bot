var io = require('socket.io-client');
var socket = io.connect('http://localhost:30000');
var user_id = null;

function getRandomInt(min, max) {
  return Math.floor(Math.random() * (max - min)) + min;
}

user_id = getRandomInt(1, 3);

socket.on('connect', function(){
    console.log('connected');
    socket.emit('identify', { id : user_id });
});

socket.on('client_message', function(data){
    console.log('message recived', data.message);
});