<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $color = sprintf('%02x', rand(0,255)) . sprintf('%02x', rand(0,255)) . sprintf('%02x', rand(0,255));
        $this->clients[$conn->resourceId] = [
            'connection' => $conn,
            'id' => $conn->resourceId,
            'color' => '#' . $color,
            'avatar' => 'http://api.adorable.io/avatars/150/' . $color . '.png',
            'is_admin' => false,
        ];
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);
        $valid_functions = ['pick','reset','admin', 'user'];
        if(in_array($data->event,$valid_functions)) {
            $functionName = 'event' . $data->event;
            $this->$functionName($from,$data);
        } else {
            $from->send('INVALID REQUEST');
        }
    }

    private function getActives()
    {
        $active_clients = [];
        foreach($this->clients as $client) {
            if(!$client['is_admin']) {
                $active_clients[] = $client;
            }
        }
        return $active_clients;
    }

    private function eventuser(ConnectionInterface $from, $data)
    {
        $current_client = $this->clients[$from->resourceId];
        $active_clients = $this->getActives();

        $send_data = [
            'event' => 'new_connection',
            'number_of_clients' => count($active_clients),
            'clients' => $active_clients,
        ];

        $from->send(json_encode(['event' => 'connected', 'avatar'=> $current_client['avatar']]));
        $this->sendMessageToAll($send_data);
    }

    private function eventadmin(ConnectionInterface $from, $data)
    {
        $current_client = $this->clients[$from->resourceId];
        $this->clients[$from->resourceId]['is_admin'] = true;
        $active_clients = $this->getActives();

        $send_data = [
            'event' => 'new_connection',
            'number_of_clients' => count($active_clients),
            'clients' => $active_clients,
        ];

        $from->send(json_encode(['event' => 'connected', 'avatar'=> $current_client['avatar']]));
        $this->sendMessageToAll($send_data);
    }

    private function eventreset(ConnectionInterface $from, $data)
    {
        $this->sendMessageToAll(['event'=>'reset']);
    }

    private function eventpick(ConnectionInterface $from, $data)
    {
        $active_clients = $this->getActives();

        $winner = rand(1,count($active_clients));
        $counter = 0;
        $winning_id = 0;
        $winning_avatar = '';
        foreach($active_clients as $client) {
            $counter++;
            if($counter == $winner) {
                $winning_id = $client['id'];
                $winning_avatar = $client['avatar'];
            }
        }
        foreach($this->clients as $client) {
            $is_winner = false;
            if($client['id'] == $winning_id) {
                $is_winner = true;
            }
            $client['connection']->send(json_encode([
                'event'=>'pick_click',
                'winner'=>$is_winner,
                'winning_avatar' => $winning_avatar
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        unset($this->clients[$conn->resourceId]);
        echo "Connection {$conn->resourceId} has disconnected\n";

        $active_clients = $this->getActives();
        $send_data = [
            'event' => 'new_connection',
            'number_of_clients' => count($active_clients),
            'clients' => $active_clients,
        ];
        $this->sendMessageToAll($send_data);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    public function sendMessageToAll($msg)
    {
        if(is_object($msg) || is_array($msg)) {
            $msg = json_encode($msg);
        }
        foreach ($this->clients as $client) {
            $client['connection']->send($msg);
        }
    }
}
