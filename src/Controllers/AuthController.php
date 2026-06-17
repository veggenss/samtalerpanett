<?php
namespace Spn\Controllers;

use Spn\Service\AuthService;

class AuthController{
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
        catch(\Spn\Exceptions\AuthException $e){
            $_SESSION['flash'] = [
                "class" => "error",
                "message" => $e->getMessage()
            ];
            header('Location: /login');
            exit;
        }
        // catch(\PHPMailer\PHPMailer\Exception $e){
        //     throw new \Exception;
        // }
        catch(\Spn\Exceptions\DatabaseException $e){
            error_log($e->getMessage());
            $_SESSION['flash'] = [
                "class" => "error",
                "message" => "Noe gikk galt! Vennligst prøv igjen"
            ];
            header('Location: /login');
            exit;
        }
        catch(\Exception $e){
            error_log($e->getMessage());
            $_SESSION['flash'] = [
                "class" => "error",
                "message" => "Ukjent feil!"
            ];
            exit;
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

            $_SESSION['flash'] = [
                "class" => "success",
                "message" => "Verifiserings e-post er send til {$data['email']}"
            ];

            header('Location: /register');
            exit;
        }
        catch(\Spn\Exceptions\AuthException $e){
            $_SESSION['flash'] = [
                "class" => "error",
                "message" => $e->getMessage()
            ];
            header('Location: /register');
            exit;
        }
        catch(\Spn\Exceptions\DatabaseException $e){
            error_log($e->getMessage());
            $_SESSION['flash'] = [
                "class" => "error",
                "message" => "Noe gikk galt! Vennligst prøv igjen"
            ];
            header('Location: /register');
            exit;
        }
        catch(\Exception $e){
            error_log($e->getMessage());
            $_SESSION['flash'] = [
                "class" => "error",
                "message" => "Ukjent feil!"
            ];
            exit;
        }
    }

    public function handleEmailToken(): void
    {
        header('Content-Type: application/json');
        $token = json_decode(file_get_contents('php://input'), true);
        try{
            $this->auth->verifyEmail($token['token']);
            echo json_encode([
                "class" => "success"
            ]);
            exit;
        }
        catch(\Spn\Exceptions\AuthException $e){
            echo json_encode([
                "class" => "error",
                "message" => "Something went wrong, uppsie woopsie!"
            ]);
            exit;
        }
    }
}