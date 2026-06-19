<?php
namespace Spn\Controllers;

use Spn\Exceptions\AuthException;
use Spn\Exceptions\DatabaseException;
use Spn\Exceptions\HandlesExceptions;
use Spn\Service\AuthService;

class AuthController
{
    use HandlesExceptions;

    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService;
    }

    public function showRegister(): void
    {
        require __DIR__ . '/../../views/auth/register.php';
    }

    public function showEmailVerify(): void
    {
        require __DIR__ . '/../../views/auth/verify_email.php';
    }

    public function showLogin(): void
    {
        require __DIR__ . '/../../views/auth/login.php';
    }

    public function showPasswordReset(): void
    {
        require __DIR__ . '/../../views/auth/password_reset.php';
    }

    public function login(): void
    {
        try{
            $data = [
                'username' => htmlspecialchars($_POST['username']),
                'password' => htmlspecialchars($_POST['password'])
            ];

            $user = $this->auth->login($data);
            $_SESSION['user']['id'] = $user['id'];
            $_SESSION['user']['username'] = $user['username'];
            $_SESSION['user']['email'] = $user['email'];

            header('Location: /chat');
            exit;
        }
        catch(AuthException $e){
            $this->redirectWithFlash('/login', 'error', $e->getMessage());
        }
        catch(DatabaseException $e){
            error_log($e->getMessage());
            $this->redirectWithFlash('/login', 'error', 'Noe gikk galt! Vennligst prøv igjen');
        }
        catch(\Exception $e){
            error_log($e->getMessage());
            $this->redirectWithFlash('/login', 'error', 'Ukjent feil!');
        }
    }

    public function register(): void
    {
        try{
            $data = [
                'username' => htmlspecialchars($_POST['username']),
                'password' => htmlspecialchars($_POST['password']),
                'email' => htmlspecialchars($_POST['email'])
            ];

            $this->auth->register($data);
            $this->redirectWithFlash('/register', 'success', "Verifiserings e-post er sendt til {$data['email']}");
        }
        catch(AuthException $e){
            $this->redirectWithFlash('/register', 'error', $e->getMessage());
        }
        catch(DatabaseException $e){
            error_log($e->getMessage());
            $this->redirectWithFlash('/register', 'error', 'Noe gikk galt! Vennligst prøv igjen');
        }
        catch(\Exception $e){
            error_log($e->getMessage());
            $this->redirectWithFlash('/register', 'error', 'Ukjent feil!');
        }
    }

    public function handleEmailToken(): void
    {
        header('Content-Type: application/json');
        $body  = json_decode(file_get_contents('php://input'), true);
        $token = $body['token'] ?? '';

        try{
            $this->auth->verifyEmail($token);
            echo json_encode(['class' => 'success']);
        }
        catch (AuthException $e){
            $this->jsonError('Noe gikk galt, vennligst prøv igjen!');
        }
        catch (DatabaseException $e){
            error_log($e->getMessage());
            $this->jsonError('Noe gikk galt, vennligst prøv igjen!');
        }
        catch (\Exception $e){
            error_log($e->getMessage());
            $this->jsonError('Ukjent feil!');
        }
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /login');
        exit;
    }
}