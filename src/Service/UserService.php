<?php
namespace Spn\Service;

use Exception;
use Spn\Repository\UserRepository;

class UserService{
    private UserRepository $user;

    public function __construct()
    {
        $this->user = new UserRepository;
    }

    public function getUserFromToken(string $token)
    {
        $user = $this->user->findByToken($token);
        if(!$user){
            throw new \Spn\Exceptions\UserException("Couldn't Find User By Token");
        }

        if((int)$user['expires_at'] < time()){
            $this->user->removeExpiredToken();
            return null;
        }

        return (int)$user['user_id'];
    }

    public function deleteUser(int $userId, string $userPwd): bool
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
}