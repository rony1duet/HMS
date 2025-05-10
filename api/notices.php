<?php
require_once '../../config/database.php';
require_once '../../models/Notice.php';
require_once '../../models/NoticeAttachment.php';
require_once '../../includes/Session.php';

header('Content-Type: application/json');

// Initialize session and models
Session::init();
$notice = new Notice($conn);
$noticeAttachment = new NoticeAttachment($conn);

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure user is logged in
if (!Session::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single notice
                $noticeId = (int)$_GET['id'];
                $result = $notice->getNoticeById($noticeId);

                if (!$result) {
                    http_response_code(404);
                    throw new Exception('Notice not found');
                }

                // Get attachments for this notice
                $attachments = $noticeAttachment->getAttachment($noticeId);
                $result['attachments'] = $attachments;

                echo json_encode($result);
            } else {
                // Get list of notices
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
                $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
                $hallId = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : null;

                // Verify hall ownership if provost
                if (Session::hasPermission('provost')) {
                    $provostSlug = Session::getSlug();
                    $stmt = $conn->prepare("SELECT hall_id FROM provost_approvals WHERE slug = ? AND status = 'approved'");
                    $stmt->execute([$provostSlug]);
                    $provostHall = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$provostHall) {
                        http_response_code(403);
                        throw new Exception('No hall assigned to this provost');
                    }

                    // Override any hall_id parameter with provost's hall
                    $hallId = $provostHall['hall_id'];
                }

                $notices = $notice->getNoticesByHallId($hallId, $page, $perPage, $filter);
                $total = $notice->countNoticesByHall($hallId, $filter);

                echo json_encode([
                    'notices' => $notices,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage
                ]);
            }
            break;

        case 'POST':
            // Create new notice
            if (!Session::hasPermission('provost')) {
                http_response_code(403);
                throw new Exception('Permission denied');
            }

            // Get provost's hall
            $provostSlug = Session::getSlug();
            $stmt = $conn->prepare("SELECT hall_id FROM provost_approvals WHERE slug = ? AND status = 'approved'");
            $stmt->execute([$provostSlug]);
            $provostHall = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$provostHall) {
                http_response_code(403);
                throw new Exception('No hall assigned to this provost');
            }

            // Parse input data
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                http_response_code(400);
                throw new Exception('Invalid request data');
            }

            // Validate required fields
            $required = ['title', 'content', 'importance', 'start_date'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    throw new Exception("Missing required field: $field");
                }
            }

            // Prepare notice data
            $noticeData = [
                'title' => trim($data['title']),
                'content' => trim($data['content']),
                'posted_by_slug' => $provostSlug,
                'hall_id' => $provostHall['hall_id'],
                'importance' => $data['importance'],
                'start_date' => $data['start_date'],
                'end_date' => !empty($data['end_date']) ? $data['end_date'] : null
            ];

            // Create notice
            $noticeId = $notice->createNotice($noticeData);
            if (!$noticeId) {
                throw new Exception('Failed to create notice');
            }

            // Handle file upload if present (for multipart/form-data)
            if (!empty($_FILES['attachment'])) {
                $uploadDir = '../../uploads/notices/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $file = $_FILES['attachment'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileType = $file['type'];
                $fileSize = $file['size'];

                // Validate file
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception('Invalid file type');
                }

                if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
                    throw new Exception('File size exceeds limit');
                }

                $uniqueName = uniqid() . '_' . $fileName;
                $uploadPath = $uploadDir . $uniqueName;

                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    $attachmentData = [
                        'notice_id' => $noticeId,
                        'file_name' => $fileName,
                        'file_path' => $uploadPath,
                        'file_type' => $fileType,
                        'file_size' => $fileSize
                    ];

                    if (!$noticeAttachment->createAttachment($noticeId, $attachmentData)) {
                        // If attachment fails, delete the notice we just created
                        $notice->deleteNotice($noticeId);
                        throw new Exception('Failed to save attachment');
                    }
                } else {
                    // If upload fails, delete the notice we just created
                    $notice->deleteNotice($noticeId);
                    throw new Exception('Failed to upload file');
                }
            }

            echo json_encode([
                'success' => true,
                'id' => $noticeId,
                'message' => 'Notice created successfully'
            ]);
            break;

        case 'PUT':
            // Update notice
            if (!Session::hasPermission('provost')) {
                http_response_code(403);
                throw new Exception('Permission denied');
            }

            // Parse input data
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['id'])) {
                http_response_code(400);
                throw new Exception('Invalid request data');
            }

            $noticeId = (int)$data['id'];

            // Verify notice belongs to provost's hall
            $provostSlug = Session::getSlug();
            $stmt = $conn->prepare("SELECT pa.hall_id FROM provost_approvals pa 
                                  JOIN notices n ON pa.hall_id = n.hall_id
                                  WHERE pa.slug = ? AND pa.status = 'approved' AND n.id = ?");
            $stmt->execute([$provostSlug, $noticeId]);
            $validNotice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$validNotice) {
                http_response_code(403);
                throw new Exception('Not authorized to edit this notice');
            }

            // Prepare update data
            $updateData = [];
            $allowedFields = ['title', 'content', 'importance', 'start_date', 'end_date'];

            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $field === 'end_date' && empty($data[$field]) ? null : $data[$field];
                }
            }

            if (empty($updateData)) {
                http_response_code(400);
                throw new Exception('No valid fields to update');
            }

            // Update notice
            if (!$notice->updateNotice($noticeId, $updateData)) {
                throw new Exception('Failed to update notice');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Notice updated successfully'
            ]);
            break;

        case 'DELETE':
            // Delete notice
            if (!Session::hasPermission('provost')) {
                http_response_code(403);
                throw new Exception('Permission denied');
            }

            if (!isset($_GET['id'])) {
                http_response_code(400);
                throw new Exception('Notice ID is required');
            }

            $noticeId = (int)$_GET['id'];

            // Verify notice belongs to provost's hall
            $provostSlug = Session::getSlug();
            $stmt = $conn->prepare("SELECT pa.hall_id FROM provost_approvals pa 
                                  JOIN notices n ON pa.hall_id = n.hall_id
                                  WHERE pa.slug = ? AND pa.status = 'approved' AND n.id = ?");
            $stmt->execute([$provostSlug, $noticeId]);
            $validNotice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$validNotice) {
                http_response_code(403);
                throw new Exception('Not authorized to delete this notice');
            }

            // Get attachments to delete files
            $attachments = $noticeAttachment->getAttachment($noticeId);
            foreach ($attachments as $attachment) {
                if (file_exists($attachment['file_path'])) {
                    unlink($attachment['file_path']);
                }
                $noticeAttachment->deleteAttachment($attachment['id']);
            }

            // Delete notice
            if (!$notice->deleteNotice($noticeId)) {
                throw new Exception('Failed to delete notice');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Notice deleted successfully'
            ]);
            break;

        default:
            http_response_code(405);
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    $statusCode = http_response_code();
    if ($statusCode === 200) {
        http_response_code(500);
    }
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTrace() // Remove in production
    ]);
}
