<?php
namespace Spn\Service;

use Spn\Repository\UserRepository;

class AuthService{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository;
    }

    private function sendVerificationEmail(string $to, string $token): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try{
            $mail->isSMTP();
            $mail->Host = $_ENV['MAIL_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['MAIL_USERNAME'];
            $mail->Password = $_ENV['MAIL_PASSWORD'];
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $mail->Port = $_ENV['MAIL_PORT'];

            $mail->setFrom($_ENV['MAIL_USERNAME'], 'Samtaler på nett');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = 'Bekreft e-posten din hos Samtaler på nett';

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $basePath = dirname($_SERVER['SCRIPT_NAME']);

            $verificationUrl = "$protocol://$host$basePath" . "/verify-email?token=$token";

            $mail->CharSet = 'UTF-8';
            $mail->Body = "
                <div style='
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    padding: 20px;
                    border-radius: 8px;
                    max-width: 600px;
                    margin: 0 auto;
                    color: #333;
                '>
                    <h2 style='color: #2c3e50;'>Hei<strong></strong></h2>
                    <p>Klikk på knappen under for å bekrefte e-posten din:</p>
                    <p style='text-align: center;'>
                        <a href='$verificationUrl' style='
                            display: inline-block;
                            padding: 12px 20px;
                            background-color: #3498db;
                            color: #fff;
                            text-decoration: none;
                            border-radius: 5px;
                            font-weight: bold;
                        '>Bekreft e-post</a>
                    </p>
                    <p style='font-size: 12px; color: #888;'>Hvis du ikke ba om denne e-posten, kan du bare ignorere den.</p>
                </div>
            ";

            return $mail->send();
        }
        catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log('PHPMailer: ' . $mail->ErrorInfo .  "\nException: " . $e->getMessage(), 0, true);
            return false;
        }
    }

    public function login(array $data): bool|array
    {
        $user = $this->userRepo->findByName($data['username']);
        if(!$user || !password_verify($data['password'], $user['password'])){
            throw new \Spn\Exceptions\AuthException("Feil brukernavn eller passord");
        }
        if(!($user['verify_email'] == true)){
            throw new \Spn\Exceptions\AuthException("Du må verifisere Epost!");
        }
        return $user;
    }

    public function register(array $data): bool
    {
        if($this->userRepo->findByName($data['username'])){
            throw new \Spn\Exceptions\AuthException("Username already exists");
        }

        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $data['emailToken'] = bin2hex(random_bytes(32));

        $saved = $this->userRepo->saveUser($data);

        if(!$saved){
            throw new \Spn\Exceptions\AuthException("Kunne ikke registrere!");
        }

        $this->sendVerificationEmail($data['email'], $data['emailToken']);

        return true;
    }

    public function verifyEmail(string $token): bool
    {
        if(!$this->userRepo->findEmailToken($token)){
            throw new \Spn\Exceptions\AuthException("Kunne ikke finne token!");
        }
        return $this->userRepo->verifyEmailByToken($token);
    }

    public function delete(array $data): bool
    {
        return false;
    }
}