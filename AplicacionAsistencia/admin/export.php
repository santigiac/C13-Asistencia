<?php
/**
 * Exportar datos a CSV
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/GroupManager.php';
require_once __DIR__ . '/../classes/Exporter.php';

Auth::requireAdmin();

$groupManager = new GroupManager();
$groups = $groupManager->getAll();

// Si se solicita exportación directa
if (isset($_GET['download'])) {
    $groupId = intval($_GET['group_id'] ?? 0);
    $month = $_GET['month'] ?? date('Y-m');

    if ($groupId) {
        Exporter::exportAttendanceReport($groupId, $month);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
        <main class="main-content">
            <div class="page-title fade-in">
                <h1>📥 Exportar Datos</h1>
                <p>Descarga informes de asistencia en formato CSV (compatible con Excel)</p>
            </div>

            <div class="card fade-in">
                <div class="card-header">
                    <h3>⬇️ Generar Informe</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <input type="hidden" name="download" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Grupo *</label>
                                <select name="group_id" class="form-select" required>
                                    <option value="">-- Selecciona grupo --</option>
                                    <?php foreach ($groups as $g): ?>
                                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mes *</label>
                                <input type="month" name="month" class="form-input" value="<?= date('Y-m') ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">📥 Descargar CSV</button>
                    </form>
                </div>
            </div>

            <div class="card fade-in mt-3">
                <div class="card-body">
                    <div class="alert alert-info">
                        💡 <strong>Consejo:</strong> Abre el archivo CSV en Excel o Google Sheets. 
                        Los datos incluyen: nombre del alumno, padre/madre, teléfono, y el estado de cada día del mes 
                        (P=Presente, A=Ausente, R=Retraso, J=Justificado).
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
