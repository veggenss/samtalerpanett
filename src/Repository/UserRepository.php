<?php
namespace Spn\Repository;

use Spn\Database\Connection;

class UserRepository{
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::get();
    }

    public function findById(int $id): mixed
    {
        $stmt = $this->conn->prepare('SELECT * FROM users WHERE id = ? AND deleted = 0');
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $res->free();
        $stmt->close();
        return $user;
    }

    public function findByName(string $username): mixed
    {
        try{
            $stmt = $this->conn->prepare('SELECT * FROM users WHERE username = ? AND deleted = 0');
            $stmt->bind_param("s", $username);
            $stmt->execute();

            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $res->free();
            $stmt->close();
            return $user;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Failed to findByName: " . $e->getMessage(), 0, $e);
        }
    }

    public function findEmailToken(string $token): array|bool
    {
        try{
            $stmt = $this->conn->prepare('SELECT * FROM users WHERE verify_email = ?');
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $res = $stmt->get_result();
            $user = $res->fetch_assoc();

            $res->free();
            $stmt->close();
            return $user ?: true;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Failed to findEmailToken: " . $e->getMessage(), 0, $e);
        }
    }

    public function findByToken(string $token)
    {
        try{
            $stmt = $this->conn->prepare('SELECT * FROM user_tokens WHERE token = ?');
            $stmt->bind_param("s", $token);
            $stmt->execute();

            $res = $stmt->get_result();
            $user = $res->fetch_assoc();

            $res->free();
            $stmt->close();
            return $user;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Failed to findByToken: " . $e->getMessage(), 0, $e);
        }
    }

    public function saveToken(string $token, int $userId, int $expire_at): bool
    {
        try{
            $stmt = $this->conn->prepare("INSERT INTO user_tokens (token, user_id, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $token, $userId, $expire_at);

            $res = $stmt->execute();
            $stmt->close();
            return $res;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Failed to saveToken: " . $e->getMessage(), 0, $e);
        }
    }

    public function saveUser(array $data): bool
    {
        try{
            $stmt = $this->conn->prepare('INSERT INTO users (username, password, email, verify_email) VALUES (?, ?, ?, ?)');
            $stmt->bind_param("ssss", $data['username'], $data['password'], $data['email'], $data['emailToken']);

            $user = $stmt->execute();
            $stmt->close();
            return $user;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Failed to save user: " . $e->getMessage(), 0, $e);
        }
    }

    public function verifyEmailByToken(string $token): bool
    {
        try{
            $stmt = $this->conn->prepare('UPDATE users SET verify_email = true WHERE verify_email = ?');
            $stmt->bind_param("s", $token);

            $res = $stmt->execute();
            $stmt->close();
            return $res;
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Failed to verifyEmailByToken: " . $e->getMessage(), 0, $e);
        }
    }

    public function removeToken(int $userId): void
    {
        try{
           $stmt = $this->conn->prepare('DELETE FROM user_tokens WHERE user_id = ?');
           $stmt->bind_param("i", $userId);

           $stmt->execute();
           $stmt->close();
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Failed to findTokenById: " . $e->getMessage(), 0, $e);
        }
    }

    public function removeExpiredToken(): void
    {
        try{
            //run only 10% of the time to avoid overhead
            if(random_int(1, 10) === 1){
                $this->conn->execute_query("DELETE FROM user_tokens WHERE expires_at < NOW()");
            }
        }
        catch(\mysqli_sql_exception $e){
            throw new \Spn\Exceptions\DatabaseException("Failed to remove expired tokens: " . $e->getMessage(), 0, $e);
        }
    }

    public function removeUser(int $id): bool
    {
        $deletedUsername = "deleted_user" . bin2hex(random_bytes(6));
        $deletedEmail = "deleted_" . $id . "@deleted.invalid";
        try {
            $stmt = $this->conn->prepare("UPDATE users SET username = ?, password = ?, verify_email = null, deleted = true WHERE id = ?");
            $stmt->bind_param("ssi", $deletedUsername, $deletedEmail, $id);

            $status = $stmt->execute();
            $stmt->close();
            return $status;
        }
        catch(\mysqli_sql_exception $e){
           throw new \Spn\Exceptions\DatabaseException("Failed to remove user: " . $e->getMessage(), 0, $e);
        }
    }
}