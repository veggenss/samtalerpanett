<?php
namespace Spn\Controllers;

use Spn\Service\UserService;

class UserController{
    private UserService $user;

    public function __construct()
    {
        $this->user = new UserService;
    }

    public function showProfile(): void
    {
        require __DIR__ . '/../../views/user/profile.php';
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function updateProfile(): void
    {

    }

    public function deleteUser(): void
    {
        $data = json_decode(file_get_contents("php://input"));
        $password = $data->password ?? null;

        try{
            if(!$password) throw new \Spn\Exceptions\UserException("Passord Udefinert!");

            $this->user->deleteUser($_SESSION['user']['id'], $password);
            session_destroy();
            echo json_encode(['class' => 'success']);
            exit;
        }
        catch(\Spn\Exceptions\UserException $e){
            echo json_encode([
                "class" => "error",
                "message" => $e->getMessage()
            ]);
            exit;
        }
        catch(\Spn\Exceptions\DatabaseException $e){
            echo json_encode([
                "class" => "error",
                "message" => "Ukjent feil! Vennligst prøv igjen."
            ]);
            exit;
        }
    }
}