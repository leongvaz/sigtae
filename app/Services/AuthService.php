<?php
namespace App\Services;

use App\Repositories\UserRepositoryInterface;

class AuthService
{
    private UserRepositoryInterface $userRepo;
    private string $sessionKey = 'sigtae_user_id';
    private string $basePath = '';

    public function __construct(UserRepositoryInterface $userRepo, array $options = [])
    {
        $this->userRepo = $userRepo;
        $this->basePath = rtrim($options['base_path'] ?? '', '/');
    }

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('sigtae_session');
            session_start();
        }
    }

    public function login(string $rpe, string $password): array
    {
        $user = $this->userRepo->findByRpe($rpe);
        if (!$user || !($user['activo'] ?? true)) {
            return ['ok' => false, 'message' => 'Credenciales incorrectas.'];
        }
        if (!password_verify($password, $user['password_hash'] ?? '')) {
            return ['ok' => false, 'message' => 'Credenciales incorrectas.'];
        }
        $this->startSession();
        $_SESSION[$this->sessionKey] = $user['id'];
        return ['ok' => true, 'user' => $this->sanitizeUser($user)];
    }

    public function logout(): void
    {
        $this->startSession();
        unset($_SESSION[$this->sessionKey]);
    }

    public function currentUser(): ?array
    {
        $this->startSession();
        $id = $_SESSION[$this->sessionKey] ?? null;
        if (!$id) {
            return null;
        }
        $user = $this->userRepo->find($id);
        return $user ? $this->sanitizeUser($user) : null;
    }

    public function requireAuth(): array
    {
        $user = $this->currentUser();
        if (!$user) {
            $loginUrl = $this->basePath . '/login.php';
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'No autorizado', 'redirect' => $loginUrl]);
                exit;
            }
            header('Location: ' . $loginUrl);
            exit;
        }
        return $user;
    }

    private function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function sanitizeUser(array $user): array
    {
        $out = $user;
        unset($out['password_hash']);
        return $out;
    }
}
