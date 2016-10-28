<html>
<head>
    <script src="//code.jquery.com/jquery-2.x-git.min.js" type="text/javascript"></script>
</head>
<body style="background:#454545">
    
    <h1>Socket client</h1>

    <h3>Messages</h3>
    <ul id="messages"></ul>

    <script src="http://www.inter.es:30000/socket.io/socket.io.js"></script>
    <script type="text/javascript">
        var socket = io('http://www.inter.es:30000');

        socket.on('client_message', function(data){

            var li = document.createElement('li');
            li.innerHTML = data.message;
            document.getElementById('messages').appendChild(li);

        });

    </script>
</body>
</html>