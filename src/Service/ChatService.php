<?php
namespace Spn\Service;

use Spn\Exceptions\ChatException;
use Spn\Repository\ChatRepository;
use Spn\Repository\UserRepository;

class ChatService{
    private ChatRepository $chatRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->chatRepo = new ChatRepository;
        $this->userRepo = new UserRepository;
    }

    public function createWsToken($user_id): string
    {
        $this->userRepo->removeToken($user_id);

        $token = bin2hex(random_bytes(32));
        $expireAt = time() + 1200;
        if(!$this->userRepo->saveToken($token, $user_id, $expireAt)){
            throw new \Spn\Exceptions\InvalException("Couldn't save User Token");
        }
        return $token;
    }

    public function getChat(): array
    {
        return $this->chatRepo->getPublicMessages();
    }

    public function getConversations(int $user_id): array
    {
        return $this->chatRepo->getConversations($user_id);
    }

    public function makeConversation(int $userId, array $data): array|bool
    {
        $participants = [];

        foreach($data['parties'] as $party){
            $participants[] = (int)$this->userRepo->findByName($party)['id'];
        }

        if(in_array($userId, $participants)){
            throw new \Spn\Exceptions\InvalException("Kan ikke starte samtale med degselv!");
        }

        $participants[] = $userId;

        $userIds = $participants
            |> (fn($arr) => array_map('intval', $arr))
            |> (fn($arr) => array_unique($arr))
            |> (fn($arr) => array_values($arr));
        return $this->chatRepo->makeConversation($userIds, $data['title']) ?: false;
    }

    public function sendMessage(array $msg): array
    {
        if(empty($msg['conv_id'])){
            $newMsg = $this->chatRepo->savePublicMessage($msg);
            if(!$newMsg){
                throw new \Spn\Exceptions\ChatException("Kunne ikke dytte PublicMessage!");
            }
            $msg['id'] = $newMsg['id'];
            $msg['date_sent'] = $newMsg['date_sent'];
            return $msg;
        }

        $msg['participant_ids'] = $this->chatRepo->getConvMembersByConvId($msg['conv_id']);
        $newMsg = $this->chatRepo->savePrivateMessage($msg);

        if(!$newMsg){
           throw new \Spn\Exceptions\ChatException("Kunne ikke dytte PrivateMessage!");
        }

        $msg['id'] = $newMsg['id'];
        $msg['date_sent'] = $newMsg['date_sent'];

        return $msg;
    }

    public function removeMessage(int $msgId, int $userId, ?int $convId = null): bool|array
    {
        if(!$convId){
            if(!$this->chatRepo->removePublicMessage($msgId, $userId)){
                throw new ChatException("Kunne ikke slette public message: ", $msgId);
            }
            return true;
        }

        if(!$this->chatRepo->removePrivateMessage($msgId, $userId, $convId)){
            throw new ChatException("Kunne ikke slette private message: ", $msgId);
        }

        return $this->chatRepo->getConvMembersByConvId($convId);
    }

    public function removeConversationMember(int $userId, int $convId): bool
    {
        return false;
    }

    public function removeConversation(int $id, int $user_id): bool
    {
        return false;
    }
}