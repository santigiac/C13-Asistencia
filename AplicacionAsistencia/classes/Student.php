<?php
/**
 * Clase Student - Gestión de alumnos
 */

class Student {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Crear un nuevo alumno
     */
    public function create($data) {
        $sql = "INSERT INTO students (name, surname, birthdate, parent_id, group_id, notes)
                VALUES (:name, :surname, :birthdate, :parent_id, :group_id, :notes)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name'      => $data['name'],
            ':surname'   => $data['surname'],
            ':birthdate' => $data['birthdate'] ?: null,
            ':parent_id' => $data['parent_id'] ?: null,
            ':group_id'  => $data['group_id'] ?: null,
            ':notes'     => $data['notes'] ?? '',
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Obtener alumno por ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, 
                   g.name AS group_name, 
                   u.name AS parent_name, 
                   u.phone AS parent_phone,
                   u.email AS parent_email
            FROM students s
            LEFT JOIN `groups` g ON s.group_id = g.id
            LEFT JOIN users u ON s.parent_id = u.id
            WHERE s.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Obtener todos los alumnos (con filtros opcionales)
     */
    public function getAll($filters = []) {
        $where = [];
        $params = [];

        if (!empty($filters['group_id'])) {
            $where[] = "s.group_id = :group_id";
            $params[':group_id'] = $filters['group_id'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(s.name LIKE :search OR s.surname LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['parent_id'])) {
            $where[] = "s.parent_id = :parent_id";
            $params[':parent_id'] = $filters['parent_id'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT s.*, 
                       g.name AS group_name, 
                       u.name AS parent_name, 
                       u.phone AS parent_phone
                FROM students s
                LEFT JOIN `groups` g ON s.group_id = g.id
                LEFT JOIN users u ON s.parent_id = u.id
                $whereClause
                ORDER BY s.surname, s.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Actualizar alumno
     */
    public function update($id, $data) {
        $sql = "UPDATE students SET 
                    name = :name, 
                    surname = :surname, 
                    birthdate = :birthdate,
                    parent_id = :parent_id,
                    group_id = :group_id,
                    notes = :notes
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name'      => $data['name'],
            ':surname'   => $data['surname'],
            ':birthdate' => $data['birthdate'] ?: null,
            ':parent_id' => $data['parent_id'] ?: null,
            ':group_id'  => $data['group_id'] ?: null,
            ':notes'     => $data['notes'] ?? '',
            ':id'        => $id,
        ]);
    }

    /**
     * Eliminar alumno
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM students WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Obtener alumnos por grupo
     */
    public function getByGroup($groupId) {
        return $this->getAll(['group_id' => $groupId]);
    }

    /**
     * Obtener alumno(s) de un padre
     */
    public function getByParent($parentId) {
        return $this->getAll(['parent_id' => $parentId]);
    }
}
