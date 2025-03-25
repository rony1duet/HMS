<?php
class Session
{
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.gc_maxlifetime', 3600);
            session_start();
        }
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
            self::destroy();
            header('Location: /HMS/');
            exit();
        }
        $_SESSION['last_activity'] = time();
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function get($key)
    {
        return $_SESSION[$key] ?? null;
    }

    public static function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }

    public static function unset($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public static function getUserRole()
    {
        return $_SESSION['role'] ?? null;
    }

    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function regenerate()
    {
        session_regenerate_id(true);
    }

    public static function setUserData($userData)
    {
        if (!is_array($userData) || empty($userData['id']) || empty($userData['role'])) {
            throw new Exception('Invalid user data provided');
        }
        self::set('user_id', $userData['id']);
        self::set('role', $userData['role']);
        self::set('email', $userData['email']);
        self::set('display_name', $userData['display_name']);
        self::regenerate();
    }

    public static function hasPermission($requiredRole)
    {
        $userRole = self::getUserRole();
        if (!$userRole) return false;

        switch ($requiredRole) {
            case 'admin':
                return $userRole === 'admin';
            case 'staff':
                return in_array($userRole, ['admin', 'staff']);
            case 'student':
                return in_array($userRole, ['admin', 'staff', 'student']);
            default:
                return false;
        }
    }
}
