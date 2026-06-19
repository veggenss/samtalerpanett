<?php
namespace Spn\Controllers;

use Spn\Exceptions\DatabaseException;
use Spn\Exceptions\HandlesExceptions;
use Spn\Exceptions\UserException;
use Spn\Service\ProfileService;

class ProfileController
{
    use HandlesExceptions;

    private ProfileService $profile;

    public function __construct()
    {
        $this->profile = new ProfileService;
    }

    public function showProfile(): void
    {
        require __DIR__ . '/../../views/profile/profile.php';
    }

    public function updateProfile(): void
    {
        header('Content-Type: application/json');

        try{
            $saved = $this->profile->updateProfile($_SESSION['user']['id'], $_POST);

            if(!$saved){
                $this->jsonError('Profiloppdatering er ikke implementert enda.');
            }

            echo json_encode(['class' => 'success']);
        }
        catch(UserException $e){
            $this->jsonError($e->getMessage());
        }
        catch(DatabaseException $e){
            error_log($e->getMessage());
            $this->jsonError('Ukjent feil! Vennligst prøv igjen.');
        }
    }

    public function deleteUser(): void
    {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $password = $data['password'] ?? null;

        try{
            if(!$password){
                throw new UserException('Passord udefinert!');
            }

            $this->profile->deleteAccount($_SESSION['user']['id'], $password);
            session_destroy();
            echo json_encode(['class' => 'success']);
        }
        catch(UserException $e){
            $this->jsonError($e->getMessage());
        }
        catch(DatabaseException $e){
            error_log($e->getMessage());
            $this->jsonError('Ukjent feil! Vennligst prøv igjen.');
        }
    }
}