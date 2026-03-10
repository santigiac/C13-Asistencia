<?php
/**
 * Importar datos desde CSV (exportado de Excel)
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/GroupManager.php';
require_once __DIR__ . '/../classes/UserManager.php';

Auth::requireAdmin();

$studentManager = new Student();
$groupManager = new GroupManager();
$userManager = new UserManager();

$message = '';
$messageType = '';
$preview = [];
$groups = $groupManager->getAll();

// Procesar CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = '❌ Error al subir el archivo.';
        $messageType = 'error';
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle) {
            // Detectar separador
            $firstLine = fgets($handle);
            rewind($handle);
            $separator = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            $headers = fgetcsv($handle, 0, $separator);
            
            // Limpiar BOM del primer header si existe
            if ($headers && isset($headers[0])) {
                $headers[0] = preg_replace('/[\x{FEFF}]/u', '', $headers[0]);
            }

            $imported = 0;
            $errors = 0;
            $groupId = intval($_POST['group_id'] ?? 0) ?: null;

            while (($row = fgetcsv($handle, 0, $separator)) !== false) {
                if (count($row) < 2) continue;

                // Mapeo flexible de columnas
                $data = [
                    'name'      => trim($row[0] ?? ''),
                    'surname'   => trim($row[1] ?? ''),
                    'birthdate' => trim($row[2] ?? '') ?: null,
                    'parent_id' => null,
                    'group_id'  => $groupId,
                    'notes'     => trim($row[3] ?? ''),
                ];

                // Si hay columna de padre (nombre), intentar buscar o crear
                if (isset($row[4]) && !empty(trim($row[4]))) {
                    $parentName = trim($row[4]);
                    $parentPhone = trim($row[5] ?? '');
                    $parentEmail = trim($row[6] ?? '');
                    
                    // Crear usuario padre automáticamente
                    $parentUsername = strtolower(str_replace(' ', '.', $parentName));
                    if (!$userManager->usernameExists($parentUsername)) {
                        $userManager->create([
                            'username' => $parentUsername,
                            'password' => 'padre123', // Contraseña por defecto
                            'role'     => 'parent',
                            'name'     => $parentName,
                            'email'    => $parentEmail,
                            'phone'    => $parentPhone,
                        ]);
                    }
                    // Obtener el ID del padre
                    $db = getDB();
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = :u");
                    $stmt->execute([':u' => $parentUsername]);
                    $parent = $stmt->fetch();
                    if ($parent) {
                        $data['parent_id'] = $parent['id'];
                    }
                }

                if (!empty($data['name']) && !empty($data['surname'])) {
                    try {
                        $studentManager->create($data);
                        $imported++;
                    } catch (Exception $e) {
                        $errors++;
                    }
                }
            }
            fclose($handle);

            $message = "✅ Importación completada: $imported alumnos importados.";
            if ($errors > 0) {
                $message .= " ($errors errores)";
            }
            $messageType = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
        <main class="main-content">
            <div class="page-title fade-in">
                <h1>📤 Importar desde Excel/CSV</h1>
                <p>Importa alumnos desde un archivo CSV exportado de Excel</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> fade-in"><?= $message ?></div>
            <?php endif; ?>

            <div class="card fade-in mb-3">
                <div class="card-header"><h3>📄 Formato del CSV</h3></div>
                <div class="card-body">
                    <div class="alert alert-info">
                        💡 El archivo CSV debe tener las siguientes columnas (separado por <code>;</code> o <code>,</code>):<br><br>
                        <strong>Nombre ; Apellidos ; Fecha_nacimiento ; Observaciones ; Nombre_padre ; Teléfono_padre ; Email_padre</strong><br><br>
                        La primera fila debe ser la cabecera. Las columnas 3-7 son opcionales. 
                        Si incluyes padre/madre, se creará automáticamente una cuenta con contraseña <code>padre123</code>.
                    </div>
                </div>
            </div>

            <div class="card fade-in">
                <div class="card-header"><h3>⬆️ Subir Archivo</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Grupo de destino (opcional)</label>
                            <select name="group_id" class="form-select">
                                <option value="">Sin grupo</option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Archivo CSV *</label>
                            <input type="file" name="csv_file" class="form-input" accept=".csv,.txt" required>
                        </div>
                        <button type="submit" class="btn btn-primary">📤 Importar</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
