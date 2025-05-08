<?php
$title = 'Notice Management | Hall Management System';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Notice.php';
require_once '../models/NoticeAttachment.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::hasPermission('provost')) {
    header('Location: /HMS/');
    exit();
}

$user = new User($conn);
$notice = new Notice($conn);
$noticeAttachment = new NoticeAttachment($conn);

// Get provost's hall_id
$provostSlug = Session::getSlug();
try {
    $query = "SELECT hall_id FROM provost_approvals WHERE slug = :provostSlug AND status = 'approved'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":provostSlug", $provostSlug, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hallId = $result ? (int)$result['hall_id'] : null;

    if (!$hallId) {
        $_SESSION['error'] = 'Could not determine your assigned hall. Please contact the administrator.';
        header('Location: /HMS/dashboard/provost.php');
        exit;
    }
} catch (PDOException $e) {
    error_log('Error getting provost hall_id: ' . $e->getMessage());
    $_SESSION['error'] = 'Database error occurred. Please try again later.';
    header('Location: /HMS/dashboard/provost.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Create new notice
        if ($_POST['action'] === 'create') {
            $uploadDir = '../uploads/notices/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $attachments = [];
            if (!empty($_FILES['attachments']['name'][0])) {
                foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
                    $fileName = $_FILES['attachments']['name'][$key];
                    $fileSize = $_FILES['attachments']['size'][$key];
                    $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

                    if (!NoticeAttachment::isValidFileType($fileType)) {
                        $_SESSION['error'] = 'Invalid file type. Only PDF and images are allowed.';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }

                    $uniqueName = NoticeAttachment::generateUniqueFilename($fileName);
                    $filePath = $uploadDir . $uniqueName;

                    if (move_uploaded_file($tmp_name, $filePath)) {
                        $attachments[] = [
                            'file_name' => $fileName,
                            'file_path' => $filePath,
                            'file_type' => $fileType === 'pdf' ? 'pdf' : 'image',
                            'file_size' => $fileSize
                        ];
                    }
                }
            }

            $noticeData = [
                'title' => trim($_POST['title']),
                'content' => trim($_POST['content']),
                'posted_by_slug' => $provostSlug,
                'hall_id' => $hallId,
                'importance' => $_POST['importance'],
                'start_date' => $_POST['start_date'],
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null
            ];

            if ($notice->createNotice($noticeData, $attachments)) {
                $_SESSION['success'] = 'Notice created successfully.';
            } else {
                $_SESSION['error'] = 'Failed to create notice.';
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Update existing notice
        elseif ($_POST['action'] === 'update' && isset($_POST['notice_id'])) {
            $noticeId = (int)$_POST['notice_id'];
            
            // Verify the notice belongs to this provost's hall
            $existingNotice = $notice->getNoticeById($noticeId);
            if (!$existingNotice || $existingNotice['hall_id'] != $hallId) {
                $_SESSION['error'] = 'Notice not found or unauthorized to edit.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
            $noticeData = [
                'title' => trim($_POST['title']),
                'content' => trim($_POST['content']),
                'importance' => $_POST['importance'],
                'start_date' => $_POST['start_date'],
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null
            ];
            
            if ($notice->updateNotice($noticeId, $noticeData)) {
                $_SESSION['success'] = 'Notice updated successfully.';
            } else {
                $_SESSION['error'] = 'Failed to update notice.';
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Delete notice
        elseif ($_POST['action'] === 'delete' && isset($_POST['notice_id'])) {
            $noticeId = (int)$_POST['notice_id'];
            
            // Verify the notice belongs to this provost's hall
            $existingNotice = $notice->getNoticeById($noticeId);
            if (!$existingNotice || $existingNotice['hall_id'] != $hallId) {
                $_SESSION['error'] = 'Notice not found or unauthorized to delete.';
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
            if ($notice->deleteNotice($noticeId)) {
                $_SESSION['success'] = 'Notice deleted successfully.';
            } else {
                $_SESSION['error'] = 'Failed to delete notice.';
            }
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Get existing notices with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$notices = $notice->getNoticesByHall($hallId, $page, $perPage);

// Include header
require_once '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 0.5rem;
            --transition: all 0.3s ease;
        }
        
        .notice-card {
            border: 1px solid #e9ecef;
            border-left: 4px solid;
            transition: var(--transition);
            border-radius: var(--border-radius);
            height: 100%;
            display: flex;
            flex-direction: column;
            background: #fff;
            position: relative;
            overflow: hidden;
        }
        
        .notice-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .notice-urgent {
            border-left-color: var(--danger-color);
            background: linear-gradient(to right, rgba(247, 37, 133, 0.05), transparent);
        }
        
        .notice-important {
            border-left-color: var(--warning-color);
            background: linear-gradient(to right, rgba(248, 150, 30, 0.05), transparent);
        }
        
        .notice-normal {
            border-left-color: var(--info-color);
            background: linear-gradient(to right, rgba(72, 149, 239, 0.05), transparent);
        }
        
        .badge-urgent {
            background-color: var(--danger-color);
            color: white;
            font-weight: 500;
        }
        
        .badge-important {
            background-color: var(--warning-color);
            color: white;
            font-weight: 500;
        }
        
        .badge-normal {
            background-color: var(--info-color);
            color: white;
            font-weight: 500;
        }
        
        .attachment-badge {
            background-color: rgba(233, 236, 239, 0.5);
            color: #495057;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-flex;
            align-items: center;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .attachment-badge i {
            margin-right: 5px;
        }
        
        .empty-state {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 60px 30px;
            text-align: center;
            border: 2px dashed #dee2e6;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #adb5bd;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 20px rgba(67, 97, 238, 0.3);
            z-index: 1000;
            transition: var(--transition);
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .floating-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(67, 97, 238, 0.4);
        }
        
        .date-badge {
            background-color: rgba(233, 236, 239, 0.5);
            color: #495057;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            background: rgba(248, 249, 250, 0.5);
            border-radius: var(--border-radius);
            border: 1px dashed #dee2e6;
        }
        
        .file-preview-item {
            background: white;
            border: 1px solid rgba(0,0,0,0.05);
            border-radius: var(--border-radius);
            padding: 10px 15px;
            display: flex;
            align-items: center;
            font-size: 0.8rem;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .file-preview-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        .file-preview-item i {
            margin-right: 5px;
            color: #6c757d;
        }
        
        .card-body {
            flex: 1;
            padding: 1.5rem;
        }
        
        .card-footer {
            background-color: rgba(0,0,0,0.02);
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
        }
        
        #editor-container {
            height: 250px;
            margin-bottom: 15px;
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        .ql-toolbar {
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            border-color: #dee2e6 !important;
            background: #f8f9fa;
        }
        
        .ql-container {
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            font-family: inherit;
            border-color: #dee2e6 !important;
        }
        
        .notice-content img {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            margin: 10px 0;
        }
        
        .attachment-thumbnail {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--border-radius);
            margin-right: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .attachment-thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .attachment-preview {
            max-width: 100%;
            max-height: 400px;
            margin-bottom: 15px;
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .tab-content {
            padding: 25px 0;
        }

        .notice-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #dee2e6;
            background: white;
            color: #495057;
            font-size: 0.9rem;
            transition: var(--transition);
            cursor: pointer;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .notice-search {
            position: relative;
            margin-bottom: 20px;
        }

        .notice-search input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 40px;
            border-radius: var(--border-radius);
            border: 1px solid #dee2e6;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .notice-search input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .notice-search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }

        .dropdown-menu {
            border-radius: var(--border-radius);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 0.5rem;
        }

        .dropdown-item {
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .dropdown-item i {
            width: 20px;
        }

        .modal-content {
            border-radius: var(--border-radius);
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .modal-header {
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid rgba(0,0,0,0.05);
            padding: 1.5rem;
        }

        .form-control,
        .form-select {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            border-color: #dee2e6;
            transition: var(--transition);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .btn {
            border-radius: var(--border-radius);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-1px);
        }

        .pagination {
            gap: 5px;
        }

        .page-link {
            border-radius: var(--border-radius);
            padding: 0.5rem 1rem;
            transition: var(--transition);
        }

        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .alert {
            border-radius: var(--border-radius);
            border: 1px solid rgba(0,0,0,0.05);
            padding: 1rem 1.5rem;
        }

        .breadcrumb {
            margin-bottom: 0;
        }

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .breadcrumb-item a:hover {
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-0">Notice Management</h2>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="/HMS/dashboard/provost.php">Dashboard</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Notices</li>
                            </ol>
                        </nav>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
                        <i class="fas fa-plus me-2"></i>New Notice
                    </button>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="notice-search">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search notices..." id="noticeSearch">
                </div>
                <div class="notice-filters">
                    <button class="filter-btn active" data-filter="all">All Notices</button>
                    <button class="filter-btn" data-filter="urgent">Urgent</button>
                    <button class="filter-btn" data-filter="important">Important</button>
                    <button class="filter-btn" data-filter="normal">Normal</button>
                    <button class="filter-btn" data-filter="new">New (24h)</button>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <div>
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div>
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <?php if (empty($notices)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-clipboard"></i>
                        </div>
                        <h4>No Notices Yet</h4>
                        <p class="text-muted">You haven't created any notices yet. Get started by creating your first notice.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
                            <i class="fas fa-plus me-2"></i>Create Notice
                        </button>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($notices as $noticeItem): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card notice-card notice-<?php echo $noticeItem['importance']; ?> h-100" data-importance="<?php echo $noticeItem['importance']; ?>" data-created="<?php echo $noticeItem['created_at']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge badge-<?php echo $noticeItem['importance']; ?>">
                                                    <?php echo ucfirst($noticeItem['importance']); ?>
                                                </span>
                                                <?php if (strtotime($noticeItem['created_at']) > strtotime('-24 hours')): ?>
                                                    <span class="badge bg-success">New</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-link text-dark" type="button" id="noticeDropdown<?php echo $noticeItem['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="noticeDropdown<?php echo $noticeItem['id']; ?>">
                                                    <li>
                                                        <button class="dropdown-item" onclick="viewNotice(<?php echo $noticeItem['id']; ?>)">
                                                            <i class="fas fa-eye me-2"></i>View
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item" onclick="editNotice(<?php echo $noticeItem['id']; ?>)">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </button>
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger" onclick="deleteNotice(<?php echo $noticeItem['id']; ?>)">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <h5 class="card-title mb-2"><?php echo htmlspecialchars($noticeItem['title']); ?></h5>
                                        
                                        <p class="card-text text-muted small mb-3">
                                            <?php echo substr(strip_tags($noticeItem['content']), 0, 150); ?>
                                            <?php if (strlen(strip_tags($noticeItem['content'])) > 150): ?>...<a href="#" onclick="viewNotice(<?php echo $noticeItem['id']; ?>)" class="text-primary">Read more</a><?php endif; ?>
                                        </p>

                                        <?php if (!empty($noticeItem['attachments'])): ?>
                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                <?php foreach ($noticeItem['attachments'] as $attachment): ?>
                                                    <span class="attachment-badge">
                                                        <i class="fas <?php echo $attachment['file_type'] === 'pdf' ? 'fa-file-pdf' : 'fa-image'; ?>"></i>
                                                        <?php echo htmlspecialchars($attachment['file_name']); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="date-badge">
                                                    <i class="far fa-calendar-alt"></i>
                                                    <?php echo date('M d, Y', strtotime($noticeItem['start_date'])); ?>
                                                    <?php if ($noticeItem['end_date']): ?>
                                                        - <?php echo date('M d, Y', strtotime($noticeItem['end_date'])); ?>
                                                    <?php endif; ?>
                                                </span>
                                                <span class="text-muted small">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php echo date('g:i A', strtotime($noticeItem['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div>
                                                <?php if (!empty($noticeItem['attachments'])): ?>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-paperclip me-1"></i><?php echo count($noticeItem['attachments']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                            <?php for ($i = 1; $i <= ceil(count($notices) / $perPage); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= ceil(count($notices) / $perPage) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Notice Modal -->
    <div class="modal fade" id="createNoticeModal" tabindex="-1" aria-labelledby="createNoticeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="createNoticeModalLabel">Create New Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-4">
                            <label for="title" class="form-label">Notice Title</label>
                            <input type="text" class="form-control form-control-lg" id="title" name="title" required maxlength="200" placeholder="Enter notice title">
                            <div class="invalid-feedback">Please provide a title for the notice.</div>
                        </div>

                        <div class="mb-4">
                            <label for="content" class="form-label">Notice Content</label>
                            <div id="editor-container"></div>
                            <textarea class="form-control d-none" id="content" name="content" rows="6" required></textarea>
                            <div class="invalid-feedback">Please provide content for the notice.</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="importance" class="form-label">Priority Level</label>
                                <select class="form-select" id="importance" name="importance" required>
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <div class="invalid-feedback">Please select a priority level.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required
                                       min="<?php echo date('Y-m-d'); ?>">
                                <div class="invalid-feedback">Please select a valid start date.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="attachments" class="form-label">Attachments</label>
                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple 
                                   accept=".pdf,.jpg,.jpeg,.png,.gif" onchange="previewFiles(this)">
                            <div class="form-text text-muted">Supported formats: PDF, JPG, PNG, GIF (Max 5MB each)</div>
                            
                            <div id="filePreviewContainer" class="file-preview mt-3"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Publish Notice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Notice Modal -->
    <div class="modal fade" id="editNoticeModal" tabindex="-1" aria-labelledby="editNoticeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="editNoticeModalLabel">Edit Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="notice_id" id="editNoticeId">
                        
                        <div class="mb-4">
                            <label for="editTitle" class="form-label">Notice Title</label>
                            <input type="text" class="form-control form-control-lg" id="editTitle" name="title" required maxlength="200">
                            <div class="invalid-feedback">Please provide a title for the notice.</div>
                        </div>

                        <div class="mb-4">
                            <label for="editContent" class="form-label">Notice Content</label>
                            <div id="editEditorContainer"></div>
                            <textarea class="form-control d-none" id="editContent" name="content" rows="6" required></textarea>
                            <div class="invalid-feedback">Please provide content for the notice.</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="editImportance" class="form-label">Priority Level</label>
                                <select class="form-select" id="editImportance" name="importance" required>
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                <div class="invalid-feedback">Please select a priority level.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="editStartDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="editStartDate" name="start_date" required>
                                <div class="invalid-feedback">Please select a valid start date.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="editEndDate" class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" id="editEndDate" name="end_date">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Existing Attachments</label>
                            <div id="existingAttachments" class="d-flex flex-wrap"></div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notice Detail Modal -->
    <div class="modal fade" id="noticeDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title" id="noticeDetailTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="noticeDetailContent">
                    <!-- Content loaded dynamically -->
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="floating-btn btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
        <i class="fas fa-plus"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>
    <script>
    // Initialize Quill editor for create modal
    const quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'image'],
                ['clean']
            ]
        },
        placeholder: 'Write the notice content here...'
    });
    
    // Initialize Quill editor for edit modal
    const editQuill = new Quill('#editEditorContainer', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'image'],
                ['clean']
            ]
        }
    });
    
    // Sync Quill content with textarea for form submission
    quill.on('text-change', function() {
        document.getElementById('content').value = quill.root.innerHTML;
    });
    
    editQuill.on('text-change', function() {
        document.getElementById('editContent').value = editQuill.root.innerHTML;
    });
    
    // View notice details
    function viewNotice(noticeId) {
        fetch(`/HMS/api/notice.php?id=${noticeId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('noticeDetailTitle').textContent = data.title;
                
                let attachmentsHtml = '';
                if (data.attachments && data.attachments.length > 0) {
                    attachmentsHtml = `
                        <div class="mt-4">
                            <h6>Attachments</h6>
                            <div class="d-flex flex-wrap gap-2">
                                ${data.attachments.map(att => `
                                    <a href="${att.file_path}" 
                                       target="_blank" 
                                       class="attachment-badge"
                                       title="${att.file_name}">
                                        <i class="fas fa-${att.file_type === 'pdf' ? 'file-pdf' : 'image'}"></i>
                                        ${att.file_name}
                                    </a>
                                `).join('')}
                            </div>
                        </div>
                    `;
                }
                
                const content = `
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-${data.importance === 'urgent' ? 'danger' : 
                                             (data.importance === 'important' ? 'warning' : 'info')}">
                            ${data.importance.charAt(0).toUpperCase() + data.importance.slice(1)}
                        </span>
                        <span class="text-muted small">
                            Posted by: ${data.posted_by_name} on ${new Date(data.created_at).toLocaleDateString()}
                        </span>
                    </div>
                    
                    <div class="notice-content border-top pt-3 mb-3">
                        ${data.content}
                    </div>
                    
                    <div class="d-flex align-items-center text-muted small">
                        <i class="far fa-calendar-alt me-2"></i>
                        <span>
                            ${new Date(data.start_date).toLocaleDateString()}
                            ${data.end_date ? ` - ${new Date(data.end_date).toLocaleDateString()}` : ''}
                        </span>
                    </div>
                    
                    ${attachmentsHtml}
                `;
                
                document.getElementById('noticeDetailContent').innerHTML = content;
                new bootstrap.Modal(document.getElementById('noticeDetailModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load notice details');
            });
    }
    
    // Edit notice
    function editNotice(noticeId) {
        fetch(`/HMS/api/notice.php?id=${noticeId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('editNoticeId').value = data.id;
                document.getElementById('editTitle').value = data.title;
                editQuill.root.innerHTML = data.content;
                document.getElementById('editContent').value = data.content;
                document.getElementById('editImportance').value = data.importance;
                document.getElementById('editStartDate').value = data.start_date.split(' ')[0];
                document.getElementById('editEndDate').value = data.end_date ? data.end_date.split(' ')[0] : '';
                
                // Set min date for end date
                document.getElementById('editEndDate').min = data.start_date.split(' ')[0];
                
                // Display existing attachments
                const attachmentsContainer = document.getElementById('existingAttachments');
                attachmentsContainer.innerHTML = '';
                
                if (data.attachments && data.attachments.length > 0) {
                    data.attachments.forEach(att => {
                        const attachmentElement = document.createElement('div');
                        attachmentElement.className = 'me-3 mb-3';
                        
                        if (att.file_type === 'image') {
                            attachmentElement.innerHTML = `
                                <img src="${att.file_path}" class="attachment-thumbnail" 
                                     onclick="openImagePreview('${att.file_path}')"
                                     title="${att.file_name}">
                            `;
                        } else {
                            attachmentElement.innerHTML = `
                                <a href="${att.file_path}" target="_blank" class="text-decoration-none">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="fas fa-file-pdf fa-3x text-danger mb-1"></i>
                                        <span class="small text-truncate" style="max-width: 100px;">${att.file_name}</span>
                                    </div>
                                </a>
                            `;
                        }
                        
                        attachmentsContainer.appendChild(attachmentElement);
                    });
                } else {
                    attachmentsContainer.innerHTML = '<p class="text-muted">No attachments</p>';
                }
                
                new bootstrap.Modal(document.getElementById('editNoticeModal')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load notice for editing');
            });
    }
    
    // Open image preview in new window
    function openImagePreview(imageUrl) {
        const previewWindow = window.open('', '_blank');
        previewWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Image Preview</title>
                <style>
                    body { margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f8f9fa; }
                    img { max-width: 100%; max-height: 90vh; object-fit: contain; }
                </style>
            </head>
            <body>
                <img src="${imageUrl}">
            </body>
            </html>
        `);
    }
    
    // Preview selected files
    function previewFiles(input) {
        const previewContainer = document.getElementById('filePreviewContainer');
        previewContainer.innerHTML = '';
        
        if (input.files.length > 0) {
            for (let i = 0; i < input.files.length; i++) {
                const file = input.files[i];
                const fileType = file.name.split('.').pop().toLowerCase();
                
                const fileItem = document.createElement('div');
                fileItem.className = 'file-preview-item';
                
                let iconClass = 'fa-file';
                if (fileType === 'pdf') iconClass = 'fa-file-pdf';
                else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) iconClass = 'fa-file-image';
                
                fileItem.innerHTML = `
                    <i class="fas ${iconClass}"></i>
                    ${file.name} (${(file.size / 1024).toFixed(1)}KB)
                `;
                
                previewContainer.appendChild(fileItem);
            }
        }
    }
    
    // Form validation
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Date validation
    document.getElementById('start_date').addEventListener('change', function() {
        document.getElementById('end_date').min = this.value;
    });
    
    document.getElementById('editStartDate').addEventListener('change', function() {
        document.getElementById('editEndDate').min = this.value;
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Notice filtering functionality
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                // Update active state
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                const filter = button.dataset.filter;
                const notices = document.querySelectorAll('.notice-card');
                
                notices.forEach(notice => {
                    const importance = notice.dataset.importance;
                    const createdDate = new Date(notice.dataset.created);
                    const isNew = (new Date() - createdDate) < (24 * 60 * 60 * 1000);
                    
                    if (filter === 'all' ||
                        (filter === importance) ||
                        (filter === 'new' && isNew)) {
                        notice.closest('.col-md-6').style.display = '';
                    } else {
                        notice.closest('.col-md-6').style.display = 'none';
                    }
                });
            });
        });

        // Notice search functionality
        const searchInput = document.getElementById('noticeSearch');
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const notices = document.querySelectorAll('.notice-card');
            
            notices.forEach(notice => {
                const title = notice.querySelector('.card-title').textContent.toLowerCase();
                const content = notice.querySelector('.card-text').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || content.includes(searchTerm)) {
                    notice.closest('.col-md-6').style.display = '';
                } else {
                    notice.closest('.col-md-6').style.display = 'none';
                }
            });
        });

        // Delete notice confirmation
        function deleteNotice(noticeId) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f72585',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = window.location.href;
                    
                    const actionInput = document.createElement('input');
                    actionInput.type = 'hidden';
                    actionInput.name = 'action';
                    actionInput.value = 'delete';
                    
                    const noticeIdInput = document.createElement('input');
                    noticeIdInput.type = 'hidden';
                    noticeIdInput.name = 'notice_id';
                    noticeIdInput.value = noticeId;
                    
                    form.appendChild(actionInput);
                    form.appendChild(noticeIdInput);
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // View notice function
        function viewNotice(noticeId) {
            // Implement view functionality
            window.location.href = `/HMS/dashboard/view_notice.php?id=${noticeId}`;
        }

        // Edit notice function
        function editNotice(noticeId) {
            // Implement edit functionality
            // You can either redirect to an edit page or show a modal
            alert('Edit functionality to be implemented');
        }
    </script>
</body>
</html>