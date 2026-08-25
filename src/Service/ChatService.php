<?php
namespace Spn\Service;

use Spn\Exceptions\ChatException;
use Spn\Exceptions\ValidationException;
use Spn\Repository\ChatRepository;
use Spn\Repository\UserRepository;

class ChatService
{
    private ChatRepository $chatRepo;
    private UserRepository $userRepo;
    private ConversationService $convService;

    public function __construct()
    {
        $this->chatRepo = new ChatRepository;
        $this->userRepo = new UserRepository;
        $this->convService = new ConversationService;
    }

    public function createWsToken(int $userId): string
    {
        if(!empty($_SESSION['user']['wsToken']) && !empty($_SESSION['user']['wsTokenExp']) && (int)$_SESSION['user']['wsTokenExp'] > time() + 60){
            return $_SESSION['user']['wsToken'];
        }

        $token = bin2hex(random_bytes(32));
        $expireAt = time() + 300;

        if(!$this->userRepo->upsertToken($token, $userId, $expireAt)){
            throw new ValidationException("Couldn't save WS token");
        }

        $_SESSION['user']['wsTokenExp'] = $expireAt;
        return $token;
    }

    public function getChat(): array
    {
        return $this->chatRepo->getPublicMessages();
    }

    public function sendMessage(array $msg): array
    {
        if(empty($msg['conv_id'])){
            $newMsg = $this->chatRepo->savePublicMessage($msg);
            if(!$newMsg){
                throw new ChatException("Kunne ikke dytte PublicMessage!");
            }
            $msg['id'] = $newMsg['id'];
            $msg['date_sent'] = $newMsg['date_sent'];
            return $msg;
        }

        $msg['participant_ids'] = $this->convService->getConvMembersByConvId($msg['conv_id']);
        $newMsg = $this->chatRepo->savePrivateMessage($msg);

        if(!$newMsg){
            throw new ChatException("Kunne ikke dytte PrivateMessage!");
        }

        $msg['id'] = $newMsg['id'];
        $msg['date_sent'] = $newMsg['date_sent'];
        return $msg;
    }

    public function removeMessage(int $msgId, int $userId, ?int $convId = null): bool|array
    {
        if(!$convId){
            if(!$this->chatRepo->removePublicMessage($msgId, $userId)){
                throw new ChatException("Kunne ikke slette public message: $msgId");
            }
            return true;
        }

        if(!$this->chatRepo->removePrivateMessage($msgId, $userId, $convId)){
            throw new ChatException("Kunne ikke slette private message: $msgId");
        }

        return $this->convService->getConvMembersByConvId($convId);
    }
}