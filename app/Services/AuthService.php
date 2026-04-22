<?php
namespace App\Services;

use App\Repositories\UserRepositoryInterface;

class AuthService
{
    private UserRepositoryInterface $userRepo;
    private string $sessionKey = 'sigtae_user_id';
    private string $basePath = '';
    private ?AdDirectoryValidationService $adValidator;
    private bool $authLocalPasswordFallback;

    public function __construct(UserRepositoryInterface $userRepo, array $options = [])
    {
        $this->userRepo = $userRepo;
        $this->basePath = rtrim($options['base_path'] ?? '', '/');
        $this->adValidator = $options['ad_validator'] ?? null;
        $this->authLocalPasswordFallback = (bool)($options['auth_local_password_fallback'] ?? false);
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
        $rpe = trim($rpe);

        $ad = $this->adValidator;
        if ($ad && $ad->isEnabled()) {
            $vr = $ad->validate($rpe, $password);
            if ($vr['outcome'] === 'ok') {
                $user = $this->userRepo->findByRpe($rpe);
                if (!$user || !($user['activo'] ?? true)) {
                    return ['ok' => false, 'message' => 'El usuario no tiene permitido el acceso al sistema.'];
                }
                return $this->establishSession($user);
            }
            if ($vr['outcome'] === 'invalid') {
                return ['ok' => false, 'message' => 'Credenciales incorrectas.'];
            }
            if ($this->authLocalPasswordFallback) {
                return $this->loginWithLocalPassword($rpe, $password);
            }
            return [
                'ok' => false,
                'message' => 'No fue posible conectar con el directorio activo. Intente más tarde.',
            ];
        }

        return $this->loginWithLocalPassword($rpe, $password);
    }

    private function loginWithLocalPassword(string $rpe, string $password): array
    {
        $user = $this->userRepo->findByRpe($rpe);
        if (!$user || !($user['activo'] ?? true)) {
            return ['ok' => false, 'message' => 'Credenciales incorrectas.'];
        }
        if (!password_verify($password, $user['password_hash'] ?? '')) {
            return ['ok' => false, 'message' => 'Credenciales incorrectas.'];
        }
        return $this->establishSession($user);
    }

    private function establishSession(array $user): array
    {
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
