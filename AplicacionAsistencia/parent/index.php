<?php
/**
 * Portal de Padres - Ver asistencia de su hijo/a
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/AttendanceManager.php';
require_once __DIR__ . '/../classes/DailyNotes.php';

Auth::requireParent();

$parentId = Auth::getUserId();
$studentManager = new Student();
$attendanceManager = new AttendanceManager();
$dailyNotes = new DailyNotes();

// Obtener los hijos de este padre
$children = $studentManager->getByParent($parentId);
$notifications = $attendanceManager->getNotifications($parentId);

// Marcar notificación como leída
if (isset($_GET['read_notif'])) {
    $attendanceManager->markNotificationRead(intval($_GET['read_notif']), $parentId);
    header('Location: ' . BASE_URL . '/parent/index.php');
    exit;
}

// Mes seleccionado para filtrar
$selectedMonth = $_GET['month'] ?? date('Y-m');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Hijo/a - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <style>
        /* Parent-specific minimal layout */
        .parent-layout {
            max-width: 800px;
            margin: 0 auto;
            padding: 1rem;
            min-height: 100vh;
        }
        .parent-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            margin-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .parent-header h2 {
            font-size: 1.1rem;
            background: linear-gradient(135deg, var(--primary-light), #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .calendar-day-header {
            text-align: center;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-muted);
            padding: 0.5rem 0;
            text-transform: uppercase;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid var(--border);
            gap: 2px;
        }
        .calendar-day.empty { border: none; }
        .calendar-day.present  { background: var(--success-bg); color: var(--success); border-color: rgba(34,197,94,0.3); }
        .calendar-day.absent   { background: var(--danger-bg); color: var(--danger); border-color: rgba(239,68,68,0.3); }
        .calendar-day.late     { background: var(--warning-bg); color: var(--warning); border-color: rgba(245,158,11,0.3); }
        .calendar-day.justified { background: var(--info-bg); color: var(--info); border-color: rgba(59,130,246,0.3); }
        .calendar-day .day-num { font-size: 0.9rem; font-weight: 700; }
        .calendar-day .day-status { font-size: 0.6rem; }
    </style>
</head>
<body>
    <div class="parent-layout">
        <header class="parent-header">
            <h2>📋 Cultura Tretze</h2>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm">Cerrar Sesión</a>
        </header>

        <div class="page-title fade-in">
            <h1>¡Hola, <?= htmlspecialchars(Auth::getUserName()) ?>! 👋</h1>
            <p>Consulta la asistencia de tu hijo/a</p>
        </div>

        <!-- Notificaciones -->
        <?php if (!empty($notifications)): ?>
            <?php 
            $unread = array_filter($notifications, fn($n) => !$n['is_read']); 
            if (!empty($unread)): 
            ?>
                <div class="card fade-in mb-3">
                    <div class="card-header">
                        <h3>🔔 Notificaciones</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php foreach ($unread as $notif): ?>
                            <div class="notification-item unread">
                                <div class="notif-icon">⚠️</div>
                                <div class="notif-content">
                                    <p><?= htmlspecialchars($notif['message']) ?></p>
                                    <div class="notif-date"><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></div>
                                </div>
                                <a href="?read_notif=<?= $notif['id'] ?>" class="btn btn-outline btn-sm">✓</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Info de cada hijo -->
        <?php if (empty($children)): ?>
            <div class="card fade-in">
                <div class="card-body">
                    <div class="empty-state">
                        <div class="icon">👦</div>
                        <h3>No se encontró información</h3>
                        <p>Contacte con el administrador del centro.</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($children as $child): ?>
                <?php
                    // Obtener asistencia del mes seleccionado
                    $attendance = $attendanceManager->getByStudent($child['id'], $selectedMonth);
                    $attendanceMap = [];
                    foreach ($attendance as $a) {
                        $day = intval(date('j', strtotime($a['date'])));
                        $attendanceMap[$day] = $a['status'];
                    }

                    // Calcular estadísticas
                    $totalPresent = count(array_filter($attendance, fn($a) => $a['status'] === 'present'));
                    $totalAbsent = count(array_filter($attendance, fn($a) => $a['status'] === 'absent'));
                    $totalLate = count(array_filter($attendance, fn($a) => $a['status'] === 'late'));

                    // Datos del calendario
                    $year = intval(substr($selectedMonth, 0, 4));
                    $month = intval(substr($selectedMonth, 5, 2));
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                    $firstDayOfWeek = date('N', mktime(0, 0, 0, $month, 1, $year)); // 1=Lunes

                    // Notas
                    $notes = $dailyNotes->getByStudent($child['id']);
                ?>
                <div class="card fade-in mb-3">
                    <div class="card-header">
                        <h3>👦 <?= htmlspecialchars($child['name'] . ' ' . $child['surname']) ?></h3>
                        <span class="badge badge-present"><?= htmlspecialchars($child['group_name'] ?? 'Sin grupo') ?></span>
                    </div>
                    <div class="card-body">
                        <!-- Estadísticas rápidas -->
                        <div class="stats-grid mb-3">
                            <div class="stat-card">
                                <div class="stat-icon success">✅</div>
                                <div class="stat-info">
                                    <h4><?= $totalPresent ?></h4>
                                    <p>Presente</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon danger">❌</div>
                                <div class="stat-info">
                                    <h4><?= $totalAbsent ?></h4>
                                    <p>Ausente</p>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon warning">⏰</div>
                                <div class="stat-info">
                                    <h4><?= $totalLate ?></h4>
                                    <p>Tarde</p>
                                </div>
                            </div>
                        </div>

                        <!-- Selector de mes -->
                        <form method="GET" class="d-flex gap-2 align-center mb-3">
                            <input type="month" name="month" class="form-input" 
                                   value="<?= $selectedMonth ?>" 
                                   onchange="this.form.submit()"
                                   style="max-width: 200px;">
                        </form>

                        <!-- Calendario visual -->
                        <div class="calendar-grid">
                            <div class="calendar-day-header">Lun</div>
                            <div class="calendar-day-header">Mar</div>
                            <div class="calendar-day-header">Mié</div>
                            <div class="calendar-day-header">Jue</div>
                            <div class="calendar-day-header">Vie</div>
                            <div class="calendar-day-header">Sáb</div>
                            <div class="calendar-day-header">Dom</div>

                            <?php for ($i = 1; $i < $firstDayOfWeek; $i++): ?>
                                <div class="calendar-day empty"></div>
                            <?php endfor; ?>

                            <?php for ($d = 1; $d <= $daysInMonth; $d++): 
                                $status = $attendanceMap[$d] ?? '';
                                $statusLabels = [
                                    'present' => 'P', 'absent' => 'A', 
                                    'late' => 'R', 'justified' => 'J'
                                ];
                            ?>
                                <div class="calendar-day <?= $status ?>">
                                    <span class="day-num"><?= $d ?></span>
                                    <?php if ($status): ?>
                                        <span class="day-status"><?= $statusLabels[$status] ?? '' ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Notas diarias del hijo -->
                <?php if (!empty($notes)): ?>
                    <div class="card fade-in mb-3">
                        <div class="card-header">
                            <h3>📝 Notas del Centro</h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php foreach (array_slice($notes, 0, 10) as $note): ?>
                                <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border);">
                                    <div class="d-flex justify-between align-center">
                                        <strong><?= date('d/m/Y', strtotime($note['date'])) ?></strong>
                                        <span style="font-size: 0.75rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($note['author_name'] ?? '') ?>
                                        </span>
                                    </div>
                                    <p class="mt-1" style="font-size: 0.9rem;"><?= nl2br(htmlspecialchars($note['content'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="text-center mt-3" style="color: var(--text-muted); font-size: 0.8rem; padding-bottom: 2rem;">
            © <?= date('Y') ?> Cultura Tretze · Sistema de Asistencia
        </div>
    </div>
</body>
</html>
