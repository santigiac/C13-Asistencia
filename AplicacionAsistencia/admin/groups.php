<?php
/**
 * Gestión de Grupos
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/GroupManager.php';

Auth::requireAdmin();

$groupManager = new GroupManager();
$message = '';
$messageType = '';

// Crear grupo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name) {
        $groupManager->create($name, $desc);
        $message = '✅ Grupo creado correctamente.';
        $messageType = 'success';
    }
}

// Actualizar grupo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_group'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $desc = trim($_POST['description'] ?? '');
    if ($name && $groupManager->update($id, $name, $desc)) {
        $message = '✅ Grupo actualizado.';
        $messageType = 'success';
    }
}

// Eliminar grupo
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($groupManager->delete($id)) {
        $message = '✅ Grupo eliminado.';
        $messageType = 'success';
    }
}

$groups = $groupManager->getAll();
$editGroup = null;
if (isset($_GET['edit'])) {
    $editGroup = $groupManager->getById(intval($_GET['edit']));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grupos - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
        <main class="main-content">
            <div class="page-title fade-in">
                <h1>👥 Gestión de Grupos</h1>
                <p>Organiza a los alumnos en grupos</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> fade-in"><?= $message ?></div>
            <?php endif; ?>

            <!-- Formulario crear/editar -->
            <div class="card fade-in mb-3">
                <div class="card-header">
                    <h3><?= $editGroup ? '✏️ Editar Grupo' : '➕ Nuevo Grupo' ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($editGroup): ?>
                            <input type="hidden" name="id" value="<?= $editGroup['id'] ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Nombre del grupo *</label>
                                <input type="text" name="name" class="form-input" required
                                       value="<?= htmlspecialchars($editGroup['name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Descripción</label>
                                <input type="text" name="description" class="form-input"
                                       value="<?= htmlspecialchars($editGroup['description'] ?? '') ?>">
                            </div>
                        </div>
                        <button type="submit" name="<?= $editGroup ? 'update_group' : 'create_group' ?>" class="btn btn-primary">
                            💾 <?= $editGroup ? 'Guardar' : 'Crear Grupo' ?>
                        </button>
                        <?php if ($editGroup): ?>
                            <a href="?action=list" class="btn btn-outline">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Lista de grupos -->
            <div class="card fade-in">
                <div class="card-header"><h3>📋 Grupos Existentes</h3></div>
                <div class="card-body">
                    <?php if (empty($groups)): ?>
                        <div class="empty-state">
                            <div class="icon">📂</div>
                            <h3>No hay grupos</h3>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr><th>Nombre</th><th>Descripción</th><th>Alumnos</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groups as $g): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($g['name']) ?></strong></td>
                                            <td><?= htmlspecialchars($g['description'] ?? '-') ?></td>
                                            <td><span class="badge badge-present"><?= $g['student_count'] ?></span></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="?edit=<?= $g['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                                                    <a href="?delete=<?= $g['id'] ?>" class="btn btn-danger btn-sm"
                                                       onclick="return confirm('¿Eliminar este grupo?')">🗑️</a>
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
