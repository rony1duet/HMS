<?php
class Utility {
    public static function validatePassword($password) {
        $minLength = 8;
        return strlen($password) >= $minLength &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }

    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function uploadFile($file, $allowedTypes, $uploadDir, $maxSize = 5242880) {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new RuntimeException('Invalid parameters.');
        }

        if ($file['size'] > $maxSize) {
            throw new RuntimeException('File size is too large.');
        }

        if (!in_array($file['type'], $allowedTypes)) {
            throw new RuntimeException('Invalid file type.');
        }

        $fileName = sprintf('%s-%s', uniqid(), $file['name']);
        $filePath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new RuntimeException('Failed to move uploaded file.');
        }

        return $fileName;
    }
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeInput($value);
            }
            return $data;
        }
        return htmlspecialchars(strip_tags(trim($data)));
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    public static function formatDate($date, $format = 'Y-m-d') {
        return date($format, strtotime($date));
    }

    public static function calculateDaysBetweenDates($date1, $date2) {
        $datetime1 = new DateTime($date1);
        $datetime2 = new DateTime($date2);
        $interval = $datetime1->diff($datetime2);
        return $interval->days;
    }

    public static function formatCurrency($amount) {
        return number_format($amount, 2);
    }

    public static function validatePhoneNumber($phone) {
        return preg_match('/^[0-9]{10,15}$/', $phone);
    }

    public static function getStatusBadgeClass($status) {
        $classes = [
            'pending' => 'badge bg-warning',
            'paid' => 'badge bg-success',
            'overdue' => 'badge bg-danger',
            'available' => 'badge bg-success',
            'occupied' => 'badge bg-primary',
            'maintenance' => 'badge bg-secondary',
            'in_progress' => 'badge bg-info',
            'resolved' => 'badge bg-success',
            'closed' => 'badge bg-secondary'
        ];
        return $classes[$status] ?? 'badge bg-secondary';
    }

    public static function redirectTo($path) {
        header("Location: $path");
        exit();
    }

    public static function setFlashMessage($type, $message) {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public static function getFlashMessages() {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }

    public static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}