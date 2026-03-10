<?php
/**
 * Login Page - Aplicación de Asistencia
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/classes/Auth.php';

// Si ya está logueado, redirigir
if (Auth::isLoggedIn()) {
    $role = Auth::getRole();
    if ($role === 'admin') {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($role === 'teacher') {
        header('Location: ' . BASE_URL . '/teacher/index.php');
    } else {
        header('Location: ' . BASE_URL . '/parent/index.php');
    }
    exit;
}

$error = '';

// Procesar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, rellena todos los campos.';
    } else {
        $user = Auth::login($username, $password);
        if ($user) {
            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/dashboard.php');
            } elseif ($user['role'] === 'teacher') {
                header('Location: ' . BASE_URL . '/teacher/index.php');
            } else {
                header('Location: ' . BASE_URL . '/parent/index.php');
            }
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}

$errorParam = $_GET['error'] ?? '';
if ($errorParam === 'access') {
    $error = 'No tienes permisos para acceder a esa sección.';
} elseif ($errorParam === 'session') {
    $error = 'Tu sesión ha expirado. Inicia sesión de nuevo.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="logo">
                <h1>📋 <?= APP_NAME ?></h1>
                <p>Inicia sesión para continuar</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">Usuario</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-input" 
                        placeholder="Tu nombre de usuario"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Contraseña</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="Tu contraseña"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 0.5rem;">
                    Iniciar Sesión
                </button>
            </form>

            <div class="text-center mt-3" style="color: var(--text-muted); font-size: 0.8rem;">
                © <?= date('Y') ?> Cultura Tretze
            </div>
        </div>
    </div>
</body>
</html>
