<?php
/**
 * Teacher Dashboard - Panel principal del profesor
 * Muestra resumen de su grupo y acciones rápidas
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AttendanceManager.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/GroupManager.php';

Auth::requireTeacher();

$groupManager = new GroupManager();
$studentManager = new Student();
$attendance = new AttendanceManager();

// Obtener el grupo del profesor
$teacherId = Auth::getUserId();
$group = $groupManager->getByTeacherId($teacherId);

// Obtener alumnos del grupo
$students = [];
if ($group) {
    $students = $studentManager->getByGroup($group['id']);
}

// Estadísticas del día
$todayStats = ['present' => 0, 'absent' => 0, 'late' => 0, 'justified' => 0, 'unmarked' => 0];
if ($group) {
    $todayAttendance = $attendance->getByGroupAndDate($group['id'], date('Y-m-d'));
    if (!empty($todayAttendance)) {
        foreach ($todayAttendance as $a) {
            if ($a['status']) {
                $todayStats[$a['status']]++;
            } else {
                $todayStats['unmarked']++;
            }
        }
    } else {
        $todayStats['unmarked'] = count($students);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_teacher.php'; ?>

        <main class="main-content">
            <div class="page-title fade-in">
                <h1>¡Hola, <?= htmlspecialchars(Auth::getUserName()) ?>! 👋</h1>
                <p>Panel del profesor — <?= date('d/m/Y') ?></p>
            </div>

            <?php if (!$group): ?>
                <div class="card fade-in">
                    <div class="card-body">
                        <div class="empty-state">
                            <div class="icon">⚠️</div>
                            <h3>No tienes un grupo asignado</h3>
                            <p>Contacta con el administrador para que te asigne un grupo.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Info del grupo -->
                <div class="card fade-in mb-3">
                    <div class="card-header">
                        <h3>📚 Mi Grupo: <?= htmlspecialchars($group['name']) ?></h3>
                        <span class="badge badge-present"><?= $group['student_count'] ?> alumnos</span>
                    </div>
                    <div class="card-body">
                        <p style="color: var(--text-muted);"><?= htmlspecialchars($group['description'] ?? 'Sin descripción') ?></p>
                    </div>
                </div>

                <!-- Estadísticas rápidas del día -->
                <div class="stats-grid fade-in">
                    <div class="stat-card">
                        <div class="stat-icon primary">👦</div>
                        <div class="stat-info">
                            <h4><?= $group['student_count'] ?></h4>
                            <p>Total Alumnos</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon success">✅</div>
                        <div class="stat-info">
                            <h4><?= $todayStats['present'] ?></h4>
                            <p>Presentes Hoy</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon danger">❌</div>
                        <div class="stat-info">
                            <h4><?= $todayStats['absent'] ?></h4>
                            <p>Ausentes Hoy</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon warning">⏳</div>
                        <div class="stat-info">
                            <h4><?= $todayStats['unmarked'] ?></h4>
                            <p>Sin Marcar</p>
                        </div>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="card fade-in mb-3">
                    <div class="card-header">
                        <h3>⚡ Acciones Rápidas</h3>
                    </div>
                    <div class="card-body d-flex flex-wrap gap-2">
                        <a href="<?= BASE_URL ?>/teacher/attendance.php" class="btn btn-primary">✅ Pasar Lista</a>
                        <a href="<?= BASE_URL ?>/teacher/students.php" class="btn btn-outline">👦 Ver Alumnos</a>
                    </div>
                </div>

                <!-- Lista rápida de alumnos -->
                <div class="card fade-in">
                    <div class="card-header">
                        <h3>👥 Alumnos de mi grupo</h3>
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
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $s): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($s['surname'] . ', ' . $s['name']) ?></strong></td>
                                                <td><?= $s['birthdate'] ? date('d/m/Y', strtotime($s['birthdate'])) : '-' ?></td>
                                                <td>
                                                    <a href="<?= BASE_URL ?>/teacher/students.php?action=view&id=<?= $s['id'] ?>" class="btn btn-outline btn-sm">👁️ Ver</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
