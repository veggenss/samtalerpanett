<?php
namespace Spn\Controllers;

use Spn\Service\ChatService;

class ChatController{
    private ChatService $chat;

    public function __construct()
    {
        $this->chat = new ChatService;
    }

    public function showChat()
    {
        try{
            $_SESSION['user']['wsToken'] = $this->chat->createWsToken($_SESSION['user']['id']);
            require __DIR__ . '/../../views/chat/main.php';
        }
        catch(\Spn\Exceptions\InvalException $e){
            echo json_encode([
                "class" => "error",
                "message" => "Failed to create WS token!"
            ]);
            exit;
        }
    }

    //fetch relevant logs
    public function getUserLogs(): void
    {
        header('Content-Type: application/json');
        try{
            echo json_encode([
                'public' => $this->chat->getChat(),
                'conversations' => $this->chat->getConversations($_SESSION['user']['id'])
            ]);
            exit;
        }
        catch(\Spn\Exceptions\InvalException $e){
            echo json_encode([
                "class" => "error",
                "message" => $e->getMessage()
            ]);
            exit;
        }
        catch(\Spn\Exceptions\DatabaseException $e){
            error_log($e->getMessage());
            echo json_encode([
                "class" => "error",
                "message" => "Noe gikk galt! Vennligst prøv igjen."
            ]);
            exit;
        }
        catch(\Exception $e){
            error_log($e->getMessage());
            echo json_encode([
                "class" => "error",
                "message" => "Ukjent feil!"
            ]);
            exit;
        }
    }


    //create user conversation
    public function makeConversation(): void
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        try{
            echo json_encode([
                'conversation' => $this->chat->makeConversation($_SESSION['user']['id'], $data)
            ]);
            exit;
        }
        catch(\Spn\Exceptions\InvalException $e){
            echo json_encode([
                "class" => "error",
                "message" => $e->getMessage()
            ]);
            exit;
        }
        catch(\Spn\Exceptions\DatabaseException $e){
            error_log($e->getMessage());
            echo json_encode([
                "class" => "error",
                "message" => "Noe gikk galt! Vennligst prøv igjen"
            ]);
            exit;
        }
        catch(\Exception $e){
            error_log($e->getMessage());
            echo json_encode([
                "class" => "error",
                "message" => "Ukjent feil!"
            ]);
            exit;
        }
    }
}