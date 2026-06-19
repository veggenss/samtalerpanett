<?php
namespace Spn\Exceptions;

trait HandlesExceptions
{
    private function jsonError(string $message): void
    {
        echo json_encode(['class' => 'error', 'message' => $message]);
        exit;
    }

    private function redirectWithFlash(string $to, string $class, string $message): void
    {
        $_SESSION['flash'] = ['class' => $class, 'message' => $message];
        header("Location: $to");
        exit;
    }
}