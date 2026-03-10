<?php
/**
 * Clase UserManager - Gestión de usuarios (admin y padres)
 */

class UserManager {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Crear nuevo usuario
     */
    public function create($data) {
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password, role, name, email, phone)
            VALUES (:username, :password, :role, :name, :email, :phone)
        ");
        return $stmt->execute([
            ':username' => $data['username'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':role'     => $data['role'] ?? 'parent',
            ':name'     => $data['name'],
            ':email'    => $data['email'] ?? null,
            ':phone'    => $data['phone'] ?? null,
        ]);
    }

    /**
     * Obtener todos los padres
     */
    public function getParents() {
        $stmt = $this->db->query("SELECT id, username, name, email, phone FROM users WHERE role = 'parent' ORDER BY name");
        return $stmt->fetchAll();
    }

    /**
     * Obtener usuario por ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT id, username, role, name, email, phone, created_at FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Actualizar usuario
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];

        if (isset($data['name'])) {
            $fields[] = "name = :name";
            $params[':name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params[':email'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $fields[] = "phone = :phone";
            $params[':phone'] = $data['phone'];
        }
        if (!empty($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) return false;

        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Eliminar usuario
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Comprobar si un username ya existe
     */
    public function usernameExists($username) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }
}
