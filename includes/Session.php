<?php

/**
 * Secure Session Management Class
 * 
 * Features:
 * - Role-based access control (admin, provost, staff, student)
 * - CSRF protection
 * - Session fingerprinting
 * - Automatic session regeneration
 * - Strict security headers
 */
class Session
{
    // Session configuration
    private static $sessionTimeout = 3600; // 1 hour in seconds
    private static $regenerationInterval = 1800; // 30 minutes in seconds
    private static $cookieParams = [
        'lifetime' => 0,        // Until browser closes
        'path' => '/',
        'domain' => '',         // Current domain only
        'secure' => true,       // Requires HTTPS
        'httponly' => true,     // No JavaScript access
        'samesite' => 'Strict'  // Strict same-site policy
    ];

    /**
     * Initialize the session with secure settings
     */
    public static function init()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Apply secure session settings
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', self::$sessionTimeout);
            ini_set('session.sid_length', '128');
            ini_set('session.sid_bits_per_character', '6');

            // Set custom cookie parameters
            session_set_cookie_params(self::$cookieParams);

            session_start();

            // Regenerate ID on initialization to prevent fixation
            self::regenerate(true);
        }

        // Initialize CSRF token if not exists
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Validate session fingerprint
        if (!self::validateFingerprint()) {
            self::destroy();
            header('Location: /HMS/?error=session_tampered');
            exit();
        }

        // Check and handle session timeout
        self::checkTimeout();

        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
    }

    /**
     * Validate session fingerprint to prevent hijacking
     */
    private static function validateFingerprint(): bool
    {
        $currentFingerprint = self::generateFingerprint();

        if (empty($_SESSION['fingerprint'])) {
            $_SESSION['fingerprint'] = $currentFingerprint;
            return true;
        }

        return hash_equals($_SESSION['fingerprint'], $currentFingerprint);
    }

    /**
     * Generate session fingerprint
     */
    private static function generateFingerprint(): string
    {
        $components = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''
        ];

        return hash_hmac('sha256', implode('|', $components), 'your_secret_salt_here');
    }

    /**
     * Check session timeout and regenerate if needed
     */
    private static function checkTimeout(): void
    {
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];

            // Destroy expired session
            if ($inactive >= self::$sessionTimeout) {
                self::destroy();
                header('Location: /HMS/?timeout=1');
                exit();
            }

            // Regenerate session ID periodically
            if ($inactive >= self::$regenerationInterval) {
                self::regenerate();
            }
        }
    }

    /**
     * Regenerate session ID
     */
    public static function regenerate(bool $destroyOld = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Preserve important session data
            $preservedData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'role' => $_SESSION['role'] ?? null,
                'fingerprint' => $_SESSION['fingerprint'] ?? null,
                'csrf_token' => $_SESSION['csrf_token'] ?? null,
                'last_activity' => $_SESSION['last_activity'] ?? null
            ];

            session_regenerate_id($destroyOld);

            // Restore preserved data
            foreach ($preservedData as $key => $value) {
                if ($value !== null) {
                    $_SESSION[$key] = $value;
                }
            }
        }
    }

    /**
     * Set session data with validation
     */
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

    /**
     * Get session data
     */
    public static function get(string $key)
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Remove session data
     */
    public static function unset(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the session completely
     */
    public static function destroy(): void
    {
        // Clear session data
        $_SESSION = [];

        // Delete session cookie
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

    /**
     * Set authenticated user data
     */
    public static function setUserData(array $userData): void
    {
        $requiredFields = ['id', 'role', 'email', 'display_name'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new InvalidArgumentException("Missing required field: $field");
            }
        }

        // Validate role against known roles
        $validRoles = array_keys(self::getRoleHierarchy());
        if (!in_array(strtolower($userData['role']), $validRoles, true)) {
            throw new InvalidArgumentException("Invalid user role specified");
        }

        // Sanitize and set user data
        $_SESSION['user_id'] = (int)$userData['id'];
        $_SESSION['role'] = strtolower($userData['role']);
        $_SESSION['email'] = filter_var($userData['email'], FILTER_SANITIZE_EMAIL);
        $_SESSION['display_name'] = htmlspecialchars($userData['display_name'], ENT_QUOTES, 'UTF-8');

        // Regenerate session ID on login
        self::regenerate(true);
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']) && self::validateFingerprint();
    }

    /**
     * Get user role
     */
    public static function getUserRole(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Get user ID
     */
    public static function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /**
     * Check if user has required permission
     */
    public static function hasPermission(string $requiredRole): bool
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $userRole = self::getUserRole();
        if (!$userRole) {
            return false;
        }

        // Define role hierarchy with permissions
        $roleHierarchy = [
            'admin' => ['admin'],
            'provost' => ['admin', 'provost'],
            'staff' => ['admin', 'provost', 'staff'],
            'student' => ['admin', 'provost', 'staff', 'student']
        ];

        return isset($roleHierarchy[$requiredRole]) &&
            in_array($userRole, $roleHierarchy[$requiredRole], true);
    }

    /**
     * Get all roles with descriptions
     */
    public static function getRoleHierarchy(): array
    {
        return [
            'admin' => 'System Administrator',
            'provost' => 'Hall Provost',
            'staff' => 'Hall Staff',
            'student' => 'Student'
        ];
    }

    /**
     * Get CSRF token
     */
    public static function getCsrfToken(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(string $token): bool
    {
        return isset($_SESSION['csrf_token']) &&
            hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Complete logout procedure
     */
    public static function logout(): void
    {
        // Clear all session data
        $_SESSION = [];

        // Delete session cookie
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
