<?php
/**
 * Script para actualizar contraseñas de los usuarios de prueba
 * Ejecutar una sola vez después de crear la base de datos
 */

require_once __DIR__ . '/config/app.php';

$db = getDB();

// Actualizar contraseña del admin (admin123)
$adminHash = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE users SET password = :pwd WHERE username = 'admin'");
$stmt->execute([':pwd' => $adminHash]);

// Actualizar contraseña del padre de ejemplo (padre123)
$parentHash = password_hash('padre123', PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE users SET password = :pwd WHERE username = 'maria.garcia'");
$stmt->execute([':pwd' => $parentHash]);

echo "✅ Contraseñas actualizadas correctamente.\n";
echo "Admin: admin / admin123\n";
echo "Padre: maria.garcia / padre123\n";
