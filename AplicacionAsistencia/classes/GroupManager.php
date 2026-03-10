<?php
/**
 * Clase GroupManager - Gestión de grupos
 * (Se llama GroupManager porque "Group" es palabra reservada en SQL)
 */

class GroupManager {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Crear un nuevo grupo
     */
    public function create($name, $description = '') {
        $stmt = $this->db->prepare("INSERT INTO `groups` (name, description) VALUES (:name, :desc)");
        $stmt->execute([':name' => $name, ':desc' => $description]);
        return $this->db->lastInsertId();
    }

    /**
     * Obtener todos los grupos
     */
    public function getAll() {
        $stmt = $this->db->query("
            SELECT g.*, COUNT(s.id) AS student_count
            FROM `groups` g
            LEFT JOIN students s ON s.group_id = g.id
            GROUP BY g.id
            ORDER BY g.name
        ");
        return $stmt->fetchAll();
    }

    /**
     * Obtener grupo por ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM `groups` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Obtener el grupo asignado a un profesor
     * @param int $teacherId  ID del usuario con rol 'teacher'
     * @return array|false  Datos del grupo o false si no se encuentra
     */
    public function getByTeacherId($teacherId) {
        $stmt = $this->db->prepare("
            SELECT g.*, COUNT(s.id) AS student_count
            FROM `groups` g
            LEFT JOIN students s ON s.group_id = g.id
            WHERE g.teacher_id = :teacher_id
            GROUP BY g.id
            LIMIT 1
        ");
        $stmt->execute([':teacher_id' => $teacherId]);
        return $stmt->fetch();
    }

    /**
     * Actualizar grupo
     */
    public function update($id, $name, $description = '') {
        $stmt = $this->db->prepare("UPDATE `groups` SET name = :name, description = :desc WHERE id = :id");
        return $stmt->execute([':name' => $name, ':desc' => $description, ':id' => $id]);
    }

    /**
     * Eliminar grupo
     */
    public function delete($id) {
        // Primero quitar el grupo de los alumnos
        $stmt = $this->db->prepare("UPDATE students SET group_id = NULL WHERE group_id = :id");
        $stmt->execute([':id' => $id]);
        // Luego eliminar el grupo
        $stmt = $this->db->prepare("DELETE FROM `groups` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
