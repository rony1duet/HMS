<?php
$title = 'View Notice';
require_once '../config/database.php';
require_once '../includes/Session.php';
require_once '../models/User.php';
require_once '../models/NoticeAttachment.php';

Session::init();
$user = new User($conn);

if (!Session::isLoggedIn() || !Session::hasPermission('student')) {
    header('Location: /HMS/');
    exit();
}

// Get notice ID from URL
$noticeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$noticeId) {
    header('Location: /HMS/dashboard/student.php');
    exit();
}

// Get student's hall ID
$studentSlug = Session::get('slug');
$query = "SELECT h.id as hall_id FROM student_profiles sp LEFT JOIN halls h ON sp.hall_name = h.name WHERE sp.slug = :studentSlug";
$stmt = $conn->prepare($query);
$stmt->bindParam(':studentSlug', $studentSlug, PDO::PARAM_STR);
$stmt->execute();
$studentData = $stmt->fetch(PDO::FETCH_ASSOC);
$hallId = $studentData['hall_id'];

// Fetch notice details with attachments
$noticeQuery = "SELECT n.*, u.display_name as posted_by FROM notices n JOIN users u ON n.posted_by_slug = u.slug WHERE n.id = :id AND (n.hall_id = :hallId OR n.hall_id IS NULL)";
$stmt = $conn->prepare($noticeQuery);
$stmt->bindParam(':id', $noticeId, PDO::PARAM_INT);
$stmt->bindParam(':hallId', $hallId, PDO::PARAM_INT);
$stmt->execute();
$notice = $stmt->fetch(PDO::FETCH_ASSOC);

// Get notice attachments
$attachmentModel = new NoticeAttachment($conn);
$attachments = $attachmentModel->getAttachments($noticeId);

if (!$notice) {
    header('Location: /HMS/dashboard/student.php');
    exit();
}

require_once '../includes/header.php';
?>

<style>
    :root {
        --primary: #4361ee;
        --primary-light: #e6e9ff;
        --secondary: #3f37c9;
        --success: #4cc9f0;
        --danger: #f72585;
        --warning: #f8961e;
        --info: #4895ef;
        --light: #f8f9fa;
        --dark: #212529;
        --white: #ffffff;
        --gray: #6c757d;
        --border-radius: 8px;
        --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
    }

    .notice-view-container {
        max-width: 900px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .notice-card {
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .notice-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        position: relative;
    }

    .notice-badge {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        padding: 0.35rem 1rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .notice-badge.urgent {
        background-color: rgba(220, 38, 38, 0.1);
        color: var(--danger);
    }

    .notice-badge.important {
        background-color: rgba(217, 119, 6, 0.1);
        color: var(--warning);
    }

    .notice-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 0.5rem;
        padding-right: 100px;
        line-height: 1.3;
    }

    .notice-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        color: var(--gray);
        font-size: 0.9rem;
        margin-top: 1rem;
    }

    .notice-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .notice-content {
        padding: 1.5rem;
        line-height: 1.7;
        color: var(--dark);
        font-size: 1.05rem;
    }

    .notice-content p {
        margin-bottom: 1rem;
    }

    .notice-footer {
        padding: 1rem 1.5rem;
        background: var(--light);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .attachments-section {
        background: var(--white);
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-title i {
        color: var(--primary);
    }

    .attachment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .attachment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.25rem;
    }

    .attachment-card {
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: var(--border-radius);
        overflow: hidden;
        transition: var(--transition);
    }

    .attachment-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
    }

    .attachment-preview {
        height: 160px;
        background: var(--light);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .attachment-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .attachment-preview .file-icon {
        font-size: 3rem;
        color: var(--primary);
    }

    .attachment-preview .pdf-icon {
        color: #e63946;
    }

    .attachment-preview .image-icon {
        color: #4361ee;
    }

    .attachment-preview .doc-icon {
        color: #2a6f97;
    }

    .attachment-info {
        padding: 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .attachment-name {
        font-weight: 500;
        color: var(--dark);
        margin-bottom: 0.5rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .attachment-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: var(--gray);
    }

    .attachment-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: var(--border-radius);
        font-weight: 500;
        text-decoration: none;
        transition: var(--transition);
        cursor: pointer;
        border: none;
        font-size: 0.9rem;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: var(--secondary);
        color: white;
    }

    .btn-outline {
        background: transparent;
        border: 1px solid var(--primary);
        color: var(--primary);
    }

    .btn-outline:hover {
        background: var(--primary-light);
    }

    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8rem;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
    }

    .back-btn:hover {
        color: var(--secondary);
    }

    .full-preview {
        margin-top: 2rem;
    }

    .full-preview-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .pdf-viewer-container {
        width: 100%;
        height: 600px;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
        margin-bottom: 2rem;
    }

    .pdf-viewer {
        width: 100%;
        height: 100%;
        border: none;
    }

    .image-preview-container {
        text-align: center;
        margin-bottom: 2rem;
    }

    .image-preview {
        max-width: 100%;
        max-height: 600px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
    }

    .download-all-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--primary);
        color: white;
        border-radius: var(--border-radius);
        text-decoration: none;
        font-weight: 500;
        transition: var(--transition);
    }

    .download-all-btn:hover {
        background: var(--secondary);
        color: white;
    }

    @media (max-width: 768px) {
        .notice-title {
            font-size: 1.5rem;
            padding-right: 0;
        }

        .notice-meta {
            flex-direction: column;
            gap: 0.5rem;
        }

        .attachment-grid {
            grid-template-columns: 1fr;
        }

        .pdf-viewer-container {
            height: 400px;
        }

        .attachment-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
    }
</style>

<div class="notice-view-container">
    <a href="/HMS/dashboard/student.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="notice-card">
        <div class="notice-header">
            <?php if ($notice['importance'] === 'urgent' || $notice['importance'] === 'important'): ?>
                <span class="notice-badge <?php echo $notice['importance']; ?>">
                    <?php echo ucfirst($notice['importance']); ?>
                </span>
            <?php endif; ?>

            <h1 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h1>

            <div class="notice-meta">
                <span class="notice-meta-item">
                    <i class="fas fa-user"></i> Posted by: <?php echo htmlspecialchars($notice['posted_by']); ?>
                </span>
                <span class="notice-meta-item">
                    <i class="fas fa-clock"></i> <?php echo date('F j, Y \a\t g:i A', strtotime($notice['created_at'])); ?>
                </span>
                <?php if ($notice['end_date']): ?>
                    <span class="notice-meta-item">
                        <i class="fas fa-calendar-times"></i> Expires: <?php echo date('F j, Y', strtotime($notice['end_date'])); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="notice-content">
            <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
        </div>
    </div>

    <?php if (!empty($attachments)): ?>
        <div class="attachments-section">
            <div class="attachment-header">
                <h3 class="section-title">
                    <?php foreach ($attachments as $attachment):
                        $fileType = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));

                        echo htmlspecialchars($attachment['file_name']); ?>

                    <?php endforeach; ?>
                </h3>
                <a href="<?php echo htmlspecialchars($attachment['file_path']); ?>" class="btn btn-primary btn-sm" download>
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
            <?php foreach ($attachments as $attachment):
                $fileType = strtolower(pathinfo($attachment['file_name'], PATHINFO_EXTENSION));
            ?>
                <?php if ($fileType === 'pdf'): ?>
                    <div class="full-preview">
                        <div class="pdf-viewer-container">
                            <iframe src="<?php echo htmlspecialchars($attachment['file_path']); ?>#toolbar=0" class="pdf-viewer"></iframe>
                        </div>
                    </div>
                <?php elseif (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <div class="full-preview">
                        <div class="image-preview-container">
                            <img src="<?php echo htmlspecialchars($attachment['file_path']); ?>" alt="<?php echo htmlspecialchars($attachment['file_name']); ?>" class="image-preview">
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.getElementById('downloadAll').addEventListener('click', function(e) {
        e.preventDefault();
        <?php foreach ($attachments as $attachment): ?>
            window.open('<?php echo htmlspecialchars($attachment['file_path']); ?>', '_blank');
        <?php endforeach; ?>
    });
</script>

<?php require_once '../includes/footer.php'; ?>