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

    public function getConversations(int $id): array
    {
        try{
            $convStmt = $this->conn->prepare('
                SELECT c.id, c.title, c.date_added AS conv_created, c.latest_message, GROUP_CONCAT(DISTINCT u.username ORDER BY u.username) AS participants, GROUP_CONCAT(DISTINCT u.id ORDER BY u.id) AS participant_ids
                FROM conversations c
                JOIN conversation_members cm ON c.id = cm.conversation_id
                JOIN users u ON cm.user_id = u.id
                WHERE c.id IN (SELECT conversation_id FROM conversation_members WHERE user_id = ?)
                GROUP BY c.id ORDER BY c.date_added ASC;
            ');
            $convStmt->bind_param("i", $id);
            $convStmt->execute();
            $convRes = $convStmt->get_result();

            $conversations = $convRes->fetch_all(MYSQLI_ASSOC);
            $convRes->free();
            $convStmt->close();

            $msgStmt = $this->conn->prepare('
                SELECT pm.*, sender.username AS sender_username FROM private_messages pm
                JOIN users sender ON pm.sender_id = sender.id
                WHERE pm.conversation_id IN (SELECT conversation_id FROM conversation_members WHERE user_id = ?)
                ORDER BY pm.conversation_id, pm.date_sent ASC, pm.id ASC;
            ');
            $msgStmt->bind_param("i", $id);
            $msgStmt->execute();
            $msgRes = $msgStmt->get_result();

            $messages = $msgRes->fetch_all(MYSQLI_ASSOC);
            $msgRes->free();
            $msgStmt->close();

            $convArr = [];

            foreach($conversations as $conv){
                $conv['participants'] = explode(',', $conv['participants']);
                $conv['participant_ids'] = $conv['participant_ids']
                    |> (fn($arr) => explode(',', $arr))
                    |> (fn($arr) => array_map('intval', $arr));
                $conv['messages'] = [];
                $convArr[$conv['id']] = $conv;
            }

            foreach($messages as $msg){
                if(isset($convArr[$msg['conversation_id']])){
                    $convArr[$msg['conversation_id']]['messages'][] = $msg;
                }
            }

            return array_values($convArr); //array_values since PHP converts it to object otherwise
        }
        catch(\mysqli_sql_exception $e){
            error_log($e->getMessage());
            throw new \Spn\Exceptions\DatabaseException("Get Conversations Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function getConvMembersByConvId(int $conv_id): array
    {
        try{
            $stmt = $this->conn->prepare('
                SELECT cm.user_id FROM conversation_members cm
                JOIN conversations c ON cm.conversation_id = c.id
                WHERE c.id = ?;
            ');
            $stmt->bind_param("i", $conv_id);
            $stmt->execute();

            $stmtRes = $stmt->get_result();
            $stmt->close();

            $participants = [];

            while($row = $stmtRes->fetch_column()){
                $participants[] = $row;
            }

            $stmtRes->free();

            return $participants;
        }
        catch(\mysqli_sql_exception $e){
            error_log($e->getMessage());
            throw new \Spn\Exceptions\DatabaseException("Get ConvMembersByConvID Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function makeConversation(array $userIds, string $title): int
    {
        $this->conn->begin_transaction();
        try{
            $stmt = $this->conn->prepare('INSERT INTO conversations (title) VALUES (?);');
            $stmt->bind_param("s", $title);
            $stmt->execute();
            $stmt->close();
            $conv_id = $this->conn->insert_id;

            $placeholders = implode(',', array_fill(0, count($userIds), '(?, ?)'));
            $types = str_repeat('ii', count($userIds));
            $params = [];

            foreach($userIds as $id){
                $params[] = $conv_id;
                $params[] = $id;
            }

            $stmt = $this->conn->prepare("INSERT INTO conversation_members (conversation_id, user_id) VALUES $placeholders;");
            $stmt->bind_param($types, ...$params);

            $stmt->execute();

            $this->conn->commit();
            $stmt->close();
            return $conv_id;
        }
        catch(\mysqli_sql_exception $e){
            $this->conn->rollback();
            error_log($e->getMessage());
            throw new \Spn\Exceptions\DatabaseException("makeConversation Failed: " . $e->getMessage(), 0, $e);
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

    public function removeConversationMember(int $convId, $userId): bool
    {
        try{
            $stmt = $this->conn->prepare('DELETE FROM conversation_members cm WHERE cm.conversation_id = ? AND cm.user_id = ?');
            $stmt->bind_param("ii", $convId, $userId);

            $status = $stmt->execute();
            $stmt->close();
            return $status;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Remove Conversation Member Failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function removeConversation(int $convId): bool
    {
        try{
            $stmt = $this->conn->prepare('DELETE FROM conversations c WHERE c.id = ?');
            $stmt->bind_param("i", $convId);

            $status = $stmt->execute();
            $stmt->close();
            return $status;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Conversation Deletion Failed: " . $e->getMessage(), 0, $e);
        }
    }
}
