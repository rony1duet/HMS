<?php
require_once '../includes/Session.php';
require_once '../config/database.php';

Session::init();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: ../index.php');
    exit();
}

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
$timestamp = time();
$attempts_key = "login_attempts_{$ip}";
$lockout_key = "login_lockout_{$ip}";

if (isset($_SESSION[$lockout_key]) && $_SESSION[$lockout_key] > $timestamp) {
    $wait_time = ceil(($_SESSION[$lockout_key] - $timestamp) / 60);
    $_SESSION['error'] = "Too many failed attempts. Please wait {$wait_time} minutes.";
    header('Location: ../index.php');
    exit();
}

if (!isset($_SESSION[$attempts_key])) {
    $_SESSION[$attempts_key] = ['count' => 0, 'first_attempt' => $timestamp];
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$remember = isset($_POST['remember']);

try {
    $stmt = $conn->prepare('SELECT id, role, password_hash, display_name, email, slug FROM users WHERE email = ? AND role = "admin"');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Increment failed attempts
        $_SESSION[$attempts_key]['count']++;

        // Check if we should implement lockout
        if ($_SESSION[$attempts_key]['count'] >= 5) {
            $_SESSION[$lockout_key] = $timestamp + (15 * 60); // 15 minutes lockout
            unset($_SESSION[$attempts_key]);
            $_SESSION['error'] = 'Account locked. Please try again after 15 minutes.';
        } else {
            $_SESSION['error'] = 'No admin account found with this email.';
        }
        header('Location: ../index.php');
        exit();
    }

    if (!password_verify($password, $user['password_hash'])) {
        // Increment failed attempts
        $_SESSION[$attempts_key]['count']++;

        // Check if we should implement lockout
        if ($_SESSION[$attempts_key]['count'] >= 5) {
            $_SESSION[$lockout_key] = $timestamp + (15 * 60); // 15 minutes lockout
            unset($_SESSION[$attempts_key]);
            $_SESSION['error'] = 'Account locked. Please try again after 15 minutes.';
        } else {
            $_SESSION['error'] = 'Incorrect password. Please try again.';
        }
        header('Location: ../index.php');
        exit();
    }

    // Reset login attempts on successful login
    unset($_SESSION[$attempts_key]);
    unset($_SESSION[$lockout_key]);

    // Ensure slug exists or generate one if missing and total length is 10 characters
    if (empty($user['slug'])) {
        $user['slug'] = 'admin_' . substr(md5(uniqid(rand(), true)), 0, 4);
        $stmt = $conn->prepare('UPDATE users SET slug = ? WHERE id = ?');
        $stmt->execute([$user['slug'], $user['id']]);
    }

    // Set user data using Session class
    Session::setUserData([
        'id' => $user['id'],
        'role' => $user['role'],
        'email' => $user['email'],
        'display_name' => $user['display_name'],
        'slug' => $user['slug']
    ]);

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

        $stmt = $conn->prepare('INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $token, $expires]);

        setcookie('remember_token', $token, strtotime('+30 days'), '/', '', true, true);
    }

    $stmt = $conn->prepare('UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?');
    $stmt->execute([$user['id']]);

    header('Location: ../dashboard/admin.php');
    exit();
} catch (PDOException $e) {
    error_log('Login error: ' . $e->getMessage());
    $_SESSION['error'] = 'An error occurred during login. Please try again.';
    header('Location: ../index.php');
    exit();
}
