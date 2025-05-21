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

            $attachment = [];
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['attachment']['name'];
                $fileTmpName = $_FILES['attachment']['tmp_name'];
                $fileType = $_FILES['attachment']['type'];
                $fileSize = $_FILES['attachment']['size'];

                // Validate file type
                $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    $_SESSION['error'] = 'Invalid file type. Allowed types are PDF, JPEG, PNG, and GIF.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                // Validate file size (max 5MB)
                if ($fileSize > 5 * 1024 * 1024) {
                    $_SESSION['error'] = 'File size exceeds maximum limit of 5MB.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                // Generate unique filename
                $uniqueName = uniqid() . '_' . $fileName;
                $uploadPath = $uploadDir . $uniqueName;

                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    $attachment = [
                        'file_name' => $fileName,
                        'file_path' => $uploadPath,
                        'file_type' => $fileType,
                        'file_size' => $fileSize
                    ];
                } else {
                    $_SESSION['error'] = 'Failed to upload file.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
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

            if ($notice->createNotice($noticeData, $attachment)) {
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
            $uploadDir = '../uploads/notices/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Verify the notice belongs to this provost's hall
            try {
                $existingNotice = $notice->getNoticeById($noticeId);
                if (!$existingNotice || $existingNotice['hall_id'] != $hallId) {
                    $_SESSION['error'] = 'Notice not found or unauthorized to edit.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                // Validate required fields
                if (empty($_POST['title']) || empty($_POST['content']) || empty($_POST['start_date'])) {
                    $_SESSION['error'] = 'Title, content and start date are required fields.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                // Validate dates
                $startDate = strtotime($_POST['start_date']);
                $endDate = !empty($_POST['end_date']) ? strtotime($_POST['end_date']) : null;

                if ($startDate === false) {
                    $_SESSION['error'] = 'Invalid start date format.';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                if ($endDate !== null) {
                    if ($endDate === false) {
                        $_SESSION['error'] = 'Invalid end date format.';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                    if ($endDate < $startDate) {
                        $_SESSION['error'] = 'End date cannot be earlier than start date.';
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    }
                }

                // Prepare notice data with proper sanitization
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
                    $_SESSION['error'] = 'Failed to update notice. Please try again.';
                }
            } catch (Exception $e) {
                error_log('Error updating notice: ' . $e->getMessage());
                $_SESSION['error'] = 'An error occurred while updating the notice.';
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

// Get existing notices with pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 5;
$currentFilter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$notices = $notice->getNoticesByHall($hallName, $page, $perPage, $currentFilter);
$totalNotices = $notice->countNoticesByHall($hallName, $currentFilter);

require_once '../includes/header.php';
?>

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
            <a href="?filter=all" class="filter-tab <?php echo $currentFilter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=urgent" class="filter-tab <?php echo $currentFilter === 'urgent' ? 'active' : ''; ?>">Urgent</a>
            <a href="?filter=important" class="filter-tab <?php echo $currentFilter === 'important' ? 'active' : ''; ?>">Important</a>
            <a href="?filter=normal" class="filter-tab <?php echo $currentFilter === 'normal' ? 'active' : ''; ?>">Normal</a>
            <a href="?filter=new" class="filter-tab <?php echo $currentFilter === 'new' ? 'active' : ''; ?>">New</a>
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
                        $hasAttachment = !empty($noticeItem['attachment']);
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
                                    <?php if ($hasAttachment): ?>
                                        <span class="notice-meta-item">
                                            <i class="fas fa-paperclip"></i>
                                            <?php echo count($noticeItem['attachment']) . ' attachment' . (count($noticeItem['attachment']) > 1 ? 's' : ''); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <button class="btn btn-primary btn-sm view-btn" onclick="viewNotice(<?php echo $noticeItem['id']; ?>)">
                                    View Details
                                    <i class="fas fa-arrow-right ms-1"></i>
                                </button>
                            </div>

                            <?php if ($hasAttachment): ?>
                                <div class="notice-attachment">
                                    <?php foreach ($noticeItem['attachment'] as $attachment): ?>
                                        <span class="attachment-badge">
                                            <i class="fas <?php echo $attachment['file_type'] === 'application/pdf' ? 'fa-file-pdf' : 'fa-image'; ?>"></i>
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

    <!-- Pagination -->
    <?php if ($totalNotices > $perPage): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?filter=<?php echo $currentFilter; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                </li>

                <?php
                $totalPages = ceil($totalNotices / $perPage);
                $maxPagesToShow = 5;
                $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                $endPage = min($startPage + $maxPagesToShow - 1, $totalPages);

                // Always show first page
                if ($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?filter=' . $currentFilter . '&page=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }

                // Show numbered pages
                for ($i = $startPage; $i <= $endPage; $i++) {
                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">';
                    echo '<a class="page-link" href="?filter=' . $currentFilter . '&page=' . $i . '">' . $i . '</a>';
                    echo '</li>';
                }

                // Always show last page
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?filter=' . $currentFilter . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>

                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?filter=<?php echo $currentFilter; ?>&page=<?php echo $page + 1; ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
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
                        <label for="attachment" class="form-label">Attachment</label>
                        <input type="file" class="form-control" id="attachment" name="attachment"
                            accept=".pdf,.jpg,.jpeg,.png,.gif" onchange="validateFileUpload(this)">
                        <div class="form-text text-muted">Supported formats: PDF, JPG, PNG, GIF (Max 5MB)</div>
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
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
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
                        <label for="editAttachment" class="form-label">Attachment</label>
                        <input type="file" class="form-control" id="editAttachment" name="attachment"
                            accept=".pdf,.jpg,.jpeg,.png,.gif" onchange="validateFileUpload(this)">
                        <div class="form-text">Supported formats: PDF, JPG, PNG, GIF (Max 5MB). Only one file allowed.</div>
                        <div id="editFilePreviewContainer" class="file-preview mt-3"></div>
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

<?php require_once '../assets/css/noticeStyle.php'; ?>
<style>
    /* Add to your noticeStyle.php or include in the page */

    .file-preview {
        border-radius: 6px;
        border: 1px dashed #dee2e6;
        padding: 10px;
        background-color: #f8f9fa;
    }

    .file-preview:empty {
        display: none;
    }

    .file-preview-item {
        background-color: white;
        border-radius: 4px;
        padding: 8px 12px;
        border: 1px solid #e9ecef;
        font-size: 0.9rem;
        color: #495057;
        transition: all 0.2s ease;
    }

    .file-preview-item:hover {
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
    }

    .file-preview-item i {
        margin-right: 8px;
        color: #6c757d;
    }

    .file-preview-item i.fa-file-pdf {
        color: #dc3545;
    }

    .file-preview-item i.fa-file-image {
        color: #198754;
    }

    .file-preview-item .btn-outline-danger {
        padding: 0.15rem 0.4rem;
        font-size: 0.75rem;
        border-radius: 3px;
    }

    .file-preview-item .btn-outline-danger:hover {
        background-color: #f8d7da;
        color: #dc3545;
        border-color: #f5c2c7;
    }
</style>

<script>
    let quill, editQuill;

    /**
     * Validate file upload to ensure only one file is allowed
     * @param {HTMLInputElement} input - The file input element
     */
    function validateFileUpload(input) {
        // Check if there are existing attachments
        const filePreviewContainer = input.id === 'editAttachment' ?
            document.getElementById('editFilePreviewContainer') :
            document.getElementById('filePreviewContainer');

        // If there are existing attachments and a new file is selected, show warning
        if (filePreviewContainer.children.length > 0 && input.files.length > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'Uploading a new file will replace the existing attachment. Only one file is allowed.',
                showCancelButton: true,
                confirmButtonText: 'Continue',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) {
                    // Reset the file input if user cancels
                    input.value = '';
                }
            });
        }

        // Validate file size
        if (input.files.length > 0) {
            const file = input.files[0];
            if (file.size > 5 * 1024 * 1024) { // 5MB limit
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'The file size exceeds the 5MB limit.'
                });
                input.value = '';
                return;
            }

            // Validate file type
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Only PDF, JPEG, PNG, and GIF files are allowed.'
                });
                input.value = '';
                return;
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Quill editor for create modal
        quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{
                        'font': []
                    }],
                    [{
                        'header': [1, 2, 3, 4, 5, 6, false]
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
                    [{
                        'indent': '-1'
                    }, {
                        'indent': '+1'
                    }],
                    [{
                        'align': []
                    }],
                    ['link'],
                    ['clean']
                ]
            },
            placeholder: 'Write the notice content here...'
        });

        // Sync Quill content with hidden textarea for create modal
        quill.on('text-change', function() {
            document.getElementById('content').value = quill.root.innerHTML;
        });

        // Initialize Quill editor for edit modal
        editQuill = new Quill('#editEditorContainer', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{
                        'font': []
                    }],
                    [{
                        'header': [1, 2, 3, 4, 5, 6, false]
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
                    [{
                        'indent': '-1'
                    }, {
                        'indent': '+1'
                    }],
                    [{
                        'align': []
                    }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        // Sync Quill content with hidden textarea for edit modal
        editQuill.on('text-change', function() {
            document.getElementById('editContent').value = editQuill.root.innerHTML;
        });

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
                        notice.style.display = '';
                    } else {
                        notice.style.display = 'none';
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
                    const title = notice.querySelector('.notice-title').textContent.toLowerCase();
                    const content = notice.querySelector('.notice-content').textContent.toLowerCase();

                    if (title.includes(searchTerm) || content.includes(searchTerm)) {
                        notice.style.display = '';
                    } else {
                        notice.style.display = 'none';
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

                // Format attachment HTML if any
                let attachmentHtml = '';
                if (data.attachment && data.attachment.length > 0) {
                    attachmentHtml = `
                    <div class="notice-attachment">
                        <h6 class="mb-3">Attachment</h6>
                        <div class="d-flex flex-wrap gap-3">
                            ${data.attachment.map(att => `
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
                
                ${attachmentHtml}
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
            .then(response => response.json())
            .then(data => {
                // Populate form fields
                document.getElementById('editNoticeId').value = data.id;
                document.getElementById('editTitle').value = data.title;
                editQuill.root.innerHTML = data.content;
                document.getElementById('editContent').value = data.content;
                document.getElementById('editImportance').value = data.importance;
                document.getElementById('editStartDate').value = data.start_date.split(' ')[0];
                document.getElementById('editEndDate').value = data.end_date ? data.end_date.split(' ')[0] : '';
                document.getElementById('editEndDate').min = data.start_date.split(' ')[0];

                // Clear any previous file previews
                const filePreviewContainer = document.getElementById('editFilePreviewContainer');
                filePreviewContainer.innerHTML = '';

                // Handle attachments
                if (data.attachments && data.attachments.length > 0) {
                    data.attachments.forEach(att => {
                        addFilePreview(att, filePreviewContainer, noticeId);
                    });
                }

                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editNoticeModal'));
                editModal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: ' + error.message,
                });
            });
    }


    // Update the form submission to use FormData
    document.querySelector('#editNoticeModal form').addEventListener('submit', function(e) {
        e.preventDefault();

        // Get content from Quill editor and update hidden textarea
        const editorContent = editQuill.root.innerHTML;
        document.getElementById('editContent').value = editorContent;

        // Create FormData object from form
        const formData = new FormData(this);
        formData.append('action', 'update');

        // Show loading indicator
        Swal.fire({
            title: 'Updating notice...',
            text: 'Please wait while we update the notice',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('/HMS/api/notices.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message || 'Notice updated successfully'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Failed to update notice'
                    });
                }
            })
            .catch(error => {
                console.error('Error updating notice:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An unexpected error occurred'
                });
            });
    });
    /**
     * Add file preview to the container
     * @param {Object} attachment - The attachment object
     * @param {HTMLElement} container - The container element
     * @param {number} noticeId - The ID of the notice
     */
    function addFilePreview(attachment, container, noticeId) {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-preview-item d-flex align-items-center mb-2';

        let iconClass = 'fa-file';
        if (attachment.file_type && attachment.file_type.includes('pdf')) {
            iconClass = 'fa-file-pdf';
        } else if (attachment.file_type && (
                attachment.file_type.includes('jpg') ||
                attachment.file_type.includes('jpeg') ||
                attachment.file_type.includes('png') ||
                attachment.file_type.includes('gif')
            )) {
            iconClass = 'fa-file-image';
        }

        fileItem.innerHTML = `
        <i class="fas ${iconClass} me-2"></i>
        <span class="me-auto">${attachment.file_name}</span>
        <button type="button" class="btn btn-sm btn-outline-danger" 
                onclick="deleteAttachment(${attachment.id}, ${noticeId})">
            <i class="fas fa-times"></i>
        </button>
    `;

        container.appendChild(fileItem);
    }


    /**
     * Delete attachment
     * @param {number} attachmentId - The ID of the attachment to delete
     * @param {number} noticeId - The ID of the notice the attachment belongs to
     */
    function deleteAttachment(attachmentId, noticeId) {
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
                fetch(`/HMS/api/notices.php?attachment_id=${attachmentId}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Attachment deleted successfully'
                            }).then(() => {
                                // Refresh the edit modal
                                editNotice(noticeId);
                            });
                        } else {
                            throw new Error(data.message || 'Failed to delete attachment');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to delete attachment'
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
        const previewContainer = input.id === 'editAttachment' ?
            document.getElementById('editFilePreviewContainer') :
            document.getElementById('filePreviewContainer');

        if (input.files.length > 0) {
            const file = input.files[0];
            const fileType = file.name.split('.').pop().toLowerCase();

            const fileItem = document.createElement('div');
            fileItem.className = 'file-preview-item d-flex align-items-center mb-2';

            let iconClass = 'fa-file';
            if (fileType === 'pdf') iconClass = 'fa-file-pdf';
            else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) iconClass = 'fa-file-image';

            fileItem.innerHTML = `
            <i class="fas ${iconClass} me-2"></i>
            <span class="me-auto">${file.name} (${(file.size / 1024).toFixed(1)}KB)</span>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="clearFileInput(this, '${input.id}')">
                <i class="fas fa-times"></i>
            </button>
        `;

            // Add new file preview at the top
            if (previewContainer.firstChild) {
                previewContainer.insertBefore(fileItem, previewContainer.firstChild);
            } else {
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
     * Clear file input
     * @param {HTMLElement} button - The button element
     * @param {string} inputId - The ID of the input element
     */
    function clearFileInput(button, inputId) {
        document.getElementById(inputId).value = '';
        button.closest('.file-preview-item').remove();
    }
</script>

<?php require_once '../includes/footer.php'; ?>