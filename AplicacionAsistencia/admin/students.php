<?php
/**
 * Gestión de Alumnos - CRUD completo
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/GroupManager.php';
require_once __DIR__ . '/../classes/UserManager.php';
require_once __DIR__ . '/../classes/DailyNotes.php';
require_once __DIR__ . '/../classes/AttendanceManager.php';

Auth::requireAdmin();

$studentManager = new Student();
$groupManager = new GroupManager();
$userManager = new UserManager();
$dailyNotes = new DailyNotes();
$attendanceManager = new AttendanceManager();

$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

// ---- CREAR ALUMNO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_student'])) {
    $id = $studentManager->create($_POST);
    if ($id) {
        $message = '✅ Alumno creado correctamente.';
        $messageType = 'success';
        $action = 'list';
    } else {
        $message = '❌ Error al crear el alumno.';
        $messageType = 'error';
    }
}

// ---- ACTUALIZAR ALUMNO ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $id = intval($_POST['id']);
    if ($studentManager->update($id, $_POST)) {
        $message = '✅ Alumno actualizado correctamente.';
        $messageType = 'success';
        $action = 'list';
    } else {
        $message = '❌ Error al actualizar.';
        $messageType = 'error';
    }
}

// ---- ELIMINAR ALUMNO ----
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($studentManager->delete($id)) {
        $message = '✅ Alumno eliminado.';
        $messageType = 'success';
    } else {
        $message = '❌ Error al eliminar.';
        $messageType = 'error';
    }
    $action = 'list';
}

// Datos para formularios
$groups = $groupManager->getAll();
$parents = $userManager->getParents();
$filterGroup = $_GET['group_id'] ?? '';
$filterSearch = $_GET['search'] ?? '';

// Obtener alumnos
$filters = [];
if ($filterGroup) $filters['group_id'] = $filterGroup;
if ($filterSearch) $filters['search'] = $filterSearch;
$students = $studentManager->getAll($filters);

// Alumno para editar / ver
$editStudent = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editStudent = $studentManager->getById(intval($_GET['id']));
}
$viewStudent = null;
if ($action === 'view' && isset($_GET['id'])) {
    $viewStudent = $studentManager->getById(intval($_GET['id']));
    $studentNotes = $dailyNotes->getByStudent(intval($_GET['id']));
    $studentAttendance = $attendanceManager->getByStudent(intval($_GET['id']));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumnos - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

        <main class="main-content">
            <div class="page-title fade-in">
                <h1>👦 Gestión de Alumnos</h1>
                <p>Crear, editar y ver la información de los alumnos</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> fade-in"><?= $message ?></div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
                <!-- LISTADO DE ALUMNOS -->
                <div class="card fade-in mb-3">
                    <div class="card-header">
                        <h3>Filtrar</h3>
                        <a href="?action=new" class="btn btn-success btn-sm">➕ Nuevo Alumno</a>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="d-flex flex-wrap gap-2 align-center">
                            <input type="text" name="search" class="form-input" placeholder="Buscar por nombre..." 
                                   value="<?= htmlspecialchars($filterSearch) ?>" style="max-width: 250px;">
                            <select name="group_id" class="form-select" style="max-width: 200px;">
                                <option value="">Todos los grupos</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g['id'] ?>" <?= $filterGroup == $g['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">🔍 Buscar</button>
                            <a href="?action=list" class="btn btn-outline btn-sm">Limpiar</a>
                        </form>
                    </div>
                </div>

                <div class="card fade-in">
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="empty-state">
                                <div class="icon">👦</div>
                                <h3>No hay alumnos</h3>
                                <p>Añade tu primer alumno.</p>
                                <a href="?action=new" class="btn btn-primary mt-2">➕ Añadir Alumno</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Grupo</th>
                                            <th>Padre/Madre</th>
                                            <th>Teléfono</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $s): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($s['surname'] . ', ' . $s['name']) ?></strong></td>
                                                <td>
                                                    <?php if ($s['group_name']): ?>
                                                        <span class="badge badge-present"><?= htmlspecialchars($s['group_name']) ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-late">Sin grupo</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($s['parent_name'] ?? '-') ?></td>
                                                <td><?= htmlspecialchars($s['parent_phone'] ?? '-') ?></td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="?action=view&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">👁️</a>
                                                        <a href="?action=edit&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
                                                        <a href="?delete=<?= $s['id'] ?>" class="btn btn-danger btn-sm" 
                                                           onclick="return confirm('¿Estás seguro de eliminar este alumno?')">🗑️</a>
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

            <?php elseif ($action === 'new' || $action === 'edit'): ?>
                <!-- FORMULARIO CREAR / EDITAR -->
                <div class="card fade-in">
                    <div class="card-header">
                        <h3><?= $action === 'new' ? '➕ Nuevo Alumno' : '✏️ Editar Alumno' ?></h3>
                        <a href="?action=list" class="btn btn-outline btn-sm">← Volver</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <?php if ($editStudent): ?>
                                <input type="hidden" name="id" value="<?= $editStudent['id'] ?>">
                            <?php endif; ?>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" name="name" class="form-input" required
                                           value="<?= htmlspecialchars($editStudent['name'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Apellidos *</label>
                                    <input type="text" name="surname" class="form-input" required
                                           value="<?= htmlspecialchars($editStudent['surname'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Fecha de nacimiento</label>
                                    <input type="date" name="birthdate" class="form-input"
                                           value="<?= $editStudent['birthdate'] ?? '' ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Grupo</label>
                                    <select name="group_id" class="form-select">
                                        <option value="">Sin grupo</option>
                                        <?php foreach ($groups as $g): ?>
                                            <option value="<?= $g['id'] ?>" 
                                                <?= ($editStudent['group_id'] ?? '') == $g['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($g['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Padre/Madre</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">Sin asignar</option>
                                    <?php foreach ($parents as $p): ?>
                                        <option value="<?= $p['id'] ?>"
                                            <?= ($editStudent['parent_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['name']) ?> (<?= htmlspecialchars($p['phone'] ?? 'Sin tel.') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Observaciones</label>
                                <textarea name="notes" class="form-textarea"><?= htmlspecialchars($editStudent['notes'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" name="<?= $action === 'new' ? 'create_student' : 'update_student' ?>" 
                                    class="btn btn-primary">
                                💾 <?= $action === 'new' ? 'Crear Alumno' : 'Guardar Cambios' ?>
                            </button>
                        </form>
                    </div>
                </div>

            <?php elseif ($action === 'view' && $viewStudent): ?>
                <!-- VER DETALLE DE ALUMNO -->
                <div class="card fade-in mb-3">
                    <div class="card-header">
                        <h3>📋 <?= htmlspecialchars($viewStudent['surname'] . ', ' . $viewStudent['name']) ?></h3>
                        <div class="d-flex gap-1">
                            <a href="?action=edit&id=<?= $viewStudent['id'] ?>" class="btn btn-outline btn-sm">✏️ Editar</a>
                            <a href="?action=list" class="btn btn-outline btn-sm">← Volver</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="form-row mb-2">
                            <div>
                                <p class="form-label">Grupo</p>
                                <p><strong><?= htmlspecialchars($viewStudent['group_name'] ?? 'Sin grupo') ?></strong></p>
                            </div>
                            <div>
                                <p class="form-label">Fecha Nacimiento</p>
                                <p><strong><?= $viewStudent['birthdate'] ? date('d/m/Y', strtotime($viewStudent['birthdate'])) : '-' ?></strong></p>
                            </div>
                        </div>
                        <div class="form-row mb-2">
                            <div>
                                <p class="form-label">Padre/Madre</p>
                                <p><strong><?= htmlspecialchars($viewStudent['parent_name'] ?? '-') ?></strong></p>
                            </div>
                            <div>
                                <p class="form-label">Teléfono Padre/Madre</p>
                                <p><strong><?= htmlspecialchars($viewStudent['parent_phone'] ?? '-') ?></strong></p>
                            </div>
                        </div>
                        <?php if ($viewStudent['parent_email']): ?>
                        <div class="mb-2">
                            <p class="form-label">Email Padre/Madre</p>
                            <p><strong><?= htmlspecialchars($viewStudent['parent_email']) ?></strong></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($viewStudent['notes']): ?>
                        <div class="mb-2">
                            <p class="form-label">Observaciones</p>
                            <p><?= nl2br(htmlspecialchars($viewStudent['notes'])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Historial de asistencia -->
                <div class="card fade-in mb-3">
                    <div class="card-header">
                        <h3>📅 Historial de Asistencia</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($studentAttendance)): ?>
                            <div class="empty-state"><p>No hay registros de asistencia.</p></div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr><th>Fecha</th><th>Estado</th><th>Notas</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($studentAttendance as $a): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($a['date'])) ?></td>
                                                <td>
                                                    <?php
                                                    $badgeMap = [
                                                        'present' => ['Presente', 'badge-present'],
                                                        'absent' => ['Ausente', 'badge-absent'],
                                                        'late' => ['Tarde', 'badge-late'],
                                                        'justified' => ['Justificado', 'badge-justified'],
                                                    ];
                                                    $b = $badgeMap[$a['status']] ?? ['?', ''];
                                                    ?>
                                                    <span class="badge <?= $b[1] ?>"><?= $b[0] ?></span>
                                                </td>
                                                <td><?= htmlspecialchars($a['notes'] ?? '') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notas diarias -->
                <div class="card fade-in">
                    <div class="card-header">
                        <h3>📝 Notas Diarias</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($studentNotes)): ?>
                            <div class="empty-state"><p>No hay notas.</p></div>
                        <?php else: ?>
                            <?php foreach ($studentNotes as $note): ?>
                                <div style="padding: 0.75rem; border-bottom: 1px solid var(--border);">
                                    <div class="d-flex justify-between align-center">
                                        <strong><?= date('d/m/Y', strtotime($note['date'])) ?></strong>
                                        <span style="font-size: 0.8rem; color: var(--text-muted);">
                                            por <?= htmlspecialchars($note['author_name'] ?? 'Sistema') ?>
                                        </span>
                                    </div>
                                    <p class="mt-1"><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
