<?php
namespace Spn\Websocket;

use OpenSwoole\Websocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;
use Spn\Service\ChatService;
use Spn\Websocket\ConnectionManager;

class WebsocketServer
{
    private Server $server;

    public function __construct(private string $host, private int $port)
    {
        $this->server = new Server($this->host, $this->port);

        $connections = new ConnectionManager;
        $chatService = new ChatService;

        $this->server->on('Start', fn() => print "Websocket started on {$this->host}:{$this->port}\n");

        $this->server->on('Open', function(Server $server, Request $request) use ($connections)
        {
            $token = $request->get['token'] ?? null;
            $userId = ((new \Spn\Service\UserService)->getUserFromToken((string)$token));

            if (!$userId) {
                $server->disconnect($request->fd, 1008, "Invalid token");
                return;
            }

            $connections->add($request->fd, $userId);
            print "New Connection! \nToken => $token \nfd => $request->fd \nuserID => $userId \n";
        });

        $this->server->on('message', function(Server $server, Frame $frame) use ($chatService, $connections)
        {
            $data = json_decode($frame->data, true);
            if(!$data){
                return;
            }

            switch($data['type']){
                case 'message': $this->handleMessage($server, $frame, $data, $chatService, $connections); break;
                case 'delete': $this->handleDelete($server, $frame, $data, $chatService, $connections); break;
                default: break;
            }
        });

        $this->server->on('Close', fn($server, $fd) => $connections->remove($fd));

        $this->server->on('Disconnect', fn($server, $fd) => $connections->remove($fd));
    }

    private function handleMessage(Server $server, Frame $frame, array $data, ChatService $chatService, ConnectionManager $connections): void
    {
        $data['user_id'] = $connections->getUserIdByFd($frame->fd);
        $msg = $chatService->sendMessage($data);
        $connections->pruneDeadFds($server);
        $this->broadcast($server, $connections, $msg);
    }

    private function handleDelete(Server $server, Frame $frame, array $data, ChatService $chatService, ConnectionManager $connections): void
    {
        $senderId = $connections->getUserIdByFd($frame->fd);
        if(!$senderId) return;

        $deleted = $chatService->removeMessage($data['message_id'], $senderId, $data['conv_id'] ?? null);
        if(!$deleted) return;

        $payload = json_encode([
           'type' => 'delete',
           'message_id' => $data['message_id'],
           'conv_id' => $data['conv_id'] ?? null
        ]);

        $this->broadcast($server, $connections, $payload, $deleted['participant_ids'] ?? null);
    }

    private function broadcast(Server $server, ConnectionManager $connections, mixed $payload, ?array $participantIds = null): void
    {
        $encoded = is_string($payload) ? $payload : json_encode($payload);
        $fds = $participantIds ? $connections->findFdsByUsers($participantIds) : $connections->allFds();
        $connections->pruneDeadFds($server);

        foreach($fds as $fd){
            if($server->isEstablished($fd)){
                $server->push($fd, $encoded);
            }
        }
        print_r($payload);
        print_r("\n");
    }

    public function start(): void
    {
        $this->server->start();
    }
}