<?php
namespace Spn\Controllers;

use Spn\Exceptions\DatabaseException;
use Spn\Exceptions\HandlesExceptions;
use Spn\Exceptions\ValidationException;
use Spn\Service\ConversationService;

class ConversationController
{
    use HandlesExceptions;

    private ConversationService $conversation;

    public function __construct()
    {
        $this->conversation = new ConversationService;
    }

    public function makeConversation(): void
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        try{
            echo json_encode([
                'conversation' => $this->conversation->makeConversation($_SESSION['user']['id'], $data),
            ]);
        }
        catch(ValidationException $e){
            $this->jsonError($e->getMessage());
        }
        catch(DatabaseException $e){
            error_log($e->getMessage());
            $this->jsonError('Noe gikk galt! Vennligst prøv igjen');
        }
        catch(\Exception $e){
            error_log($e->getMessage());
            $this->jsonError('Ukjent feil!');
        }
    }
}