<?php
namespace Spn\Controllers;

use Spn\Exceptions\DatabaseException;
use Spn\Exceptions\HandlesExceptions;
use Spn\Exceptions\ValidationException;
use Spn\Service\ChatService;
use Spn\Service\ConversationService;

class ChatController
{
    use HandlesExceptions;

    private ChatService $chat;
    private ConversationService $conversation;

    public function __construct()
    {
        $this->chat = new ChatService;
        $this->conversation = new ConversationService;
    }

    public function showChat(): void
    {
        try{
            $_SESSION['user']['wsToken'] = $this->chat->createWsToken($_SESSION['user']['id']);
            require __DIR__ . '/../../views/chat/main.php';
        }
        catch(ValidationException $e){
            error_log($e->getMessage());
            $this->redirectWithFlash('/chat', 'error', 'Kunne ikke opprette WS-tilkobling, prøv igjen.');
        }
        catch(DatabaseException $e){
            error_log($e->getMessage());
            $this->redirectWithFlash('/chat', 'error', 'Noe gikk galt! Vennligst prøv igjen.');
        }
    }

    public function getUserLogs(): void
    {
        header('Content-Type: application/json');
        try{
            echo json_encode([
                'public' => $this->chat->getChat(),
                'conversations' => $this->conversation->getConversations($_SESSION['user']['id']),
            ]);
        }
        catch
        (DatabaseException $e){
            error_log($e->getMessage());
            $this->jsonError('Noe gikk galt! Vennligst prøv igjen.');
        }
        catch(\Exception $e){
            error_log($e->getMessage());
            $this->jsonError('Ukjent feil!');
        }
    }
}