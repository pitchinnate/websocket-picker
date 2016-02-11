<?php

namespace App;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Message implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients[$conn->resourceId] = [
            'connection' => $conn,
        ];
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);
        $valid_functions = ['pick','reset','connect'];
        if(in_array($data->event,$valid_functions)) {
            $functionName = 'event' . $data->event;
            $this->$functionName($from,$data);
        } else {
            $from->send('INVALID REQUEST');
        }
    }

    private function eventconnect(ConnectionInterface $from, $data)
    {
        $avatar = 'http://api.adorable.io/avatars/150/' . rand(100000,999999) . '.png';
        $this->clients[$from->resourceId]['avatar'] = $avatar;
        $this->clients[$from->resourceId]['is_admin'] = $data->is_admin;

        $send_data = [
            'event' => 'connect',
            'clients' => $this->clients,
        ];
        $from->send(json_encode(['event' => 'connected', 'avatar'=> $avatar]));
        $this->sendMessageToAll($send_data);
    }

    private function eventreset(ConnectionInterface $from, $data)
    {
        $this->sendMessageToAll(['event'=>'reset']);
    }

    private function eventpick(ConnectionInterface $from, $data)
    {
        $users = [];
        foreach($this->clients as $key => $client) {
            if(!$client['is_admin']) $users[] = $key;
        }
        $winning_id = $users[rand(0,(count($users)-1))];
        $winning_avatar = $this->clients[$winning_id]['avatar'];
        foreach($this->clients as $key => $client) {
            $client['connection']->send(json_encode([
                'event'=>'pick',
                'winner'=> ($winning_id == $key ? true : false),
                'winning_avatar' => $winning_avatar,
            ]));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        unset($this->clients[$conn->resourceId]);
        $send_data = [
            'event' => 'connect',
            'clients' => $this->clients,
        ];
        $this->sendMessageToAll($send_data);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    private function sendMessageToAll($msg)
    {
        if(is_object($msg) || is_array($msg)) {
            $msg = json_encode($msg);
        }
        foreach ($this->clients as $client) {
            $client['connection']->send($msg);
        }
    }
}
