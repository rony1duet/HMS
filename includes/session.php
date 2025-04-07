<?php
class Session
{
    private static $sessionTimeout = 3600;
    private static $regenerationInterval = 1800;
    private static $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    public static function init(bool $regenerate = true)
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', self::$sessionTimeout);
            ini_set('session.sid_length', '128');
            ini_set('session.sid_bits_per_character', '6');
            session_set_cookie_params(self::$cookieParams);
            session_start();
            if ($regenerate) {
                self::regenerate(true);
            }
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        if (!self::validateFingerprint()) {
            self::destroy();
            header('Location: /HMS/?error=session_tampered');
            exit();
        }
        self::checkTimeout();
        $_SESSION['last_activity'] = time();
    }

    private static function validateFingerprint(): bool
    {
        $currentFingerprint = self::generateFingerprint();
        if (empty($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = $currentFingerprint;
            return true;
        }
        return hash_equals($_SESSION['fingerprint'], $currentFingerprint);
    }

    private static function generateFingerprint(): string
    {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''
        ];
        return hash_hmac('sha256', implode('|', $components), 'your_secret_salt_here');
    }

    private static function checkTimeout(): void
    {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            if ($inactive >= self::$sessionTimeout) {
                self::destroy();
                header('Location: /HMS/?timeout=1');
                exit();
            }
            if ($inactive >= self::$regenerationInterval) {
                self::regenerate();
            }
        }
    }

    public static function regenerate(bool $destroyOld = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionData = $_SESSION;
            session_regenerate_id($destroyOld);
            $_SESSION = $sessionData;
        }
    }

    public static function set(string $key, $value): void
    {
        if (is_array($value)) {
            $_SESSION[$key] = array_map('htmlspecialchars', $value);
        } elseif (is_string($value)) {
            $_SESSION[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } else {
            $_SESSION[$key] = $value;
        }
    }

    public static function get(string $key)
    {
        return $_SESSION[$key] ?? null;
    }

    public static function unset(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function setUserData(array $userData): void
    {
        $requiredFields = ['id', 'role', 'email', 'display_name', 'slug'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new InvalidArgumentException("Missing required field: $field");
            }
        }

        $validRoles = array_keys(self::getRoleHierarchy());
        if (!in_array(strtolower($userData['role']), $validRoles, true)) {
            throw new InvalidArgumentException("Invalid user role specified");
        }

        $_SESSION['id'] = (int)$userData['id'];
        $_SESSION['role'] = strtolower($userData['role']);
        $_SESSION['email'] = filter_var($userData['email'], FILTER_SANITIZE_EMAIL);
        $_SESSION['display_name'] = htmlspecialchars($userData['display_name'], ENT_QUOTES, 'UTF-8');
        $_SESSION['slug'] = htmlspecialchars($userData['slug'], ENT_QUOTES, 'UTF-8');

        self::regenerate(true);
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['id']) && self::validateFingerprint();
    }

    public static function getUserRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function getUserId(): ?int
    {
        return isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;
    }

    public static function hasPermission(string $requiredRole): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }
        $userRole = self::getUserRole();
        if (!$userRole) {
            return false;
        }
        $roleHierarchy = [
            'admin' => ['admin'],
            'provost' => ['admin', 'provost'],
            'staff' => ['admin', 'provost', 'staff'],
            'student' => ['admin', 'provost', 'staff', 'student']
        ];
        return isset($roleHierarchy[$requiredRole]) &&
            in_array($userRole, $roleHierarchy[$requiredRole], true);
    }

    public static function getRoleHierarchy(): array
    {
        return [
            'admin' => 'System Administrator',
            'provost' => 'Hall Provost',
            'staff' => 'Hall Staff',
            'student' => 'Student'
        ];
    }

    public static function getCsrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) &&
            hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function getSlug(): string
    {
        return $_SESSION['slug'] ?? '';
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        self::destroy();
    }
}
