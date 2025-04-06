<?php
session_start();
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || Session::getUserRole() !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get student's scheduled meals for current month
// Get student profile ID using slug
$slug = Session::getSlug();
$profile_sql = "SELECT id FROM student_profiles WHERE slug = :slug";
$stmt = $conn->prepare($profile_sql);
$stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
$stmt->execute();
$student_profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student_profile) {
    http_response_code(404);
    echo json_encode(['error' => 'Student profile not found']);
    exit();
}

$student_id = $student_profile['id'];
$current_month = date('m');
$current_year = date('Y');

$sql = "SELECT * FROM meal_schedules 
        WHERE student_id = :student_id 
        AND month = :month 
        AND year = :year";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindValue(':month', $current_month, PDO::PARAM_INT);
    $stmt->bindValue(':year', $current_year, PDO::PARAM_INT);
    $stmt->execute();

    $scheduled_days = [];
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        for ($i = 1; $i <= 31; $i++) {
            if ($row['day' . $i] == 1) {
                $scheduled_days[] = $i;
            }
        }
    }

    header('Content-Type: application/json');
    echo json_encode(['scheduled_days' => $scheduled_days]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log('Database Error: ' . $e->getMessage());
}
