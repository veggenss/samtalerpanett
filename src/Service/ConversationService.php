<?php
namespace Spn\Service;

use Spn\Exceptions\ValidationException;
use Spn\Repository\ConversationRepository;
use Spn\Repository\UserRepository;

class ConversationService
{
    private ConversationRepository $convRepo;
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->convRepo = new ConversationRepository;
        $this->userRepo = new UserRepository;
    }

    public function getConversations(int $userId): array
    {
        return $this->convRepo->getConversations($userId);
    }

    public function makeConversation(int $userId, array $data): array|bool
    {
        $participants = [];

        foreach($data['parties'] as $party){
            $participants[] = (int)$this->userRepo->findByName($party)['id'];
        }

        if(in_array($userId, $participants)){
            throw new ValidationException("Kan ikke starte samtale med degselv!");
        }

        $participants[] = $userId;

        $userIds = $participants
            |> (fn($arr) => array_map('intval', $arr))
            |> (fn($arr) => array_unique($arr))
            |> (fn($arr) => array_values($arr));

        return $this->convRepo->makeConversation($userIds, $data['title']) ?: false;
    }

    public function getConvMembersByConvId(int $convId): array
    {
        return $this->convRepo->getConvMembersByConvId($convId);
    }

    public function removeConversationMember(int $userId, int $convId): bool
    {
        return false;
    }

    public function removeConversation(int $id, int $userId): bool
    {
        return false;
    }
}