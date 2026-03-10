<?php
/**
 * Clase DailyNotes - Notas diarias de alumnos
 */

class DailyNotes {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Añadir una nota
     */
    public function create($studentId, $date, $content, $authorId) {
        $stmt = $this->db->prepare("
            INSERT INTO daily_notes (student_id, date, content, author_id)
            VALUES (:student_id, :date, :content, :author_id)
        ");
        return $stmt->execute([
            ':student_id' => $studentId,
            ':date'       => $date,
            ':content'    => $content,
            ':author_id'  => $authorId,
        ]);
    }

    /**
     * Obtener notas de un alumno
     */
    public function getByStudent($studentId, $limit = 20) {
        $stmt = $this->db->prepare("
            SELECT dn.*, u.name AS author_name
            FROM daily_notes dn
            LEFT JOIN users u ON u.id = dn.author_id
            WHERE dn.student_id = :student_id
            ORDER BY dn.date DESC, dn.created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':student_id', $studentId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener notas de un grupo en una fecha
     */
    public function getByGroupAndDate($groupId, $date) {
        $stmt = $this->db->prepare("
            SELECT dn.*, s.name AS student_name, s.surname AS student_surname, u.name AS author_name
            FROM daily_notes dn
            JOIN students s ON s.id = dn.student_id
            LEFT JOIN users u ON u.id = dn.author_id
            WHERE s.group_id = :group_id AND dn.date = :date
            ORDER BY s.surname, s.name
        ");
        $stmt->execute([':group_id' => $groupId, ':date' => $date]);
        return $stmt->fetchAll();
    }

    /**
     * Eliminar una nota
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM daily_notes WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
