<?php
/**
 * Teacher Students - Ver alumnos de su grupo (solo lectura)
 * El profesor NO puede crear, editar ni eliminar alumnos
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/GroupManager.php';
require_once __DIR__ . '/../classes/DailyNotes.php';
require_once __DIR__ . '/../classes/AttendanceManager.php';

Auth::requireTeacher();

$studentManager = new Student();
$groupManager = new GroupManager();
$dailyNotes = new DailyNotes();
$attendanceManager = new AttendanceManager();

// Obtener el grupo del profesor
$teacherId = Auth::getUserId();
$group = $groupManager->getByTeacherId($teacherId);

$action = $_GET['action'] ?? 'list';

// Obtener alumnos del grupo
$students = [];
if ($group) {
    $students = $studentManager->getByGroup($group['id']);
}

// Ver detalle de un alumno (solo si pertenece a su grupo)
$viewStudent = null;
$studentNotes = [];
$studentAttendance = [];
if ($action === 'view' && isset($_GET['id']) && $group) {
    $viewStudent = $studentManager->getById(intval($_GET['id']));
    // Verificar que el alumno pertenece al grupo del profesor
    if ($viewStudent && $viewStudent['group_id'] != $group['id']) {
        $viewStudent = null; // No pertenece a su grupo, denegar acceso
    }
    if ($viewStudent) {
        $studentNotes = $dailyNotes->getByStudent(intval($_GET['id']));
        $studentAttendance = $attendanceManager->getByStudent(intval($_GET['id']));
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Alumnos - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_teacher.php'; ?>

        <main class="main-content">
            <div class="page-title fade-in">
                <h1>👦 Mis Alumnos</h1>
                <p>
                    <?php if ($group): ?>
                        Grupo: <strong><?= htmlspecialchars($group['name']) ?></strong>
                    <?php else: ?>
                        No tienes un grupo asignado
                    <?php endif; ?>
                </p>
            </div>

            <?php if (!$group): ?>
                <div class="card fade-in">
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="icon">⚠️</div>
                            <h3>Sin grupo asignado</h3>
                            <p>Contacta con el administrador.</p>
                        </div>
                    </div>
                </div>

            <?php elseif ($action === 'list'): ?>
                <!-- LISTADO DE ALUMNOS (solo lectura) -->
                <div class="card fade-in">
                    <div class="card-header">
                        <h3>📋 Lista de Alumnos</h3>
                        <span class="badge badge-present"><?= count($students) ?> alumnos</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students)): ?>
                            <div class="empty-state">
                                <div class="icon">👦</div>
                                <h3>No hay alumnos en tu grupo</h3>
                                <p>El administrador debe asignar alumnos a tu grupo.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Fecha Nacimiento</th>
                                            <th>Padre/Madre</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $s): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($s['surname'] . ', ' . $s['name']) ?></strong></td>
                                                <td><?= $s['birthdate'] ? date('d/m/Y', strtotime($s['birthdate'])) : '-' ?></td>
                                                <td><?= htmlspecialchars($s['parent_name'] ?? '-') ?></td>
                                                <td>
                                                    <a href="?action=view&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">👁️ Ver</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($action === 'view' && $viewStudent): ?>
                <!-- VER DETALLE DE ALUMNO (solo lectura) -->
                <div class="card fade-in mb-3">
                    <div class="card-header">
                        <h3>📋 <?= htmlspecialchars($viewStudent['surname'] . ', ' . $viewStudent['name']) ?></h3>
                        <a href="?action=list" class="btn btn-outline btn-sm">← Volver</a>
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

            <?php else: ?>
                <!-- Alumno no encontrado o no pertenece al grupo -->
                <div class="card fade-in">
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="icon">🚫</div>
                            <h3>Alumno no encontrado</h3>
                            <p>El alumno no existe o no pertenece a tu grupo.</p>
                            <a href="?action=list" class="btn btn-primary mt-2">← Volver a la lista</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
