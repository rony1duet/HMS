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

// Get provost's hall_id and name
$provostSlug = Session::getSlug();
try {
    $query = "SELECT pa.hall_id, h.name as hall_name FROM provost_approvals pa 
              JOIN halls h ON pa.hall_id = h.id 
              WHERE pa.slug = :provostSlug AND pa.status = 'approved'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":provostSlug", $provostSlug, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hallId = $result ? (int)$result['hall_id'] : null;
    $hallName = $result ? $result['hall_name'] : null;

    if (!$hallId || !$hallName) {
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
$notices = $notice->getNoticesByHallId($hallId, $page, $perPage);

// Include header
require_once '../includes/header.php';
?>

<style>
    /* Inherit Bootstrap theme colors from header */
    :root {
        --primary: var(--bs-primary, #4361ee);
        --primary-light: var(--bs-primary-rgb, #e6e9ff);
        --secondary: var(--bs-secondary, #3f37c9);
        --danger: var(--bs-danger, #f72585);
        --warning: var(--bs-warning, #f8961e);
        --info: var(--bs-info, #4895ef);
        --success: var(--bs-success, #4cc9f0);
        --light: var(--bs-light, #f8f9fa);
        --dark: var(--bs-dark, #212529);
        --gray: var(--bs-gray, #6c757d);
        --white: var(--bs-white, #ffffff);
        --border-radius: var(--bs-border-radius, 8px);
        --box-shadow: var(--bs-box-shadow, 0 4px 12px rgba(0, 0, 0, 0.08));
        --transition: all 0.3s ease;
        --font-family: var(--bs-font-sans-serif, 'Inter', system-ui, -apple-system, sans-serif);
    }

    .notices-container {
        max-width: min(1200px, 100% - 2rem);
        margin: 1rem auto;
        padding: 0 1rem;
        width: 100%;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0;
        color: var(--dark);
    }

    .page-subtitle {
        color: var(--gray);
        margin: 0.25rem 0 0;
        font-size: 1rem;
    }

    .hall-badge {
        background-color: var(--primary-light);
        color: var(--primary);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .filter-container {
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        align-items: center;
        justify-content: space-between;
    }

    .filter-tabs {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .filter-tab {
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
        background: var(--light);
        color: var(--gray);
        border: 1px solid rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
    }

    .filter-tab:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .filter-tab.active {
        background: var(--primary);
        color: var(--white);
        border-color: var(--primary);
    }

    .search-box {
        min-width: 300px;
    }

    .search-box .input-group-text {
        border-radius: var(--border-radius) 0 0 var(--border-radius);
    }

    .search-box .form-control {
        border-radius: 0 var(--border-radius) var(--border-radius) 0;
    }

    .notices-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .notice-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        box-shadow: var(--box-shadow);
        border-left: 4px solid var(--info);
        transition: var(--transition);
    }

    .notice-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    .notice-card.urgent {
        border-left-color: var(--danger);
    }

    .notice-card.important {
        border-left-color: var(--warning);
    }

    .notice-card.normal {
        border-left-color: var(--info);
    }

    .notice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1rem;
    }

    .notice-title-section {
        flex: 1;
        margin-right: 1rem;
    }

    .notice-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0 0 0.5rem;
        color: var(--dark);
    }

    .notice-badges {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .notice-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .notice-badge.urgent {
        background-color: rgba(247, 37, 133, 0.1);
        color: var(--danger);
    }

    .notice-badge.important {
        background-color: rgba(248, 150, 30, 0.1);
        color: var(--warning);
    }

    .notice-badge.normal {
        background-color: rgba(72, 149, 239, 0.1);
        color: var(--info);
    }

    .notice-badge.new {
        background-color: rgba(76, 201, 240, 0.1);
        color: var(--success);
    }

    .notice-content {
        color: var(--dark);
        margin: 1rem 0;
        line-height: 1.6;
    }

    .notice-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .notice-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .notice-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--gray);
        font-size: 0.875rem;
    }

    .notice-meta-item i {
        color: var(--primary);
    }

    .notice-actions {
        display: flex;
        gap: 0.5rem;
    }

    .notice-attachments {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .attachment-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--light);
        padding: 0.5rem 0.75rem;
        border-radius: var(--border-radius);
        font-size: 0.75rem;
        color: var(--gray);
        transition: var(--transition);
    }

    .attachment-badge i {
        color: var(--primary);
    }

    .attachment-badge:hover {
        background: var(--primary-light);
        color: var(--primary);
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background: var(--light);
        border-radius: var(--border-radius);
        margin: 2rem 0;
    }

    .empty-state-icon {
        font-size: 3rem;
        color: var(--gray);
        margin-bottom: 1rem;
    }

    .empty-state h4 {
        color: var(--dark);
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: var(--gray);
        margin: 0;
    }

    @media (max-width: 991.98px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .filter-container {
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
        }

        .filter-tabs {
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.5rem;
        }

        .search-box {
            min-width: 100%;
        }

        .notice-card {
            padding: 1rem;
        }

        .notice-header {
            flex-direction: column;
            gap: 0.75rem;
        }

        .notice-actions {
            width: 100%;
            justify-content: flex-end;
        }

        .notice-footer {
            flex-direction: column;
            gap: 1rem;
        }

        .notice-meta {
            flex-direction: column;
            gap: 0.75rem;
        }

        .notice-attachments {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 0.5rem;
        }

        .view-btn {
            width: 100%;
            justify-content: center;
        }
    }

    @media (max-width: 575.98px) {
        .notices-container {
            margin: 0.5rem auto;
            padding: 0 0.5rem;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .notice-title {
            font-size: 1.1rem;
        }

        .notice-content {
            font-size: 0.9rem;
        }

        .notice-meta-item {
            font-size: 0.8rem;
        }
    }

    /* Notice View Modal Styles */
    .notice-view {
        padding: 1rem;
    }

    .notice-content {
        font-size: 1rem;
        line-height: 1.6;
        color: var(--dark);
    }

    .attachment-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: var(--dark);
        padding: 1rem;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: var(--border-radius);
        transition: var(--transition);
        gap: 0.5rem;
        min-width: 120px;
        text-align: center;
    }

    .attachment-item:hover {
        background-color: var(--primary-light);
        border-color: var(--primary);
        color: var(--primary);
        transform: translateY(-2px);
    }

    .attachment-item.pdf {
        color: var(--danger);
    }

    .attachment-item.pdf:hover {
        background-color: rgba(247, 37, 133, 0.1);
        border-color: var(--danger);
    }

    .attachment-item span {
        font-size: 0.75rem;
        max-width: 100px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .attachment-preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        cursor: pointer;
        padding: 0.5rem;
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .attachment-preview:hover {
        background-color: var(--primary-light);
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .attachment-preview img {
        border-radius: var(--border-radius);
        transition: var(--transition);
    }

    .attachment-preview:hover img {
        transform: scale(1.05);
    }

    /* Quill Editor Styles */
    .ql-editor {
        min-height: 200px;
        font-size: 1rem;
        line-height: 1.6;
    }

    .ql-toolbar {
        border-top-left-radius: var(--border-radius);
        border-top-right-radius: var(--border-radius);
        border-color: rgba(0, 0, 0, 0.1);
    }

    .ql-container {
        border-bottom-left-radius: var(--border-radius);
        border-bottom-right-radius: var(--border-radius);
        border-color: rgba(0, 0, 0, 0.1);
    }

    /* SweetAlert2 Custom Styles */
    .swal2-popup {
        border-radius: var(--border-radius);
        padding: 2rem;
    }

    .swal2-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--dark);
    }

    .swal2-html-container {
        font-size: 1rem;
        color: var(--gray);
    }

    .swal2-confirm {
        background-color: var(--primary) !important;
        border-radius: var(--border-radius) !important;
    }

    .swal2-cancel {
        background-color: var(--gray) !important;
        border-radius: var(--border-radius) !important;
    }
</style>

<div class="notices-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Notice Management</h1>
            <p class="page-subtitle">Manage and publish notices for your hall</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
                <i class="fas fa-plus me-2"></i>New Notice
            </button>
        </div>
    </div>

    <div class="filter-container">
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?php echo !isset($_GET['filter']) || $_GET['filter'] === 'all' ? 'active' : ''; ?>">All Notices</a>
            <a href="?filter=urgent" class="filter-tab <?php echo isset($_GET['filter']) && $_GET['filter'] === 'urgent' ? 'active' : ''; ?>">Urgent</a>
            <a href="?filter=important" class="filter-tab <?php echo isset($_GET['filter']) && $_GET['filter'] === 'important' ? 'active' : ''; ?>">Important</a>
            <a href="?filter=normal" class="filter-tab <?php echo isset($_GET['filter']) && $_GET['filter'] === 'normal' ? 'active' : ''; ?>">Normal</a>
            <a href="?filter=new" class="filter-tab <?php echo isset($_GET['filter']) && $_GET['filter'] === 'new' ? 'active' : ''; ?>">New</a>
        </div>
        <div class="search-box">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-start-0" placeholder="Search notices..." id="noticeSearch">
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
                </div>
            <?php else: ?>
                <div class="notices-list">
                    <?php foreach ($notices as $noticeItem): ?>
                        <?php
                        $isNew = (strtotime($noticeItem['created_at']) >= strtotime('-24 hours'));
                        $importanceClass = strtolower($noticeItem['importance']);
                        $hasAttachments = !empty($noticeItem['attachments']);
                        ?>
                        <div class="notice-card <?php echo $importanceClass; ?>" data-id="<?php echo $noticeItem['id']; ?>">
                            <div class="notice-header">
                                <div class="notice-title-section">
                                    <h3 class="notice-title"><?php echo htmlspecialchars($noticeItem['title']); ?></h3>
                                    <div class="notice-badges">
                                        <span class="notice-badge <?php echo $importanceClass; ?>">
                                            <?php echo ucfirst($importanceClass); ?>
                                        </span>
                                        <?php if ($isNew): ?>
                                            <span class="notice-badge new">New</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="notice-actions">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="editNotice(<?php echo $noticeItem['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteNotice(<?php echo $noticeItem['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="notice-content">
                                <?php echo htmlspecialchars(substr(strip_tags($noticeItem['content']), 0, 200)) . (strlen(strip_tags($noticeItem['content'])) > 200 ? '...' : ''); ?>
                            </div>

                            <div class="notice-footer">
                                <div class="notice-meta">
                                    <span class="notice-meta-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo date('M j, Y', strtotime($noticeItem['start_date'])); ?>
                                        <?php if ($noticeItem['end_date']): ?>
                                            - <?php echo date('M j, Y', strtotime($noticeItem['end_date'])); ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="notice-meta-item">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('g:i A', strtotime($noticeItem['created_at'])); ?>
                                    </span>
                                    <?php if ($hasAttachments): ?>
                                        <span class="notice-meta-item">
                                            <i class="fas fa-paperclip"></i>
                                            <?php echo count($noticeItem['attachments']) . ' attachment' . (count($noticeItem['attachments']) > 1 ? 's' : ''); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <button class="btn btn-primary btn-sm view-btn" onclick="viewNotice(<?php echo $noticeItem['id']; ?>)">
                                    View Details
                                    <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                            </div>

                            <?php if ($hasAttachments): ?>
                                <div class="notice-attachments">
                                    <?php foreach ($noticeItem['attachments'] as $attachment): ?>
                                        <span class="attachment-badge">
                                            <i class="fas <?php echo $attachment['file_type'] === 'pdf' ? 'fa-file-pdf' : 'fa-image'; ?>"></i>
                                            <?php echo htmlspecialchars($attachment['file_name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Notice Modal -->
    <div class="modal fade" id="viewNoticeModal" tabindex="-1" aria-labelledby="viewNoticeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewNoticeModalLabel">Notice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewNoticeContent">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Notice Modal -->
    <div class="modal fade" id="editNoticeModal" tabindex="-1" aria-labelledby="editNoticeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editNoticeModalLabel">Edit Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editNoticeForm" method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="notice_id" id="editNoticeId">

                        <div class="mb-3">
                            <label for="editTitle" class="form-label">Title</label>
                            <input type="text" class="form-control" id="editTitle" name="title" required>
                            <div class="invalid-feedback">Please provide a title.</div>
                        </div>

                        <div class="mb-3">
                            <label for="editContent" class="form-label">Content</label>
                            <div id="editEditorContainer"></div>
                            <textarea id="editContent" name="content" style="display: none;"></textarea>
                            <div class="invalid-feedback">Please provide content.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="editImportance" class="form-label">Importance</label>
                                <select class="form-select" id="editImportance" name="importance" required>
                                    <option value="normal">Normal</option>
                                    <option value="important">Important</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="editStartDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="editStartDate" name="start_date" required>
                                <div class="invalid-feedback">Please select a start date.</div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="editEndDate" class="form-label">End Date (Optional)</label>
                                <input type="date" class="form-control" id="editEndDate" name="end_date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Attachments</label>
                            <div id="existingAttachments" class="d-flex flex-wrap gap-3"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
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
            <form id="editNoticeForm" class="needs-validation" novalidate enctype="multipart/form-data">
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
                        <div id="existingAttachments" class="d-flex flex-wrap gap-3"></div>
                    </div>

                    <div class="mb-4">
                        <label for="editAttachments" class="form-label">Add New Attachments</label>
                        <input type="file" class="form-control" id="editAttachments" name="attachments[]" multiple
                            accept=".pdf,.jpg,.jpeg,.png,.gif" onchange="previewFiles(this)">
                        <div class="form-text">Supported formats: PDF, JPG, PNG, GIF (Max 5MB each)</div>
                        <div id="editFilePreviewContainer" class="file-preview mt-3"></div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
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
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
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
        border: 1px solid rgba(0, 0, 0, 0.05);
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

        0%,
        20%,
        50%,
        80%,
        100% {
            transform: translateY(0);
        }

        40% {
            transform: translateY(-20px);
        }

        60% {
            transform: translateY(-10px);
        }
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
        border: 1px solid rgba(0, 0, 0, 0.05);
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
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: var(--border-radius);
        padding: 10px 15px;
        display: flex;
        align-items: center;
        font-size: 0.8rem;
        transition: var(--transition);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    .file-preview-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
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
        background-color: rgba(0, 0, 0, 0.02);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
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
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .attachment-thumbnail:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .attachment-preview {
        max-width: 100%;
        max-height: 400px;
        margin-bottom: 15px;
        border-radius: var(--border-radius);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
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
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1.5rem;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
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
        border: 1px solid rgba(0, 0, 0, 0.05);
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

<script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Quill editors
        const quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{
                        'header': [1, 2, 3, false]
                    }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{
                        'color': []
                    }, {
                        'background': []
                    }],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            placeholder: 'Write the notice content here...'
        });

        // Initialize Quill editor for edit modal
        const editQuill = new Quill('#editContent', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{
                        'header': [1, 2, 3, false]
                    }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{
                        'color': []
                    }, {
                        'background': []
                    }],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        // Sync Quill content with hidden form fields
        if (quill) {
            quill.on('text-change', function() {
                document.getElementById('content').value = quill.root.innerHTML;
            });
        }

        if (editQuill) {
            editQuill.on('text-change', function() {
                document.getElementById('editContentInput').value = editQuill.root.innerHTML;
            });
        }

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
        if (searchInput) {
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

        // Date validation for create form
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        if (startDateInput && endDateInput) {
            startDateInput.addEventListener('change', function() {
                endDateInput.min = this.value;
            });
        }

        // Date validation for edit form
        const editStartDateInput = document.getElementById('editStartDate');
        const editEndDateInput = document.getElementById('editEndDate');
        if (editStartDateInput && editEndDateInput) {
            editStartDateInput.addEventListener('change', function() {
                editEndDateInput.min = this.value;
            });
        }
    });

    /**
     * View notice details
     * @param {number} noticeId - The ID of the notice to view
     */
    function viewNotice(noticeId) {
        fetch(`/HMS/api/notices.php?id=${noticeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Format importance badge class
                const importanceClass = data.importance === 'urgent' ? 'danger' :
                    (data.importance === 'important' ? 'warning' : 'info');

                // Format attachments HTML if any
                let attachmentsHtml = '';
                if (data.attachments && data.attachments.length > 0) {
                    attachmentsHtml = `
                    <div class="notice-attachments">
                        <h6 class="mb-3">Attachments</h6>
                        <div class="d-flex flex-wrap gap-3">
                            ${data.attachments.map(att => `
                                <a href="${att.file_path}" 
                                   class="attachment-item ${att.file_type === 'pdf' ? 'pdf' : 'image'}" 
                                   target="_blank"
                                   ${att.file_type === 'image' ? `onclick="previewImage('${att.file_path}'); return false;"` : ''}>
                                    <i class="fas fa-${att.file_type === 'pdf' ? 'file-pdf' : 'image'} fa-2x"></i>
                                    <span>${att.file_name}</span>
                                </a>
                            `).join('')}
                        </div>
                    </div>`;
                }

                // Set modal title and build content
                document.getElementById('noticeDetailTitle').textContent = data.title;

                const content = `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-${importanceClass}">
                        ${data.importance.charAt(0).toUpperCase() + data.importance.slice(1)}
                    </span>
                    <span class="text-muted small">
                        Posted by: ${data.posted_by_name || data.posted_by} on ${new Date(data.created_at).toLocaleDateString()}
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

                // Show the modal
                const modal = new bootstrap.Modal(document.getElementById('noticeDetailModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load notice details. Please try again.',
                });
            });
    }

    /**
     * Edit notice
     * @param {number} noticeId - The ID of the notice to edit
     */
    function editNotice(noticeId) {
        fetch(`/HMS/api/notices.php?id=${noticeId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch notice data');
                }
                return response.json();
            })
            .then(data => {
                // Handle response format variations (direct or with .success/.data)
                const noticeData = data.success && data.data ? data.data : data;

                // Populate the form fields
                document.getElementById('editNoticeId').value = noticeData.id;
                document.getElementById('editTitle').value = noticeData.title;

                // Populate Quill editor
                const editQuill = Quill.find(document.getElementById('editContent'));
                editQuill.root.innerHTML = noticeData.content;
                document.getElementById('editContentInput').value = noticeData.content;

                // Other form fields
                document.getElementById('editImportance').value = noticeData.importance;
                document.getElementById('editStartDate').value = noticeData.start_date.split(' ')[0];
                document.getElementById('editEndDate').value = noticeData.end_date ? noticeData.end_date.split(' ')[0] : '';

                // Set min date for end date
                document.getElementById('editEndDate').min = noticeData.start_date.split(' ')[0];

                // Display existing attachments
                displayExistingAttachments(noticeData.attachments);

                // Show the modal
                const editModal = new bootstrap.Modal(document.getElementById('editNoticeModal'));
                editModal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to load notice data',
                    icon: 'error'
                });
            });
    }

    /**
     * Display existing attachments in the edit modal
     * @param {Array} attachments - Array of attachment objects
     */
    function displayExistingAttachments(attachments) {
        const attachmentsContainer = document.getElementById('existingAttachments');
        attachmentsContainer.innerHTML = '';

        if (attachments && attachments.length > 0) {
            const attachmentsList = document.createElement('div');
            attachmentsList.className = 'list-group mb-3';

            attachments.forEach(attachment => {
                const item = document.createElement('div');
                item.className = 'list-group-item d-flex justify-content-between align-items-center';
                item.innerHTML = `
                <span>
                    <i class="fas fa-${attachment.file_type === 'pdf' ? 'file-pdf' : 'file-image'} me-2"></i>
                    ${attachment.file_name}
                </span>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteAttachment(${attachment.id})">
                    <i class="fas fa-trash"></i>
                </button>
            `;
                attachmentsList.appendChild(item);
            });

            attachmentsContainer.appendChild(attachmentsList);
        } else {
            attachmentsContainer.innerHTML = '<p class="text-muted">No attachments</p>';
        }
    }

    /**
     * Update notice
     */
    function updateNotice() {
        const form = document.getElementById('editNoticeForm');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        // Get form data
        const formData = new FormData(form);

        // Make sure to get content from Quill editor
        const editQuill = Quill.find(document.getElementById('editContent'));
        formData.set('content', editQuill.root.innerHTML);

        // Send update request
        fetch('/HMS/api/notices/update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success',
                        text: 'Notice updated successfully',
                        icon: 'success'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to update notice');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'Failed to update notice',
                    icon: 'error'
                });
            });
    }

    /**
     * Delete attachment
     * @param {number} attachmentId - The ID of the attachment to delete
     */
    function deleteAttachment(attachmentId) {
        Swal.fire({
            title: 'Delete Attachment',
            text: 'Are you sure you want to delete this attachment?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f72585',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/HMS/api/notices/attachments/${attachmentId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Refresh the attachments list
                            editNotice(document.getElementById('editNoticeId').value);
                        } else {
                            throw new Error(data.message || 'Failed to delete attachment');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error',
                            text: error.message || 'Failed to delete attachment',
                            icon: 'error'
                        });
                    });
            }
        });
    }

    /**
     * Delete notice
     * @param {number} noticeId - The ID of the notice to delete
     */
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

    /**
     * Preview files that are selected for upload
     * @param {HTMLInputElement} input - The file input element
     */
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

    /**
     * Open image preview in a modal
     * @param {string} imagePath - The path to the image
     */
    function previewImage(imagePath) {
        Swal.fire({
            imageUrl: imagePath,
            imageAlt: 'Notice Image',
            width: '80%',
            showConfirmButton: false,
            showCloseButton: true,
            padding: '1rem',
            customClass: {
                image: 'img-fluid'
            }
        });
    }

    /**
     * Open image preview in a new window
     * @param {string} imageUrl - The URL of the image
     */
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
</script>

<?php require_once '../includes/footer.php'; ?>