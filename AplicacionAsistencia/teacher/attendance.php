<?php
/**
 * Teacher Attendance - Pasar lista (solo su grupo)
 * El profesor solo puede pasar lista de su propio grupo
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/AttendanceManager.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/GroupManager.php';
require_once __DIR__ . '/../classes/DailyNotes.php';

Auth::requireTeacher();

$attendance = new AttendanceManager();
$studentManager = new Student();
$groupManager = new GroupManager();
$dailyNotes = new DailyNotes();

// Obtener el grupo del profesor (fijo, no se puede cambiar)
$teacherId = Auth::getUserId();
$group = $groupManager->getByTeacherId($teacherId);

$selectedDate = $_GET['date'] ?? ($_POST['date'] ?? date('Y-m-d'));
$message = '';
$messageType = '';

// Procesar formulario de asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance']) && $group) {
    $date = $_POST['date'];
    $statuses = $_POST['status'] ?? [];
    $notes = $_POST['notes'] ?? [];

    $records = [];
    foreach ($statuses as $studentId => $status) {
        $records[] = [
            'student_id' => $studentId,
            'status'     => $status,
            'notes'      => $notes[$studentId] ?? '',
        ];
    }

    if ($attendance->markBulk($date, $records, Auth::getUserId())) {
        $message = '✅ Asistencia guardada correctamente.';
        $messageType = 'success';

        // Comprobar ausencias consecutivas y crear notificaciones
        foreach ($records as $record) {
            if ($record['status'] === 'absent') {
                if ($attendance->checkConsecutiveAbsences($record['student_id'])) {
                    $attendance->createAbsenceNotification($record['student_id']);
                }
            }
        }
    } else {
        $message = '❌ Error al guardar la asistencia.';
        $messageType = 'error';
    }

    // Guardar notas diarias
    $dailyNotesContent = $_POST['daily_note'] ?? [];
    foreach ($dailyNotesContent as $studentId => $content) {
        $content = trim($content);
        if (!empty($content)) {
            $dailyNotes->create($studentId, $date, $content, Auth::getUserId());
        }
    }

    $selectedDate = $date;
}

// Obtener alumnos del grupo del profesor
$existingAttendance = [];
if ($group) {
    $existingAttendance = $attendance->getByGroupAndDate($group['id'], $selectedDate);
    if (empty($existingAttendance)) {
        $rawStudents = $studentManager->getByGroup($group['id']);
        foreach ($rawStudents as $s) {
            $existingAttendance[] = [
                'student_id' => $s['id'],
                'name'       => $s['name'],
                'surname'    => $s['surname'],
                'status'     => null,
                'attendance_notes' => '',
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasar Lista - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_teacher.php'; ?>

        <main class="main-content">
            <div class="page-title fade-in">
                <h1>✅ Pasar Lista</h1>
                <p>
                    <?php if ($group): ?>
                        Grupo: <strong><?= htmlspecialchars($group['name']) ?></strong>
                    <?php else: ?>
                        No tienes un grupo asignado
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> fade-in"><?= $message ?></div>
            <?php endif; ?>

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
            <?php else: ?>
                <!-- Selector de fecha -->
                <div class="card fade-in mb-3">
                    <div class="card-body">
                        <form method="GET" action="" class="d-flex flex-wrap gap-2 align-center">
                            <div class="form-group" style="margin-bottom: 0; min-width: 160px;">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="date" class="form-input" 
                                       value="<?= htmlspecialchars($selectedDate) ?>" 
                                       onchange="this.form.submit()">
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de alumnos -->
                <?php if (!empty($existingAttendance)): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="date" value="<?= $selectedDate ?>">

                        <div class="card fade-in">
                            <div class="card-header">
                                <h3>📋 Lista de Alumnos</h3>
                                <span class="badge badge-present"><?= count($existingAttendance) ?> alumnos</span>
                            </div>
                            <div class="card-body">
                                <div class="attendance-grid">
                                    <?php foreach ($existingAttendance as $student): ?>
                                        <div class="attendance-row">
                                            <div class="student-name">
                                                <?= htmlspecialchars($student['surname'] . ', ' . $student['name']) ?>
                                            </div>
                                            <div class="attendance-options">
                                                <?php 
                                                $sid = $student['student_id'];
                                                $currentStatus = $student['status'] ?? '';
                                                $opts = [
                                                    'present' => ['label' => '✅ Presente', 'class' => 'opt-present'],
                                                    'absent'  => ['label' => '❌ Ausente', 'class' => 'opt-absent'],
                                                    'late'    => ['label' => '⏰ Tarde', 'class' => 'opt-late'],
                                                    'justified' => ['label' => '📝 Justificado', 'class' => 'opt-justified'],
                                                ];
                                                foreach ($opts as $val => $opt): ?>
                                                    <div class="attendance-option">
                                                        <input type="radio" 
                                                               name="status[<?= $sid ?>]" 
                                                               id="status_<?= $sid ?>_<?= $val ?>" 
                                                               value="<?= $val ?>"
                                                               <?= $currentStatus === $val ? 'checked' : '' ?>
                                                               <?= $val === 'present' && !$currentStatus ? 'checked' : '' ?>>
                                                        <label for="status_<?= $sid ?>_<?= $val ?>" class="<?= $opt['class'] ?>">
                                                            <?= $opt['label'] ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <input type="text" 
                                                   name="notes[<?= $sid ?>]" 
                                                   class="attendance-notes-input" 
                                                   placeholder="Notas..."
                                                   value="<?= htmlspecialchars($student['attendance_notes'] ?? '') ?>">
                                            <input type="text" 
                                                   name="daily_note[<?= $sid ?>]" 
                                                   class="attendance-notes-input" 
                                                   placeholder="Comentario del día...">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" name="save_attendance" class="btn btn-primary">
                                    💾 Guardar Asistencia
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="card fade-in">
                        <div class="card-body">
                            <div class="empty-state">
                                <div class="icon">👦</div>
                                <h3>No hay alumnos en tu grupo</h3>
                                <p>El administrador debe asignar alumnos a tu grupo.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
