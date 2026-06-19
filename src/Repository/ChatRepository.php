<?php
namespace Spn\Repository;

use Spn\Database\Connection;

class ChatRepository{
    private \mysqli $conn;

    public function __construct()
    {
        $this->conn = Connection::get();
    }

    public function getPublicMessages(): array
    {
        try{
            $stmt = $this->conn->query('SELECT pm.*, u.username FROM public_messages pm INNER JOIN users u ON pm.sender_id = u.id ORDER BY date_added ASC, id ASC;');
            $pm = $stmt->fetch_all(MYSQLI_ASSOC);
            $stmt->free_result();
            return $pm;
        }
        catch(\mysqli_sql_exception $e){
            error_log($e->getMessage());
            throw new \Spn\Exceptions\DatabaseException("Get Public Messages Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function savePrivateMessage(array $data): array
    {
        try{
            $stmt = $this->conn->prepare('INSERT INTO private_messages (conversation_id, sender_id, message) VALUES (?, ?, ?) RETURNING id, date_sent;');
            $stmt->bind_param("iis", $data['conv_id'], $data['sender_id'], $data['message']);
            $stmt->execute();

            $stmtRes = $stmt->get_result();
            $stmt->close();

            $msgData = $stmtRes->fetch_assoc();
            $stmtRes->free();

            return $msgData;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Private Message Insetion Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function savePublicMessage(array $data): array
    {
        try{
            $stmt = $this->conn->prepare('INSERT INTO public_messages (sender_id, message) VALUES (?, ?) RETURNING id, date_sent;');
            $stmt->bind_param("is", $data['sender_id'], $data['message']);
            $stmt->execute();

            $stmtRes = $stmt->get_result();
            $stmt->close();

            $msgData = $stmtRes->fetch_assoc();
            $stmtRes->free();

            return $msgData;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Public Message Insetion Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function removePrivateMessage(int $msgId, int $userId, int $convId): bool
    {
        try{
            $stmt = $this->conn->prepare('DELETE FROM private_messages WHERE id = ? AND sender_id = ? AND conversation_id = ?');
            $stmt->bind_param("iii", $msgId, $userId, $convId);

            $status = $stmt->execute();
            $stmt->close();
            return $status;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Private Message Deletion Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function removePublicMessage(int $msgId, int $userId): bool
    {
        try{
            $stmt = $this->conn->prepare('DELETE FROM public_messages WHERE id = ? AND sender_id = ?');
            $stmt->bind_param("ii", $msgId, $userId);

            $status = $stmt->execute();
            $stmt->close();
            return $status;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Public Message Deletion Failed: " . $e->getMessage(), 0, $e);
        }
    }
}
