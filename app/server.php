<?php
namespace App;

use Ratchet\Server\IoServer;
use App\Chat;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__).'/vendor/autoload.php';

$server = IoServer::factory(
new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8282
);

$server->run();
