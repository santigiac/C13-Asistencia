<?php
/**
 * Clase AttendanceManager - Gestión de asistencia
 * Incluye lógica de notificación por 3 ausencias consecutivas
 */

class AttendanceManager {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Registrar asistencia para un alumno en una fecha
     * Usa INSERT ... ON DUPLICATE KEY UPDATE para evitar duplicados
     */
    public function mark($studentId, $date, $status, $notes = '', $recordedBy = null) {
        $sql = "INSERT INTO attendance (student_id, date, status, notes, recorded_by)
                VALUES (:student_id, :date, :status, :notes, :recorded_by)
                ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), 
                    notes = VALUES(notes), 
                    recorded_by = VALUES(recorded_by)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':student_id'  => $studentId,
            ':date'        => $date,
            ':status'      => $status,
            ':notes'       => $notes,
            ':recorded_by' => $recordedBy,
        ]);
    }

    /**
     * Registrar asistencia masiva para un grupo
     * $records = [['student_id' => X, 'status' => 'present', 'notes' => ''], ...]
     */
    public function markBulk($date, $records, $recordedBy = null) {
        $this->db->beginTransaction();
        try {
            foreach ($records as $record) {
                $this->mark(
                    $record['student_id'],
                    $date,
                    $record['status'],
                    $record['notes'] ?? '',
                    $recordedBy
                );
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Obtener asistencia de un grupo en una fecha
     */
    public function getByGroupAndDate($groupId, $date) {
        $sql = "SELECT s.id AS student_id, s.name, s.surname, 
                       a.status, a.notes AS attendance_notes, a.id AS attendance_id
                FROM students s
                LEFT JOIN attendance a ON a.student_id = s.id AND a.date = :date
                WHERE s.group_id = :group_id
                ORDER BY s.surname, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':group_id' => $groupId, ':date' => $date]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener historial de asistencia de un alumno
     */
    public function getByStudent($studentId, $month = null) {
        $params = [':student_id' => $studentId];
        $dateFilter = '';
        
        if ($month) {
            $dateFilter = "AND DATE_FORMAT(a.date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }

        $sql = "SELECT a.*, s.name, s.surname 
                FROM attendance a
                JOIN students s ON s.id = a.student_id
                WHERE a.student_id = :student_id $dateFilter
                ORDER BY a.date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtener resumen mensual de un grupo
     */
    public function getMonthlyReport($groupId, $yearMonth) {
        $sql = "SELECT s.id AS student_id, s.name, s.surname,
                       a.date, a.status
                FROM students s
                LEFT JOIN attendance a ON a.student_id = s.id 
                    AND DATE_FORMAT(a.date, '%Y-%m') = :month
                WHERE s.group_id = :group_id
                ORDER BY s.surname, s.name, a.date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':group_id' => $groupId, ':month' => $yearMonth]);
        return $stmt->fetchAll();
    }

    /**
     * Comprobar si un alumno tiene 3 ausencias consecutivas
     * Devuelve true si las últimas 3 asistencias son 'absent'
     */
    public function checkConsecutiveAbsences($studentId, $threshold = 3) {
        $sql = "SELECT status FROM attendance 
                WHERE student_id = :student_id 
                ORDER BY date DESC 
                LIMIT :threshold";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();

        if (count($records) < $threshold) {
            return false; // No hay suficientes registros
        }

        // Comprobar si todas son 'absent'
        foreach ($records as $record) {
            if ($record['status'] !== 'absent') {
                return false;
            }
        }
        return true;
    }

    /**
     * Crear notificación para el padre cuando hay 3 ausencias
     */
    public function createAbsenceNotification($studentId) {
        // Obtener datos del alumno y padre
        $stmt = $this->db->prepare("
            SELECT s.name, s.surname, s.parent_id 
            FROM students s 
            WHERE s.id = :id AND s.parent_id IS NOT NULL
        ");
        $stmt->execute([':id' => $studentId]);
        $student = $stmt->fetch();

        if (!$student || !$student['parent_id']) {
            return false;
        }

        // Comprobar si ya hay una notificación reciente (últimas 24h)
        $stmt = $this->db->prepare("
            SELECT id FROM notifications 
            WHERE student_id = :student_id AND parent_id = :parent_id
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $stmt->execute([':student_id' => $studentId, ':parent_id' => $student['parent_id']]);
        if ($stmt->fetch()) {
            return false; // Ya notificado recientemente
        }

        $message = "⚠️ Su hijo/a {$student['name']} {$student['surname']} ha faltado 3 días consecutivos. Por favor, póngase en contacto con el centro.";

        $stmt = $this->db->prepare("
            INSERT INTO notifications (parent_id, student_id, message)
            VALUES (:parent_id, :student_id, :message)
        ");
        return $stmt->execute([
            ':parent_id'  => $student['parent_id'],
            ':student_id' => $studentId,
            ':message'    => $message,
        ]);
    }

    /**
     * Obtener notificaciones de un padre
     */
    public function getNotifications($parentId) {
        $stmt = $this->db->prepare("
            SELECT n.*, s.name AS student_name, s.surname AS student_surname
            FROM notifications n
            JOIN students s ON s.id = n.student_id
            WHERE n.parent_id = :parent_id
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([':parent_id' => $parentId]);
        return $stmt->fetchAll();
    }

    /**
     * Marcar notificación como leída
     */
    public function markNotificationRead($notificationId, $parentId) {
        $stmt = $this->db->prepare("
            UPDATE notifications SET is_read = 1 
            WHERE id = :id AND parent_id = :parent_id
        ");
        return $stmt->execute([':id' => $notificationId, ':parent_id' => $parentId]);
    }

    /**
     * Contar asistencias hoy por grupo
     */
    public function getTodayStats($groupId = null) {
        $today = date('Y-m-d');
        $params = [':date' => $today];
        $groupFilter = '';
        
        if ($groupId) {
            $groupFilter = "AND s.group_id = :group_id";
            $params[':group_id'] = $groupId;
        }

        $sql = "SELECT 
                    COUNT(CASE WHEN a.status = 'present' THEN 1 END) AS present_count,
                    COUNT(CASE WHEN a.status = 'absent'  THEN 1 END) AS absent_count,
                    COUNT(CASE WHEN a.status = 'late'    THEN 1 END) AS late_count,
                    COUNT(CASE WHEN a.status = 'justified' THEN 1 END) AS justified_count,
                    (SELECT COUNT(*) FROM students s2 WHERE 1=1 " . 
                    ($groupId ? "AND s2.group_id = :group_id2" : "") . ") AS total_students
                FROM attendance a
                JOIN students s ON s.id = a.student_id
                WHERE a.date = :date $groupFilter";
        
        if ($groupId) {
            $params[':group_id2'] = $groupId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Obtener estadísticas generales
     */
    public function getGeneralStats() {
        $today = date('Y-m-d');
        
        $stats = [];
        
        // Total alumnos
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM students");
        $stats['total_students'] = $stmt->fetch()['total'];
        
        // Presentes hoy
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM attendance WHERE date = :date AND status = 'present'");
        $stmt->execute([':date' => $today]);
        $stats['present_today'] = $stmt->fetch()['total'];
        
        // Ausentes hoy
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM attendance WHERE date = :date AND status = 'absent'");
        $stmt->execute([':date' => $today]);
        $stats['absent_today'] = $stmt->fetch()['total'];
        
        // Total grupos
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM `groups`");
        $stats['total_groups'] = $stmt->fetch()['total'];
        
        // Notificaciones sin leer
        $stmt = $this->db->query("SELECT COUNT(*) AS total FROM notifications WHERE is_read = 0");
        $stats['unread_notifications'] = $stmt->fetch()['total'];
        
        return $stats;
    }
}
