<?php
$base = dirname(__DIR__);
$container = require $base . '/app/bootstrap.php';
$basePath = $container['base_path'] ?? '';
$auth = sigtae_auth_service($container, $basePath);
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
$year = date('Y');
$rpeValue = htmlspecialchars($_POST['rpe'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= htmlspecialchars($basePath) ?>/assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-layout">
        <section class="login-hero" aria-label="SIGTAE">
            <div class="login-hero__top">
                <div class="login-hero__brand">
                    <div class="login-hero__badge">SIG</div>
                    <div>
                        <div class="login-hero__brand-title">SIGTAE</div>
                        <div class="login-hero__brand-sub">Laboratorio de Medición</div>
                    </div>
                </div>
            </div>

            <div class="login-hero__mid">
                <h1 class="login-hero__title">Gestión integral de tareas del laboratorio.</h1>
                <p class="login-hero__lead">
                    Asigna actividades, da seguimiento por oficina, captura evidencias
                    y evalúa el cumplimiento desde un solo sistema.
                </p>
                <ul class="login-hero__list">
                    <li><span class="dot" aria-hidden="true"></span> Tareas por área: Metrología, Preparación y AMI</li>
                    <li><span class="dot" aria-hidden="true"></span> Seguimiento, calendario y programa de trabajo</li>
                    <li><span class="dot" aria-hidden="true"></span> Evidencias, evaluación y reportes</li>
                </ul>
            </div>

            <div class="login-hero__bot">
                <div class="login-hero__foot">
                    <span>© <?= (int) $year ?> CFE</span>
                    <span class="dotsep" aria-hidden="true">·</span>
                    <span>Uso interno autorizado</span>
                </div>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-card">
                <div class="login-card__head">
                    <h2 class="login-card__title">Inicia sesión</h2>
                    <p class="login-card__sub">Inicia sesión con tu cuenta de directorio activo.</p>
                </div>

                <?php if ($error !== ''): ?>
                    <div class="login-alert" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= htmlspecialchars($basePath) ?>/login.php" class="login-form" autocomplete="on" novalidate>
                    <div class="login-row">
                        <span class="login-row__suffix" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <div class="login-row__control password_input_container">
                            <input
                                id="login-rpe"
                                class="password_input_container__input"
                                name="rpe"
                                type="text"
                                inputmode="text"
                                autocapitalize="characters"
                                spellcheck="false"
                                autocomplete="username"
                                placeholder=" "
                                value="<?= $rpeValue ?>"
                                required
                                autofocus
                            />
                            <label class="password_input_container__label" for="login-rpe">RPE</label>
                        </div>
                    </div>

                    <div class="login-row">
                        <span class="login-row__suffix" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <div class="login-row__control password_input_container">
                            <input
                                id="login-pass"
                                class="password_input_container__input"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                placeholder=" "
                                required
                            />
                            <label class="password_input_container__label" for="login-pass">Contraseña</label>
                            <button type="button" class="password_input_container__toggle" id="login-pass-toggle" aria-label="Mostrar contraseña"></button>
                        </div>
                    </div>

                    <button class="btn--login-submit" type="submit">
                        <span class="btn--login-submit__text">Entrar</span>
                        <svg class="btn--login-submit__chev" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14"></path>
                            <path d="M13 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </section>
    </div>

    <script>
    (function () {
        var btn = document.getElementById('login-pass-toggle');
        var inp = document.getElementById('login-pass');
        if (!btn || !inp) return;

        var svgShow = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>';
        var svgHide = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>';

        btn.innerHTML = svgShow;

        btn.addEventListener('click', function () {
            if (inp.type === 'password') {
                inp.type = 'text';
                btn.innerHTML = svgHide;
                btn.setAttribute('aria-label', 'Ocultar contraseña');
            } else {
                inp.type = 'password';
                btn.innerHTML = svgShow;
                btn.setAttribute('aria-label', 'Mostrar contraseña');
            }
        });
    })();
    </script>
</body>
</html>
