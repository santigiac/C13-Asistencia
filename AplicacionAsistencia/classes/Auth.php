<?php
/**
 * Clase Auth - Gestión de autenticación y sesiones
 * Soporta roles: admin, teacher y parent
 */

require_once __DIR__ . '/../config/app.php';

class Auth {

    /**
     * Intentar login
     * @return array|false  Datos del usuario si es correcto, false si falla
     */
    public static function login($username, $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Guardar datos en sesión
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['timestamp'] = time();
            return $user;
        }
        return false;
    }

    /**
     * Cerrar sesión
     */
    public static function logout() {
        session_unset();
        session_destroy();
    }

    /**
     * Comprobar si el usuario está autenticado
     */
    public static function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        // Comprobar timeout
        if (isset($_SESSION['timestamp']) && (time() - $_SESSION['timestamp'] > SESSION_TIMEOUT)) {
            self::logout();
            return false;
        }
        $_SESSION['timestamp'] = time(); // Renovar
        return true;
    }

    /**
     * Obtener el rol del usuario actual
     */
    public static function getRole() {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Obtener el ID del usuario actual
     */
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Obtener el nombre del usuario actual
     */
    public static function getUserName() {
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Requerir autenticación como Admin.
     * Si no es admin, redirige al login.
     */
    public static function requireAdmin() {
        if (!self::isLoggedIn() || self::getRole() !== 'admin') {
            header('Location: ' . BASE_URL . '/index.php?error=access');
            exit;
        }
    }

    /**
     * Requerir autenticación como Teacher.
     * Si no es teacher, redirige al login.
     */
    public static function requireTeacher() {
        if (!self::isLoggedIn() || self::getRole() !== 'teacher') {
            header('Location: ' . BASE_URL . '/index.php?error=access');
            exit;
        }
    }

    /**
     * Requerir autenticación como Admin o Teacher.
     * Para páginas compartidas (ej: pasar lista).
     */
    public static function requireAdminOrTeacher() {
        if (!self::isLoggedIn() || !in_array(self::getRole(), ['admin', 'teacher'])) {
            header('Location: ' . BASE_URL . '/index.php?error=access');
            exit;
        }
    }

    /**
     * Requerir autenticación como Parent.
     * Si no es parent, redirige al login.
     */
    public static function requireParent() {
        if (!self::isLoggedIn() || self::getRole() !== 'parent') {
            header('Location: ' . BASE_URL . '/index.php?error=access');
            exit;
        }
    }

    /**
     * Requerir cualquier autenticación
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/index.php?error=session');
            exit;
        }
    }
}
