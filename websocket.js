var server = require('http').Server();

var io = require('socket.io')(server, {
    cors: {
        origin: "http://redis-demo.test",
        methods: ["GET", "POST"]
    }
});

var Redis = require('ioredis');
var redis = new Redis({
    host: 'redis',
    port: 6379
});

redis.subscribe('laravel_database_test-channel');
redis.on('message', function (channel, message) {
    console.log(channel, message);
    message = JSON.parse(message);
    io.emit(channel + ":" + message.event, message.data);
});

server.listen(3000, () => {
    console.log('http server started and listen on 3000.');
});
