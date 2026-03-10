<?php
/**
 * Admin Dashboard - Panel principal del administrador
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AttendanceManager.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/GroupManager.php';

Auth::requireAdmin();

$attendance = new AttendanceManager();
$studentManager = new Student();
$groupManager = new GroupManager();

$stats = $attendance->getGeneralStats();
$groups = $groupManager->getAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

        <main class="main-content">
            <div class="page-title fade-in">
                <h1>¡Hola, <?= htmlspecialchars(Auth::getUserName()) ?>! 👋</h1>
                <p>Resumen del día: <?= date('d/m/Y') ?></p>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="stats-grid fade-in">
                <div class="stat-card">
                    <div class="stat-icon primary">👦</div>
                    <div class="stat-info">
                        <h4><?= $stats['total_students'] ?></h4>
                        <p>Total Alumnos</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success">✅</div>
                    <div class="stat-info">
                        <h4><?= $stats['present_today'] ?></h4>
                        <p>Presentes Hoy</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon danger">❌</div>
                    <div class="stat-info">
                        <h4><?= $stats['absent_today'] ?></h4>
                        <p>Ausentes Hoy</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning">🔔</div>
                    <div class="stat-info">
                        <h4><?= $stats['unread_notifications'] ?></h4>
                        <p>Alertas Pendientes</p>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="card fade-in mb-3">
                <div class="card-header">
                    <h3>⚡ Acciones Rápidas</h3>
                </div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/admin/attendance.php" class="btn btn-primary">✅ Pasar Lista</a>
                    <a href="<?= BASE_URL ?>/admin/students.php?action=new" class="btn btn-success">➕ Añadir Alumno</a>
                    <a href="<?= BASE_URL ?>/admin/export.php" class="btn btn-outline">📥 Exportar Datos</a>
                    <a href="<?= BASE_URL ?>/admin/import.php" class="btn btn-outline">📤 Importar Excel</a>
                </div>
            </div>

            <!-- Resumen por grupos -->
            <div class="card fade-in">
                <div class="card-header">
                    <h3>👥 Grupos</h3>
                    <a href="<?= BASE_URL ?>/admin/groups.php" class="btn btn-outline btn-sm">Ver Todos</a>
                </div>
                <div class="card-body">
                    <?php if (empty($groups)): ?>
                        <div class="empty-state">
                            <div class="icon">📂</div>
                            <h3>No hay grupos</h3>
                            <p>Crea tu primer grupo para empezar.</p>
                            <a href="<?= BASE_URL ?>/admin/groups.php" class="btn btn-primary mt-2">Crear Grupo</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Grupo</th>
                                        <th>Alumnos</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groups as $group): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($group['name']) ?></strong></td>
                                            <td><?= $group['student_count'] ?> alumnos</td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/admin/attendance.php?group_id=<?= $group['id'] ?>" class="btn btn-primary btn-sm">
                                                    Pasar Lista
                                                </a>
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
