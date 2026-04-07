<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = new \App\Services\AuthService($container['repositories']['user'], ['base_path' => $basePath]);
$auth->startSession();
$user = $auth->currentUser();
if ($user) {
    header('Location: ' . $basePath . '/dashboard.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = trim($_POST['rpe'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($rpe === '' || $password === '') {
        $error = 'Indique RPE y contraseña.';
    } else {
        $result = $auth->login($rpe, $password);
        if ($result['ok']) {
            header('Location: ' . $basePath . '/dashboard.php');
            exit;
        }
        $error = $result['message'];
    }
}
$pageTitle = 'Iniciar sesión — SIGTAE';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sigtae-navy: #0c2340;
            --sigtae-petrol: #1a4d6d;
            --sigtae-cyan: #4a9fb8;
            --sigtae-cyan-soft: #e8f4f8;
            --sigtae-gray: #5c6b7a;
            --sigtae-white: #f8fafc;
        }
        body { background: linear-gradient(145deg, var(--sigtae-navy) 0%, var(--sigtae-petrol) 50%, #0f3d52 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', system-ui, sans-serif; }
        .login-card { background: rgba(255,255,255,0.98); border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.25); overflow: hidden; max-width: 400px; }
        .login-header { background: linear-gradient(135deg, var(--sigtae-petrol), var(--sigtae-cyan)); color: #fff; padding: 1.5rem; text-align: center; }
        .login-header h1 { font-size: 1.35rem; font-weight: 700; margin: 0; }
        .login-header p { margin: 0.25rem 0 0; opacity: 0.95; font-size: 0.9rem; }
        .login-body { padding: 1.75rem; }
        .form-control:focus { border-color: var(--sigtae-cyan); box-shadow: 0 0 0 0.2rem rgba(74,159,184,0.25); }
        .btn-sigtae { background: linear-gradient(135deg, var(--sigtae-petrol), var(--sigtae-cyan)); border: none; color: #fff; font-weight: 600; padding: 0.6rem 1.25rem; }
        .btn-sigtae:hover { background: linear-gradient(135deg, #153d52, #3d8aa0); color: #fff; }
        .alert { border-radius: 10px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h1><i class="bi bi-clipboard-check"></i> SIGTAE</h1>
            <p>Laboratorio de Medición</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= htmlspecialchars($basePath) ?>/login.php">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary">RPE</label>
                    <input type="text" name="rpe" class="form-control" placeholder="RPE" value="<?= htmlspecialchars($_POST['rpe'] ?? '') ?>" autofocus autocomplete="username">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="Contraseña">
                </div>
                <button type="submit" class="btn btn-sigtae w-100">Iniciar sesión</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
