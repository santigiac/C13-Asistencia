<?php
/**
 * Gestión de Padres/Madres (Cuentas de tipo parent)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/UserManager.php';

Auth::requireAdmin();

$userManager = new UserManager();
$message = '';
$messageType = '';

// Crear padre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_parent'])) {
    $username = trim($_POST['username']);
    if ($userManager->usernameExists($username)) {
        $message = '❌ Ese nombre de usuario ya existe.';
        $messageType = 'error';
    } else {
        $data = [
            'username' => $username,
            'password' => $_POST['password'],
            'role'     => 'parent',
            'name'     => trim($_POST['name']),
            'email'    => trim($_POST['email'] ?? ''),
            'phone'    => trim($_POST['phone'] ?? ''),
        ];
        if ($userManager->create($data)) {
            $message = '✅ Cuenta de padre/madre creada. Usuario: ' . htmlspecialchars($username);
            $messageType = 'success';
        } else {
            $message = '❌ Error al crear la cuenta.';
            $messageType = 'error';
        }
    }
}

// Actualizar padre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_parent'])) {
    $id = intval($_POST['id']);
    $data = [
        'name'  => trim($_POST['name']),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
    ];
    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }
    if ($userManager->update($id, $data)) {
        $message = '✅ Actualizado correctamente.';
        $messageType = 'success';
    }
}

// Eliminar padre
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($userManager->delete($id)) {
        $message = '✅ Cuenta eliminada.';
        $messageType = 'success';
    }
}

$parents = $userManager->getParents();
$editParent = null;
if (isset($_GET['edit'])) {
    $editParent = $userManager->getById(intval($_GET['edit']));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Padres - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
        <main class="main-content">
            <div class="page-title fade-in">
                <h1>👨‍👩‍👧 Gestión de Padres/Madres</h1>
                <p>Crear y gestionar cuentas para que los padres vean la asistencia de sus hijos</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> fade-in"><?= $message ?></div>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card fade-in mb-3">
                <div class="card-header">
                    <h3><?= $editParent ? '✏️ Editar' : '➕ Nueva cuenta de Padre/Madre' ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editParent): ?>
                            <input type="hidden" name="id" value="<?= $editParent['id'] ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nombre completo *</label>
                                <input type="text" name="name" class="form-input" required
                                       value="<?= htmlspecialchars($editParent['name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="phone" class="form-input"
                                       value="<?= htmlspecialchars($editParent['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-input"
                                       value="<?= htmlspecialchars($editParent['email'] ?? '') ?>">
                            </div>
                            <?php if (!$editParent): ?>
                                <div class="form-group">
                                    <label class="form-label">Usuario (login) *</label>
                                    <input type="text" name="username" class="form-input" required>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Contraseña <?= $editParent ? '(dejar vacío para no cambiar)' : '*' ?></label>
                            <input type="password" name="password" class="form-input" 
                                   <?= $editParent ? '' : 'required' ?>>
                        </div>
                        <button type="submit" name="<?= $editParent ? 'update_parent' : 'create_parent' ?>" class="btn btn-primary">
                            💾 <?= $editParent ? 'Guardar' : 'Crear Cuenta' ?>
                        </button>
                        <?php if ($editParent): ?>
                            <a href="parents.php" class="btn btn-outline">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Lista -->
            <div class="card fade-in">
                <div class="card-header"><h3>📋 Cuentas de Padres</h3></div>
                <div class="card-body">
                    <?php if (empty($parents)): ?>
                        <div class="empty-state">
                            <div class="icon">👨‍👩‍👧</div>
                            <h3>No hay cuentas de padres</h3>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr><th>Nombre</th><th>Usuario</th><th>Email</th><th>Teléfono</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parents as $p): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($p['username']) ?></td>
                                            <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($p['phone'] ?? '-') ?></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="?edit=<?= $p['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                                                    <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm"
                                                       onclick="return confirm('¿Eliminar esta cuenta?')">🗑️</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
