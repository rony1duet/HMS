<?php
$title = 'Student Dashboard';
require_once '../config/database.php';
require_once '../includes/Session.php';
require_once '../models/User.php';
require_once '../includes/html_purifier.php';

Session::init();
$user = new User($conn);

if (!Session::isLoggedIn() || !Session::hasPermission('student')) {
    header('Location: /HMS/');
    exit();
}

try {
    $profileStatus = $user->getProfileStatus($_SESSION['slug'] ?? '');
    if ($profileStatus === 'not_updated') {
        header('Location: /HMS/profiles/student/');
        exit();
    }
} catch (Exception $e) {
    error_log("Profile status check failed: " . $e->getMessage());
    header('Location: /HMS/error.php');
    exit();
}

// Get student profile information
$studentSlug = Session::get('slug');
$query = "SELECT sp.*, h.name as hall_name, h.id as hall_id, sp.profile_image_uri 
          FROM student_profiles sp
          LEFT JOIN halls h ON sp.hall_name = h.name 
          WHERE sp.slug = :studentSlug";
$stmt = $conn->prepare($query);
$studentSlugParam = $studentSlug;
$stmt->bindParam(":studentSlug", $studentSlugParam, PDO::PARAM_STR);
$stmt->execute();
$studentProfile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get meal balance and status
$mealQuery = "SELECT smc.credits as meal_balance,
              COALESCE(ms.day" . date('j') . ", 0) as today_schedule
              FROM student_meal_credits smc
              LEFT JOIN meal_schedules ms ON ms.student_id = smc.student_id
              AND ms.month = :month AND ms.year = :year
              WHERE smc.student_id = :student_id";
$stmt = $conn->prepare($mealQuery);
$studentId = $studentProfile['id'];
$currentMonth = date('n');
$currentYear = date('Y');
$stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
$stmt->bindParam(':month', $currentMonth, PDO::PARAM_INT);
$stmt->bindParam(':year', $currentYear, PDO::PARAM_INT);
$stmt->execute();
$mealData = $stmt->fetch(PDO::FETCH_ASSOC);

$mealBalance = $mealData['meal_balance'] ?? 0;
$todaySchedule = $mealData['today_schedule'] ?? 0;

// Convert schedule bits to meal status
$mealStatus = [
    'breakfast' => ($todaySchedule & 1) ? 'Taken' : 'Not Taken',
    'lunch' => ($todaySchedule & 2) ? 'Taken' : 'Not Taken',
    'dinner' => ($todaySchedule & 4) ? 'Taken' : 'Pending'
];

// Get monthly meal statistics
$statsQuery = "SELECT total_meals FROM meal_statistics 
              WHERE student_id = :student_id 
              AND month = :month AND year = :year";
$stmt = $conn->prepare($statsQuery);
$studentId = $studentProfile['id'];
$currentMonth = date('n');
$currentYear = date('Y');
$stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
$stmt->bindParam(':month', $currentMonth, PDO::PARAM_INT);
$stmt->bindParam(':year', $currentYear, PDO::PARAM_INT);
$stmt->execute();
$mealStats = $stmt->fetch(PDO::FETCH_ASSOC);
$monthlyMeals = $mealStats['total_meals'] ?? 0;

// Get upcoming events (limit to 3)
$upcomingEvents = [];
try {
    $eventsQuery = "SELECT * FROM events 
                   WHERE hall_id = :hallId 
                   AND event_date >= CURDATE() 
                   ORDER BY event_date ASC 
                   LIMIT 3";
    $stmt = $conn->prepare($eventsQuery);
    $hallId = $studentProfile['hall_id'];
    $stmt->bindParam(":hallId", $hallId, PDO::PARAM_INT);
    $stmt->execute();
    $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Events query failed: " . $e->getMessage());
}

// Get recent notices (limit to 5)
$recentNotices = [];
try {
    $noticeQuery = "SELECT n.*, u.display_name as posted_by 
                   FROM notices n 
                   JOIN users u ON n.posted_by_slug = u.slug 
                   WHERE (n.hall_id = :hallId OR n.hall_id IS NULL) 
                   AND (n.end_date >= CURDATE() OR n.end_date IS NULL) 
                   ORDER BY n.created_at DESC 
                   LIMIT 5";
    $stmt = $conn->prepare($noticeQuery);
    $hallId = $studentProfile['hall_id'];
    $stmt->bindParam(":hallId", $hallId, PDO::PARAM_INT);
    $stmt->execute();
    $recentNotices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Notices query failed: " . $e->getMessage());
}

// Meal status is already fetched from database above

require_once '../includes/header.php';
?>

<style>
    :root {
        --primary: #2563eb;
        --primary-light: #dbeafe;
        --secondary: #1d4ed8;
        --success: #059669;
        --warning: #d97706;
        --info: #4895ef;
        --danger: #dc2626;
        --dark: #111827;
        --light: #f9fafb;
        --gray: #6b7280;
        --white: #ffffff;
        --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }

    .dashboard {
        background-color: var(--light);
        min-height: calc(100vh - 56px);
        padding: 1rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    @media (min-width: 640px) {
        .dashboard {
            padding: 1.5rem;
        }
    }

    @media (min-width: 1024px) {
        .dashboard {
            padding: 2rem;
        }
    }

    /* Welcome Card */
    .welcome-card {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 1rem;
        padding: 1.25rem;
        color: white;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-md);
        position: relative;
        overflow: hidden;
    }

    .welcome-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 100%);
        pointer-events: none;
    }

    .welcome-content {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        color: white;
        flex-shrink: 0;
        overflow: hidden;
    }

    .avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .welcome-text h2 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .welcome-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .welcome-meta span {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (min-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    .stat-card {
        background: var(--white);
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: var(--shadow);
        transition: all 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
        background: var(--primary-light);
    }

    .stat-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        background-color: var(--primary);
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .stat-title {
        color: var(--gray);
        font-size: 0.9rem;
    }



    /* Content Sections */
    .dashboard-content {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
        margin-top: 2rem;
    }

    @media (min-width: 768px) {
        .dashboard-content {
            grid-template-columns: 3fr 2fr;
            align-items: start;
        }
    }

    @media (min-width: 1280px) {
        .dashboard-content {
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
    }

    .section-card {
        background: var(--white);
        border-radius: 0.75rem;
        box-shadow: var(--shadow);
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.05);
        height: 100%;
        display: flex;
        flex-direction: column;
        min-height: 400px;
        max-height: 600px;
    }

    .section-header {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--light);
    }

    @media (min-width: 640px) {
        .section-header {
            padding: 1rem 1.25rem;
        }
    }

    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        margin: 0;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-title i {
        color: var(--primary);
        font-size: 1rem;
    }

    .view-all {
        font-size: 0.875rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        transition: color 0.2s ease;
    }

    .view-all:hover {
        color: var(--secondary);
    }

    /* Notices List */
    .notices-list {
        padding: 1rem;
        flex-grow: 1;
        overflow-y: auto;
        height: calc(100% - 60px);
        scrollbar-width: thin;
        scrollbar-color: var(--primary) var(--light);
    }

    .notices-list::-webkit-scrollbar {
        width: 6px;
    }

    .notices-list::-webkit-scrollbar-track {
        background: var(--light);
        border-radius: 3px;
    }

    .notices-list::-webkit-scrollbar-thumb {
        background-color: var(--primary);
        border-radius: 3px;
    }

    .notices-table {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .notice-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 1.25rem;
        background: var(--white);
        border-radius: 0.75rem;
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    }

    .notice-row:hover {
        transform: translateY(-2px);
        background: var(--primary-light);
        border-color: var(--info);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
    }

    .notice-row.urgent {
        border-left: 4px solid var(--danger);
        background: rgba(220, 38, 38, 0.05);
    }

    .notice-row.important {
        border-left: 4px solid var(--warning);
        background: rgba(217, 119, 6, 0.05);
    }

    .notice-row.normal {
        border-left: 4px solid var(--primary);
        background: rgba(5, 150, 105, 0.05);
    }

    .notice-cell {
        padding: 0.5rem;
    }

    .notice-main {
        flex: 1;
        min-width: 0;
    }

    .notice-title-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 0.5rem;
    }

    .notice-title {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        color: var(--dark);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .notice-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-weight: 600;
        flex-shrink: 0;
    }

    .notice-badge.urgent {
        background-color: rgba(220, 38, 38, 0.1);
        color: var(--danger);
    }

    .notice-badge.important {
        background-color: rgba(217, 119, 6, 0.1);
        color: var(--warning);
    }

    .notice-badge.normal {
        background-color: rgba(5, 150, 105, 0.1);
        color: var(--info);
    }

    .notice-excerpt {
        font-size: 0.875rem;
        color: var(--gray);
        margin: 0;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notice-meta {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-end;
        min-width: 150px;
    }

    .notice-author,
    .notice-date {
        font-size: 0.8rem;
        color: var(--gray);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    @media (max-width: 768px) {
        .notice-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .notice-meta {
            flex-direction: row;
            justify-content: space-between;
            width: 100%;
            margin-top: 1rem;
            align-items: center;
        }
    }

    /* Events List */
    .events-list {
        padding: 0.5rem;
    }

    .event-item {
        padding: 1rem;
        margin-bottom: 0.75rem;
        border-radius: 8px;
        background: var(--light);
        transition: all 0.2s;
    }

    .event-item:hover {
        transform: translateX(5px);
        background: var(--primary-light);
    }

    .event-title {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .event-desc {
        font-size: 0.9rem;
        color: var(--gray);
        margin-bottom: 0.75rem;
        line-height: 1.5;
    }

    .event-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: var(--gray);
        margin-bottom: 0.5rem;
    }

    .event-status {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .status-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        font-weight: 600;
    }

    /* Meal Status */
    .meal-status {
        padding: 1.5rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    @media (min-width: 640px) {
        .meal-status {
            padding: 1.25rem;
        }
    }

    .meal-progress {
        margin-bottom: 1.5rem;
    }

    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        color: var(--gray);
    }

    .progress-bar {
        height: 6px;
        border-radius: 999px;
        background: var(--light);
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: var(--success);
        width: 75%;
        transition: width 0.3s ease;
    }

    .meal-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-top: auto;
    }

    .meal-card {
        padding: 1rem;
        border-radius: 0.5rem;
        background: var(--light);
        text-align: center;
        transition: all 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .meal-card:hover {
        transform: translateY(-2px);
        background: var(--primary-light);
    }

    .meal-time {
        font-size: 0.75rem;
        color: var(--gray);
        margin-bottom: 0.375rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .meal-state {
        font-weight: 600;
        color: var(--dark);
        font-size: 0.875rem;
    }

    .meal-state {
        font-weight: 600;
        padding: 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        justify-content: center;
    }

    .meal-state.taken {
        color: var(--success);
        background-color: rgba(5, 150, 105, 0.1);
    }

    .meal-state.not-taken {
        color: var(--danger);
        background-color: rgba(220, 38, 38, 0.1);
    }

    .meal-state.pending {
        color: var(--warning);
        background-color: rgba(217, 119, 6, 0.1);
    }

    .meal-state i {
        font-size: 1.1rem;
    }

    .progress-fill {
        transition: width 0.3s ease;
        background: linear-gradient(90deg, var(--success) 0%, var(--primary) 100%);
    }

    /* Empty State */
    .empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: var(--gray);
    }

    .empty-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .actions-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .welcome-content {
            flex-direction: column;
            text-align: center;
        }

        .welcome-meta {
            justify-content: center;
        }
    }

    @media (max-width: 576px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .actions-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .meal-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="dashboard">
    <!-- Welcome Section -->
    <div class="welcome-card">
        <div class="welcome-content">
            <div class="avatar">
                <?php if ($studentProfile && !empty($studentProfile['profile_image_uri'])): ?>
                    <img src="<?php echo htmlspecialchars($studentProfile['profile_image_uri']); ?>" alt="Profile Picture" class="avatar-img">
                <?php else: ?>
                    <?php echo substr($_SESSION['display_name'] ?? 'S', 0, 1); ?>
                <?php endif; ?>
            </div>
            <div class="welcome-text">
                <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['display_name'] ?? 'Student'); ?>!</h2>
                <div class="welcome-meta">
                    <span><i class="fas fa-university"></i> <?php echo htmlspecialchars($studentProfile['hall_name'] ?? 'Hall not assigned'); ?></span>
                    <?php if ($studentProfile && isset($studentProfile['student_id'])): ?>
                        <span><i class="fas fa-id-card"></i> ID: <?php echo htmlspecialchars($studentProfile['student_id']); ?></span>
                    <?php endif; ?>
                    <span><i class="fas fa-wallet"></i> Balance: ৳<?php echo $mealBalance; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon">
                    <i class="fas fa-utensils"></i>
                </div>
            </div>
            <div class="stat-value">৳<?php echo $mealBalance; ?></div>
            <div class="stat-title">Meal Balance</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: var(--success);">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($upcomingEvents); ?></div>
            <div class="stat-title">Upcoming Events</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: var(--warning);">
                    <i class="fas fa-bell"></i>
                </div>
            </div>
            <div class="stat-value"><?php echo count($recentNotices); ?></div>
            <div class="stat-title">New Notices</div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background-color: var(--danger);">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>
            <div class="stat-value">2</div>
            <div class="stat-title">Pending Issues</div>
        </div>
    </div>



    <!-- Main Content -->
    <div class="dashboard-content">
        <!-- Left Column -->
        <div>
            <!-- Meal Status -->
            <div class="section-card mb-3">
                <div class="section-header">
                    <h3 class="section-title">Today's Meal Status</h3>
                </div>
                <div class="meal-status">
                    <div class="meal-progress">
                        <div class="progress-label">
                            <span>Monthly Meal Usage</span>
                            <span><?php echo $monthlyMeals; ?>/60 meals</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min(($monthlyMeals / 60) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                    <div class="meal-grid">
                        <div class="meal-card">
                            <div class="meal-time">Breakfast</div>
                            <div class="meal-state <?php echo $mealStatus['breakfast'] === 'Taken' ? 'taken' : ($mealStatus['breakfast'] === 'Not Taken' ? 'not-taken' : 'pending'); ?>">
                                <i class="fas <?php echo $mealStatus['breakfast'] === 'Taken' ? 'fa-check-circle' : ($mealStatus['breakfast'] === 'Not Taken' ? 'fa-times-circle' : 'fa-clock'); ?>"></i>
                                <?php echo $mealStatus['breakfast']; ?>
                            </div>
                        </div>
                        <div class="meal-card">
                            <div class="meal-time">Lunch</div>
                            <div class="meal-state <?php echo $mealStatus['lunch'] === 'Taken' ? 'taken' : ''; ?>">
                                <?php echo $mealStatus['lunch']; ?>
                            </div>
                        </div>
                        <div class="meal-card">
                            <div class="meal-time">Dinner</div>
                            <div class="meal-state <?php echo $mealStatus['dinner'] === 'Taken' ? 'taken' : ($mealStatus['dinner'] === 'Pending' ? 'pending' : ''); ?>">
                                <?php echo $mealStatus['dinner']; ?>
                            </div>
                        </div>
                        <div class="meal-card">
                            <div class="meal-time">Balance</div>
                            <div class="meal-state"><?php echo $mealBalance; ?> credits</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Notices Section -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Recent Notices</h3>
                    <a href="/HMS/dashboard/student_notices.php" class="view-all">View All</a>
                </div>
                <div class="notices-list">
                    <?php if (!empty($recentNotices)): ?>
                        <div class="notices-table">
                            <?php foreach ($recentNotices as $notice):
                                $importanceClass = $notice['importance'] === 'urgent' ? 'urgent' : ($notice['importance'] === 'important' ? 'important' : 'normal');
                            ?>
                                <a href="/HMS/dashboard/view_notice.php?id=<?php echo $notice['id']; ?>" class="notice-row <?php echo $importanceClass; ?>">
                                    <div class="notice-cell notice-main">
                                        <div class="notice-title-wrapper">
                                            <h4 class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></h4>
                                            <?php if ($notice['importance'] === 'urgent'): ?>
                                                <span class="notice-badge urgent">Urgent</span>
                                            <?php elseif ($notice['importance'] === 'important'): ?>
                                                <span class="notice-badge important">Important</span>
                                            <?php else: ?>
                                                <span class="notice-badge normal">Normal</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="notice-excerpt">
                                            <?php
                                            // Strip HTML tags but preserve spaces
                                            $plainContent = strip_tags($notice['content']);
                                            // Clean up whitespace for better preview
                                            $plainContent = preg_replace('/\s+/', ' ', $plainContent);
                                            $plainContent = trim($plainContent);
                                            // Limit to around 100 characters
                                            echo htmlspecialchars(substr($plainContent, 0, 100)) .
                                                (strlen($plainContent) > 100 ? '...' : '');
                                            ?>
                                        </p>
                                    </div>
                                    <div class="notice-cell notice-meta">
                                        <div class="notice-author">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($notice['posted_by']); ?>
                                        </div>
                                        <div class="notice-date">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('M j, Y', strtotime($notice['created_at'])); ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-bell-slash"></i>
                            </div>
                            <p>No notices available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Upcoming Events -->
            <div class="section-card mb-3">
                <div class="section-header">
                    <h3 class="section-title">Upcoming Events</h3>
                    <a href="/HMS/events/" class="view-all">View All</a>
                </div>
                <div class="events-list">
                    <?php if (!empty($upcomingEvents)): ?>
                        <?php foreach ($upcomingEvents as $event): ?>
                            <div class="event-item">
                                <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                                <div class="event-desc">
                                    <?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 80)) .
                                        (strlen($event['description']) > 80 ? '...' : '')); ?>
                                </div>
                                <div class="event-meta">
                                    <span><i class="fas fa-calendar-day"></i> <?php echo date('M j', strtotime($event['event_date'])); ?></span>
                                    <span><i class="fas fa-clock"></i> <?php echo $event['event_time'] ? date('g:i A', strtotime($event['event_time'])) : 'TBA'; ?></span>
                                </div>
                                <div class="event-status">
                                    <span class="status-badge bg-<?php
                                                                    echo $event['status'] === 'upcoming' ? 'primary' : ($event['status'] === 'ongoing' ? 'success' : ($event['status'] === 'cancelled' ? 'danger' : 'secondary'));
                                                                    ?>">
                                        <?php echo ucfirst($event['status']); ?>
                                    </span>
                                    <?php if ($event['location']): ?>
                                        <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <p>No upcoming events</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Contacts -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Quick Contacts</h3>
                </div>
                <div class="events-list">
                    <div class="event-item">
                        <div class="event-title">Hall Office</div>
                        <div class="event-meta">
                            <span><i class="fas fa-phone"></i> +880 1234 56789</span>
                        </div>
                    </div>
                    <div class="event-item">
                        <div class="event-title">Provost</div>
                        <div class="event-meta">
                            <span><i class="fas fa-phone"></i> +880 9876 54321</span>
                        </div>
                    </div>
                    <div class="event-item">
                        <div class="event-title">Emergency</div>
                        <div class="event-meta">
                            <span><i class="fas fa-phone"></i> +880 1122 33445</span>
                        </div>
                    </div>
                    <div class="event-item">
                        <div class="event-title">Email</div>
                        <div class="event-meta">
                            <span><i class="fas fa-envelope"></i> hall@university.edu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Live Clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        document.getElementById('live-clock').textContent = timeString;
    }

    updateClock();
    setInterval(updateClock, 1000);
</script>

<?php require_once '../includes/footer.php'; ?>