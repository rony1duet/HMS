<?php
$title = 'Student Notices';
require_once '../config/database.php';
require_once '../includes/Session.php';
require_once '../models/Notice.php';
require_once '../models/User.php';
require_once '../includes/html_purifier.php';

Session::init();

// Check if user is logged in and has student role
if (!Session::isLoggedIn() || !Session::hasPermission('student')) {
    header('Location: /HMS/');
    exit();
}

$user = new User($conn);
$notice = new Notice($conn);

// Get student's hall_id
$studentSlug = Session::get('slug');
$query = "SELECT hall_name FROM student_profiles WHERE slug = :studentSlug";
$stmt = $conn->prepare($query);
$stmt->bindParam(":studentSlug", $studentSlug, PDO::PARAM_STR);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$hallName = $result ? $result['hall_name'] : null;

if (!$hallName) {
    $_SESSION['error'] = 'Could not determine your hall. Please contact the administrator.';
    header('Location: /HMS/dashboard/student.php');
    exit;
}

// Get notices for student's hall with pagination and filtering
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 5;
$currentFilter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$notices = $notice->getNoticesByHall($hallName, $page, $perPage, $currentFilter);
$totalNotices = $notice->countNoticesByHall($hallName, $currentFilter);

require_once '../includes/header.php';
?>

<style>
    :root {
        --primary: #4361ee;
        --primary-light: #e6e9ff;
        --secondary: #3f37c9;
        --danger: #f72585;
        --warning: #f8961e;
        --info: #4895ef;
        --success: #4cc9f0;
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --white: #ffffff;
        --border-radius: 8px;
        --box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        --transition: all 0.3s ease;
        --font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .notices-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
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
    }

    .filter-label {
        font-weight: 600;
        color: var(--dark);
        font-size: 0.875rem;
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

    .notice-card {
        background: var(--white);
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1rem;
        box-shadow: var(--box-shadow);
        border-left: 4px solid var(--info);
        transition: var(--transition);
    }

    .notice-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .notice-card.urgent {
        border-left-color: var(--danger);
    }

    .notice-card.important {
        border-left-color: var(--warning);
    }

    .notice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }

    .notice-title {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
        color: var(--dark);
    }

    .notice-badges {
        display: flex;
        align-items: center;
        gap: 0.5rem;
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
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.5;
    }

    /* Quill.js content formatting */
    .notice-content p {
        margin: 0.25rem 0;
    }

    .notice-content br {
        display: block;
        content: "";
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
        gap: 1rem;
        color: var(--gray);
        font-size: 0.875rem;
    }

    .notice-meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .view-btn {
        padding: 0.5rem 1.25rem;
        border-radius: var(--border-radius);
        background: var(--primary);
        color: var(--white);
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .view-btn:hover {
        background: var(--secondary);
        color: var(--white);
        transform: translateY(-1px);
    }

    .attachment-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: rgba(0, 0, 0, 0.05);
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-size: 0.75rem;
        color: var(--gray);
    }

    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
        background: var(--light);
        border-radius: var(--border-radius);
        margin: 2rem 0;
    }

    .empty-state-icon {
        font-size: 2.5rem;
        color: var(--gray);
        margin-bottom: 1rem;
    }

    .empty-state-title {
        font-size: 1.25rem;
        margin-bottom: 0.5rem;
        color: var(--dark);
    }

    .empty-state-text {
        color: var(--gray);
        margin: 0;
    }

    .pagination-container {
        display: flex;
        justify-content: center;
        margin-top: 2rem;
    }

    .pagination {
        display: flex;
        gap: 0.5rem;
    }

    .pagination-btn {
        padding: 0.5rem 0.75rem;
        border-radius: var(--border-radius);
        background: var(--white);
        color: var(--dark);
        border: 1px solid #dee2e6;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        font-weight: 500;
        text-decoration: none;
    }

    .pagination-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 2px 8px rgba(67, 97, 238, 0.2);
    }

    .pagination-btn:hover:not(.active):not(.disabled) {
        background: var(--primary-light);
        color: var(--primary);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.15);
    }

    .pagination-btn.disabled {
        opacity: 0.5;
        pointer-events: none;
        cursor: default;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .notice-meta {
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            margin-bottom: 1rem;
        }

        .notice-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
        }

        .notice-meta-item i {
            width: 1rem;
            text-align: center;
            margin-right: 0.25rem;
        }

        .notice-footer {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
            padding-top: 1.25rem;
        }

        .view-btn {
            width: 100%;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
        }
    }
</style>

<div class="notices-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Hall Notices</h1>
            <p class="page-subtitle">Important announcements from your hall administration</p>
        </div>
        <span class="hall-badge">
            <i class="fas fa-building"></i> <?php echo htmlspecialchars($hallName); ?>
        </span>
    </div>

    <div class="filter-container">
        <div class="filter-tabs">
            <a href="?filter=all" class="filter-tab <?php echo $currentFilter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="?filter=urgent" class="filter-tab <?php echo $currentFilter === 'urgent' ? 'active' : ''; ?>">Urgent</a>
            <a href="?filter=important" class="filter-tab <?php echo $currentFilter === 'important' ? 'active' : ''; ?>">Important</a>
            <a href="?filter=normal" class="filter-tab <?php echo $currentFilter === 'normal' ? 'active' : ''; ?>">Normal</a>
            <a href="?filter=new" class="filter-tab <?php echo $currentFilter === 'new' ? 'active' : ''; ?>">New</a>
        </div>
    </div>

    <?php if (empty($notices)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="fas fa-bell-slash"></i>
            </div>
            <h3 class="empty-state-title">No Notices Available</h3>
            <p class="empty-state-text">There are currently no <?php echo $currentFilter !== 'all' ? strtolower($currentFilter) : ''; ?> notices posted for your hall.</p>
        </div>
    <?php else: ?>
        <div class="notices-list">
            <?php foreach ($notices as $n): ?>
                <?php
                $isNew = (strtotime($n['created_at']) >= strtotime('-24 hours'));
                $importanceClass = strtolower($n['importance']);
                $hasAttachments = !empty($n['attachments']);
                ?>
                <div class="notice-card <?php echo $importanceClass; ?>">
                    <div class="notice-header">
                        <h3 class="notice-title"><?php echo htmlspecialchars($n['title']); ?></h3>
                        <div class="notice-badges">
                            <span class="notice-badge <?php echo $importanceClass; ?>">
                                <?php echo ucfirst($importanceClass); ?>
                            </span>
                            <?php if ($isNew): ?>
                                <span class="notice-badge new">New</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="notice-content">
                        <?php
                        // Convert HTML to plain text for preview while preserving basic structure
                        $purifiedContent = purify_html($n['content']);
                        $plainContent = strip_tags($purifiedContent);

                        // Clean up whitespace for better preview
                        $plainContent = preg_replace('/\s+/', ' ', $plainContent);
                        $plainContent = trim($plainContent);

                        // Limit to around 200 characters for preview
                        $limitedContent = mb_substr($plainContent, 0, 200);

                        // Add ellipsis if content was truncated
                        echo htmlspecialchars($limitedContent) . (mb_strlen($plainContent) > 200 ? '...' : '');
                        ?>
                    </div>

                    <div class="notice-footer">
                        <div class="notice-meta">
                            <span class="notice-meta-item">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($n['posted_by_name']); ?>
                            </span>
                            <span class="notice-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('M j, Y', strtotime($n['created_at'])); ?>
                            </span>
                            <?php if ($n['end_date']): ?>
                                <span class="notice-meta-item">
                                    <i class="fas fa-calendar-check"></i>
                                    Valid until <?php echo date('M j, Y', strtotime($n['end_date'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($hasAttachments): ?>
                                <span class="notice-meta-item">
                                    <i class="fas fa-paperclip"></i>
                                    <?php echo count($n['attachments']) . ' attachment' . (count($n['attachments']) > 1 ? 's' : ''); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <a href="/HMS/dashboard/view_notice.php?id=<?php echo $n['id']; ?>" class="view-btn">
                            View Notice
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalNotices > $perPage): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <a href="?filter=<?php echo $currentFilter; ?>&page=<?php echo max(1, $page - 1); ?>"
                        class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>

                    <?php
                    $totalPages = ceil($totalNotices / $perPage);
                    $maxPagesToShow = 5;
                    $startPage = max(1, min($page - floor($maxPagesToShow / 2), $totalPages - $maxPagesToShow + 1));
                    $endPage = min($startPage + $maxPagesToShow - 1, $totalPages);

                    // Always show first page
                    if ($startPage > 1) {
                        echo '<a href="?filter=' . $currentFilter . '&page=1" class="pagination-btn">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination-btn disabled">...</span>';
                        }
                    }

                    // Show numbered pages
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        echo '<a href="?filter=' . $currentFilter . '&page=' . $i . '" class="pagination-btn ' . ($i == $page ? 'active' : '') . '">' . $i . '</a>';
                    }

                    // Always show last page
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="pagination-btn disabled">...</span>';
                        }
                        echo '<a href="?filter=' . $currentFilter . '&page=' . $totalPages . '" class="pagination-btn">' . $totalPages . '</a>';
                    }
                    ?>

                    <a href="?filter=<?php echo $currentFilter; ?>&page=<?php echo $page + 1; ?>"
                        class="pagination-btn <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>