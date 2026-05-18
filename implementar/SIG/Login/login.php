<?php
session_start();
if (!empty($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/login.class.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rpe = $_POST['rpe'] ?? '';
    $psw = $_POST['psw'] ?? '';
    $login = new Login();
    $result = $login->validar($rpe, $psw);
    if ($result['ok']) {
        $_SESSION['user'] = $result['user'];
        $_SESSION['ultimoAcceso'] = time();
        header('Location: ../index.php');
        exit;
    }
    $error = $result['error'] ?? 'Error al iniciar sesión';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIG Laboratorio DVMC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center">
    <div class="card shadow-sm" style="width: 100%; max-width: 380px;">
        <div class="card-body p-4">
            <h1 class="h5 card-title text-center mb-2">SIG</h1>
            <p class="text-muted text-center small mb-4">Carga de documentos</p>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="">
                <div class="mb-3">
                    <label for="rpe" class="form-label small">Usuario (RPE)</label>
                    <input type="text" id="rpe" name="rpe" class="form-control" required
                           value="<?= htmlspecialchars($_POST['rpe'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="psw" class="form-label small">Contraseña</label>
                    <input type="password" id="psw" name="psw" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
