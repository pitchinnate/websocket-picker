<?php
namespace App;

use Ratchet\Server\IoServer;
use App\Message;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__).'/vendor/autoload.php';

$server = IoServer::factory(
new HttpServer(
        new WsServer(
            new Message()
        )
    ),
    8282
);

$server->run();
