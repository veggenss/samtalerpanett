<?php

namespace Spn\Websocket;

use OpenSwoole\Table;

class ConnectionManager
{
    private Table $table;

    public function __construct()
    {
        $this->table = new Table(1024);
        $this->table->column('user_id', Table::TYPE_INT);
        $this->table->create();
    }

    public function add(int $fd, int $userId): void
    {
        $this->table->set($fd, array('user_id' => $userId));
    }

    public function remove(int $fd): void
    {
        $this->table->del($fd);
    }

    public function getUserIdByFd(int $fd): ?int
    {
        $row = $this->table->get($fd);
        return $row['user_id'] ?? null;
    }

    public function allFds(): array
    {
        $fds = [];
        foreach ($this->table as $fd => $row) {
            $fds[] = $fd;
        }
        return $fds;
    }

    public function findFdsByUsers(array $userIds): array
    {
        $fds = [];
        foreach($this->table as $fd => $row){
            if(in_array($row['user_id'], $userIds, true)){
                $fds[] = $fd;
            }
        }
        return $fds;
    }

    public function pruneDeadFds($server): void
    {
        foreach ($this->table as $fd => $row) {
            if (!$server->isEstablished($fd)) {
                $this->remove($fd);
            }
        }
    }
}