<?php
namespace Spn\Service;

use Spn\Repository\UserRepository;

class ProfileService{
    private UserRepository $user;

    public function __construct()
    {
        $this->user = new UserRepository;
    }

    public function deleteAccount(int $userId, string $userPwd): bool
    {
        if(!$userId || !$userPwd){
            throw new \Spn\Exceptions\UserException("UserId / UserPwd er Udefinert!");
        }

        $user = $this->user->findById($userId);

        if(!$user){
            throw new \Spn\Exceptions\UserException("Kunne ikke finne bruker!");
        }

        if(!password_verify($userPwd, $user['password'])){
            throw new \Spn\Exceptions\UserException("Ugyldig passord!");
        }

        return $this->user->removeUser($userId);
    }

    public function updateProfile(int $userId, array $data): bool
    {
        // TODO: not yet implemented - no persistence layer exists yet for
        // editable profile fields (username/email/password/profile picture).
        // Wiring is fixed up so this no longer 500s, but the actual save
        // logic still needs to be designed before this does anything.
        return false;
    }
}
