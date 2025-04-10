<?php
require_once '../includes/Session.php';
require_once '../models/ProvostApproval.php';
require_once '../config/database.php';

Session::init();

// Check if user is logged in and has admin role
if (!Session::isLoggedIn() || !Session::getUserRole() == 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['slug']) || !isset($data['hall_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

// Validate hall_id is numeric
if (!is_numeric($data['hall_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid hall ID']);
    exit();
}

$provostApproval = new ProvostApproval($conn);

try {
    // Update approval status
    $provostApproval->approveProvost(
        $data['slug'],
        Session::getSlug(), // admin's slug as approver
        (int)$data['hall_id'] // selected hall ID
    );

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to approve provost']);
}
