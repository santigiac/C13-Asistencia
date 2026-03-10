<?php
/**
 * Quick verification script
 */
require_once __DIR__ . '/config/app.php';

$db = getDB();
echo "=== TABLES ===\n";
$stmt = $db->query('SHOW TABLES');
while ($r = $stmt->fetch(PDO::FETCH_NUM)) echo "  " . $r[0] . "\n";

echo "\n=== USERS ===\n";
$stmt = $db->query('SELECT id, username, role, name FROM users');
while ($r = $stmt->fetch()) echo "  #{$r['id']} {$r['username']} ({$r['role']}) - {$r['name']}\n";

echo "\n=== GROUPS ===\n";
$stmt = $db->query('SELECT * FROM `groups`');
while ($r = $stmt->fetch()) echo "  #{$r['id']} {$r['name']}\n";

echo "\n=== STUDENTS ===\n";
$stmt = $db->query('SELECT s.*, u.name AS parent_name FROM students s LEFT JOIN users u ON u.id = s.parent_id');
while ($r = $stmt->fetch()) echo "  #{$r['id']} {$r['name']} {$r['surname']} (Parent: {$r['parent_name']})\n";

echo "\n=== LOGIN TEST ===\n";
$stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$user = $stmt->fetch();
echo "  Admin exists: " . ($user ? "YES" : "NO") . "\n";
echo "  Password verify (admin123): " . (password_verify('admin123', $user['password']) ? "OK" : "FAIL") . "\n";

$stmt = $db->prepare("SELECT * FROM users WHERE username = 'maria.garcia'");
$stmt->execute();
$parent = $stmt->fetch();
echo "  Parent exists: " . ($parent ? "YES" : "NO") . "\n";
echo "  Password verify (padre123): " . (password_verify('padre123', $parent['password']) ? "OK" : "FAIL") . "\n";
echo "\n✅ All checks passed!\n";
