<?php
$title = 'Provost Dashboard';
require_once '../includes/Session.php';
require_once '../models/ProvostApproval.php';
require_once '../models/Student.php';
require_once '../models/Room.php';
require_once '../models/Staff.php';
require_once '../config/database.php';

Session::init();

// Check if user is logged in and has provost role
if (!Session::isLoggedIn() || !Session::hasPermission('provost')) {
    header('Location: /HMS/');
    exit();
}

// Check provost approval status
$provostApproval = new ProvostApproval($conn);
$userSlug = Session::getSlug();
$approvalStatus = $provostApproval->getApprovalStatus($userSlug);

// If not even in approval system, create request
if (!$approvalStatus) {
    $provostApproval->createApprovalRequest($userSlug);
    $approvalStatus = $provostApproval->getApprovalStatus($userSlug);
}

// Get hall information if provost is approved
$hallId = null;
$hallName = null;
$totalStudents = 0;
$availableRooms = 0;
$pendingRequests = 0;
$totalStaff = 0;
$recentActivities = [];
$hallCapacity = 0;
$occupancyRate = 0;
$requestsByType = [
    'room_change' => 0,
    'maintenance' => 0,
    'leave' => 0
];
$requestsByStatus = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'in_progress' => 0
];

if ($approvalStatus && $approvalStatus['status'] === 'approved') {
    $hallId = $approvalStatus['hall_id'];
    $hallName = $approvalStatus['hall_name'];

    // Get total students count in this hall
    try {
        $query = "SELECT COUNT(*) as total FROM student_profiles WHERE hall_name = 
                 (SELECT name FROM halls WHERE id = :hall_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalStudents = $result ? $result['total'] : 0;
    } catch (PDOException $e) {
        error_log("Error getting student count: " . $e->getMessage());
    }

    // Get hall capacity and calculate occupancy rate
    try {
        $query = "SELECT sum(capacity) as total_capacity FROM rooms WHERE hall_id = :hall_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $hallCapacity = $result ? $result['total_capacity'] : 0;

        if ($hallCapacity > 0) {
            $occupancyRate = round(($totalStudents / $hallCapacity) * 100);
        }
    } catch (PDOException $e) {
        error_log("Error getting hall capacity: " . $e->getMessage());
    }

    // Get available rooms count
    try {
        $roomModel = new Room($conn);
        $availableRoomsData = $roomModel->getAvailableRooms();
        $availableRooms = count($availableRoomsData);
    } catch (Exception $e) {
        error_log("Error getting available rooms: " . $e->getMessage());
    }

    // Get pending requests count (room changes, leave applications, maintenance)
    try {
        // Room change requests
        $query = "SELECT status, COUNT(*) as total FROM room_change_requests 
                 WHERE hall_id = :hall_id GROUP BY status";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $row) {
                $status = strtolower($row['status']);
                $requestsByStatus[$status] = $requestsByStatus[$status] + $row['total'];
                if ($status === 'pending') {
                    $pendingRequests += $row['total'];
                    $requestsByType['room_change'] = $row['total'];
                }
            }
        }

        // Maintenance requests
        $query = "SELECT status, COUNT(*) as total FROM maintenance_requests 
                 WHERE hall_id = :hall_id GROUP BY status";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $row) {
                $status = strtolower($row['status']);
                $requestsByStatus[$status] = $requestsByStatus[$status] + $row['total'];
                if ($status === 'pending') {
                    $pendingRequests += $row['total'];
                    $requestsByType['maintenance'] = $row['total'];
                }
            }
        }

        // Leave applications
        $query = "SELECT status, COUNT(*) as total FROM leave_applications 
                 WHERE hall_id = :hall_id GROUP BY status";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as $row) {
                $status = strtolower($row['status']);
                $requestsByStatus[$status] = $requestsByStatus[$status] + $row['total'];
                if ($status === 'pending') {
                    $pendingRequests += $row['total'];
                    $requestsByType['leave'] = $row['total'];
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Error getting pending requests: " . $e->getMessage());
    }

    // Get total staff count
    try {
        $query = "SELECT COUNT(*) as total FROM staff_profiles 
                 WHERE working_hall = (SELECT name FROM halls WHERE id = :hall_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalStaff = $result ? $result['total'] : 0;
    } catch (PDOException $e) {
        error_log("Error getting staff count: " . $e->getMessage());
    }

    // Get recent activities
    try {
        // Get room change requests with more details
        $query = "SELECT r.id, r.created_at as date, s.full_name as student_name, s.id as student_id,
                 'Room Change Request' as activity_type, r.status, r.reason,
                 r.current_room_id, r.requested_room_id, 
                 (SELECT number FROM rooms WHERE id = r.current_room_id) as current_room,
                 (SELECT number FROM rooms WHERE id = r.requested_room_id) as requested_room
                 FROM room_change_requests r
                 JOIN student_profiles s ON r.student_id = s.id
                 WHERE r.hall_id = :hall_id
                 ORDER BY r.created_at DESC LIMIT 5";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
            $stmt->execute();
            $roomChangeRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $recentActivities = array_merge($recentActivities, $roomChangeRequests);
        }

        // Get maintenance requests with more details
        $query = "SELECT m.id, m.created_at as date, s.full_name as student_name, s.id as student_id,
                 'Maintenance Request' as activity_type, m.status, m.description as reason,
                 m.room_id, (SELECT number FROM rooms WHERE id = m.room_id) as room_number,
                 m.issue_type
                 FROM maintenance_requests m
                 JOIN student_profiles s ON m.student_id = s.id
                 WHERE m.hall_id = :hall_id
                 ORDER BY m.created_at DESC LIMIT 5";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
            $stmt->execute();
            $maintenanceRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $recentActivities = array_merge($recentActivities, $maintenanceRequests);
        }

        // Get leave applications with more details
        $query = "SELECT l.id, l.created_at as date, s.full_name as student_name, s.id as student_id,
                 'Leave Application' as activity_type, l.status, l.reason,
                 l.start_date, l.end_date, l.room_id,
                 (SELECT number FROM rooms WHERE id = l.room_id) as room_number
                 FROM leave_applications l
                 JOIN student_profiles s ON l.student_id = s.id
                 WHERE l.hall_id = :hall_id
                 ORDER BY l.created_at DESC LIMIT 5";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bindParam(':hall_id', $hallId, PDO::PARAM_INT);
            $stmt->execute();
            $leaveApplications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $recentActivities = array_merge($recentActivities, $leaveApplications);
        }

        // Sort by date, most recent first
        usort($recentActivities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Limit to 5 most recent activities
        $recentActivities = array_slice($recentActivities, 0, 5);
    } catch (PDOException $e) {
        error_log("Error getting recent activities: " . $e->getMessage());
    }
}

require_once '../includes/header.php';
?>

<style>
    :root {
        --primary: #4361ee;
        --primary-light: #eef2ff;
        --secondary: #3f37c9;
        --success: #4cc9f0;
        --danger: #f72585;
        --warning: #f8961e;
        --info: #4895ef;
        --light: #f8f9fa;
        --dark: #212529;
        --gray: #6c757d;
        --light-gray: #e9ecef;
        --border-radius: 0.375rem;
        --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        --transition: all 0.3s ease;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fb;
        color: #333;
    }

    .dashboard-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--border-radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .dashboard-title {
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .dashboard-subtitle {
        font-size: 1rem;
        opacity: 0.9;
    }

    .hall-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.875rem;
    }

    .header-actions .btn {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border: none;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .header-actions .btn:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    .stat-card {
        background: white;
        border-radius: var(--border-radius);
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--box-shadow);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .stat-number {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--gray);
        margin-bottom: 0.5rem;
    }

    .stat-trend {
        font-size: 0.75rem;
        color: var(--gray);
    }

    .trend-up {
        color: var(--success);
    }

    .trend-down {
        color: var(--danger);
    }

    .card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        margin-bottom: 1.5rem;
    }

    .card-header {
        padding: 1rem 1.25rem;
        background: white;
        border-bottom: 1px solid var(--light-gray);
        border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
    }

    .card-header h5 {
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }

    .card-body {
        padding: 1.25rem;
    }

    .activity-item {
        padding: 1rem 0;
        border-bottom: 1px solid var(--light-gray);
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .activity-meta {
        font-size: 0.875rem;
        color: var(--gray);
        margin-bottom: 0.5rem;
    }

    .activity-time {
        font-size: 0.75rem;
        color: var(--gray);
    }

    .status-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-pending {
        background-color: rgba(248, 150, 30, 0.1);
        color: var(--warning);
    }

    .status-approved {
        background-color: rgba(76, 201, 240, 0.1);
        color: var(--success);
    }

    .status-rejected {
        background-color: rgba(247, 37, 133, 0.1);
        color: var(--danger);
    }

    .status-in-progress {
        background-color: rgba(72, 149, 239, 0.1);
        color: var(--info);
    }

    .action-card {
        display: block;
        background: white;
        border-radius: var(--border-radius);
        padding: 1.25rem;
        margin-bottom: 1rem;
        text-decoration: none;
        color: inherit;
        transition: var(--transition);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .action-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--box-shadow);
        color: inherit;
    }

    .action-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        margin-bottom: 0.75rem;
    }

    .action-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .action-subtitle {
        font-size: 0.875rem;
        color: var(--gray);
    }

    .progress-container {
        width: 120px;
        height: 120px;
        margin: 0 auto 1rem;
        position: relative;
    }

    .progress-circle {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: conic-gradient(var(--primary) calc(var(--percentage) * 1%), var(--light-gray) 0);
    }

    .progress-value {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }

    .progress-percentage {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .progress-label {
        font-size: 0.75rem;
        color: var(--gray);
    }

    .hall-stat {
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--light-gray);
        display: flex;
        justify-content: space-between;
    }

    .hall-stat:last-child {
        border-bottom: none;
    }

    .approval-card {
        max-width: 600px;
        margin: 2rem auto;
        background: white;
        border-radius: var(--border-radius);
        overflow: hidden;
        box-shadow: var(--box-shadow);
    }

    .approval-header {
        padding: 2rem;
        text-align: center;
    }

    .approval-pending-bg {
        background: linear-gradient(135deg, #ff9a44, #fc6076);
        color: white;
    }

    .approval-rejected-bg {
        background: linear-gradient(135deg, #fc4a1a, #f7b733);
        color: white;
    }

    .approval-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .approval-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .approval-body {
        padding: 2rem;
    }

    .approval-message {
        margin-bottom: 1.5rem;
    }

    .approval-reason {
        background: var(--light);
        padding: 1rem;
        border-radius: var(--border-radius);
        margin-bottom: 1.5rem;
    }

    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--danger);
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .dashboard-title {
            font-size: 1.5rem;
        }

        .stat-card {
            padding: 1rem;
        }

        .stat-number {
            font-size: 1.5rem;
        }

        .header-actions .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }

    @media (max-width: 576px) {
        .dashboard-header {
            padding: 1rem;
        }

        .card-body {
            padding: 1rem;
        }
    }
</style>

<div class="container py-4">
    <?php if ($approvalStatus['status'] !== 'approved'): ?>
        <!-- Approval Pending/Rejected Display -->
        <div class="approval-card">
            <div class="approval-header <?php echo $approvalStatus['status'] === 'pending' ? 'approval-pending-bg' : 'approval-rejected-bg'; ?>">
                <div class="approval-icon">
                    <?php if ($approvalStatus['status'] === 'pending'): ?>
                        <i class="fas fa-clock"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle"></i>
                    <?php endif; ?>
                </div>
                <h1 class="approval-title">
                    <?php if ($approvalStatus['status'] === 'pending'): ?>
                        Approval Pending
                    <?php else: ?>
                        Access Restricted
                    <?php endif; ?>
                </h1>
                <p>
                    <?php if ($approvalStatus['status'] === 'pending'): ?>
                        Your request is being reviewed by administrators
                    <?php else: ?>
                        Your provost access request was not approved
                    <?php endif; ?>
                </p>
            </div>
            <div class="approval-body">
                <p class="approval-message">
                    <?php if ($approvalStatus['status'] === 'pending'): ?>
                        Your provost account is currently pending administrative approval.
                        This process typically takes 24-48 hours. You will receive an email notification
                        once your request has been processed.
                    <?php else: ?>
                        Your provost access request has been rejected by the administrator.
                        <?php if ($approvalStatus['rejection_reason']): ?>
                <div class="approval-reason mt-3">
                    <strong>Reason for Rejection:</strong>
                    <p class="mb-0"><?php echo htmlspecialchars($approvalStatus['rejection_reason']); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        </p>

        <p class="text-muted small">
            If you believe this is a mistake or have any questions,
            please contact the system administrator.
        </p>

        <a href="/HMS/" class="btn btn-primary mt-3">
            <i class="fas fa-home me-2"></i> Return to Home
        </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="dashboard-title">Welcome, Provost</h1>
                    <p class="dashboard-subtitle">
                        Managing <span class="hall-badge"><?php echo htmlspecialchars($hallName); ?></span>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="text-white">
                        <div><?php echo date('l, F j, Y'); ?></div>
                        <div class="h4" id="current-time"></div>
                    </div>
                </div>
            </div>
            <div class="header-actions mt-3">
                <a href="/HMS/requests/all.php?hall_id=<?php echo $hallId; ?>" class="btn">
                    <i class="fas fa-clipboard-list me-2"></i> All Requests
                </a>
                <a href="/HMS/reports/hall_summary.php?hall_id=<?php echo $hallId; ?>" class="btn">
                    <i class="fas fa-chart-bar me-2"></i> Generate Report
                </a>
                <a href="/HMS/settings/hall.php?hall_id=<?php echo $hallId; ?>" class="btn">
                    <i class="fas fa-cogs me-2"></i> Hall Settings
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($totalStudents); ?></div>
                    <div class="stat-label">Total Students</div>
                    <div class="stat-trend">
                        <i class="fas fa-arrow-up text-success me-1"></i>
                        <span class="text-success">Active residents</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div class="stat-number"><?php echo $availableRooms; ?></div>
                    <div class="stat-label">Available Rooms</div>
                    <div class="stat-trend">
                        <?php if ($availableRooms > 0): ?>
                            <i class="fas fa-check text-success me-1"></i>
                            <span class="text-success">Available</span>
                        <?php else: ?>
                            <i class="fas fa-exclamation text-warning me-1"></i>
                            <span class="text-warning">Full</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-number"><?php echo $pendingRequests; ?></div>
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-trend">
                        <?php if ($pendingRequests > 0): ?>
                            <i class="fas fa-exclamation text-danger me-1"></i>
                            <span class="text-danger">Action needed</span>
                        <?php else: ?>
                            <i class="fas fa-check text-success me-1"></i>
                            <span class="text-success">Up to date</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-number"><?php echo $totalStaff; ?></div>
                    <div class="stat-label">Staff Members</div>
                    <div class="stat-trend">
                        <i class="fas fa-users text-info me-1"></i>
                        <span class="text-info">Supporting staff</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Recent Activities -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-activity me-2"></i> Recent Activities</h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="activityFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="activityFilterDropdown">
                                <li><a class="dropdown-item" href="#" data-filter="all">All Activities</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="room-change">Room Changes</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="maintenance">Maintenance</a></li>
                                <li><a class="dropdown-item" href="#" data-filter="leave">Leave Applications</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#" data-filter="pending">Pending Only</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h5>No Activities Found</h5>
                                <p class="text-muted">There are no recent activities to display.</p>
                            </div>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <?php
                                    $statusClass = '';
                                    $activityType = '';

                                    switch (strtolower($activity['status'])) {
                                        case 'pending':
                                            $statusClass = 'status-pending';
                                            break;
                                        case 'approved':
                                            $statusClass = 'status-approved';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'status-rejected';
                                            break;
                                        case 'in progress':
                                            $statusClass = 'status-in-progress';
                                            break;
                                        default:
                                            $statusClass = 'bg-secondary';
                                    }

                                    switch ($activity['activity_type']) {
                                        case 'Room Change Request':
                                            $activityType = 'room-change';
                                            $activityUrl = "/HMS/requests/room_change_details.php?id={$activity['id']}";
                                            break;
                                        case 'Maintenance Request':
                                            $activityType = 'maintenance';
                                            $activityUrl = "/HMS/maintenance/details.php?id={$activity['id']}";
                                            break;
                                        case 'Leave Application':
                                            $activityType = 'leave';
                                            $activityUrl = "/HMS/leave/details.php?id={$activity['id']}";
                                            break;
                                        default:
                                            $activityType = '';
                                            $activityUrl = "#";
                                    }
                                    ?>
                                    <div class="activity-item" data-type="<?php echo $activityType; ?>" data-status="<?php echo strtolower($activity['status']); ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="activity-title mb-0"><?php echo htmlspecialchars($activity['activity_type']); ?></h6>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(htmlspecialchars($activity['status'])); ?>
                                            </span>
                                        </div>
                                        <p class="mb-2">From <strong><?php echo htmlspecialchars($activity['student_name']); ?></strong></p>

                                        <?php if ($activity['activity_type'] === 'Room Change Request' && isset($activity['current_room']) && isset($activity['requested_room'])): ?>
                                            <p class="activity-meta mb-2">
                                                <i class="fas fa-exchange-alt me-1"></i>
                                                Room <?php echo htmlspecialchars($activity['current_room']); ?> → <?php echo htmlspecialchars($activity['requested_room']); ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($activity['activity_type'] === 'Maintenance Request' && isset($activity['room_number']) && isset($activity['issue_type'])): ?>
                                            <p class="activity-meta mb-2">
                                                <i class="fas fa-tools me-1"></i>
                                                <?php echo htmlspecialchars($activity['issue_type']); ?> in Room <?php echo htmlspecialchars($activity['room_number']); ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($activity['activity_type'] === 'Leave Application' && isset($activity['start_date']) && isset($activity['end_date'])): ?>
                                            <p class="activity-meta mb-2">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('M j', strtotime($activity['start_date'])); ?> - <?php echo date('M j, Y', strtotime($activity['end_date'])); ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if (isset($activity['reason'])): ?>
                                            <p class="activity-meta mb-2">
                                                <i class="fas fa-comment-alt me-1"></i>
                                                <?php echo substr(htmlspecialchars($activity['reason']), 0, 60); ?>
                                                <?php echo (strlen($activity['reason']) > 60) ? '...' : ''; ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="activity-time">
                                                <?php echo date('M j, Y - g:i a', strtotime($activity['date'])); ?>
                                            </small>
                                            <a href="<?php echo $activityUrl; ?>" class="btn btn-sm btn-outline-primary">
                                                View <i class="fas fa-arrow-right ms-1"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="text-center mt-3">
                                <a href="/HMS/requests/all.php?hall_id=<?php echo $hallId; ?>" class="btn btn-outline-primary">
                                    View All Activities <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Hall Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-building me-2"></i> Hall Information</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="progress-container mb-3">
                            <div class="progress-circle" style="--percentage: <?php echo $occupancyRate; ?>;"></div>
                            <div class="progress-value">
                                <div class="progress-percentage"><?php echo $occupancyRate; ?>%</div>
                                <div class="progress-label">Occupancy Rate</div>
                            </div>
                        </div>
                        <p class="text-muted small">
                            <?php echo $totalStudents; ?> students out of <?php echo $hallCapacity; ?> capacity
                        </p>

                        <div class="mt-4">
                            <div class="hall-stat">
                                <span><i class="fas fa-users me-2"></i> Total Students</span>
                                <strong><?php echo $totalStudents; ?></strong>
                            </div>
                            <div class="hall-stat">
                                <span><i class="fas fa-door-open me-2"></i> Available Rooms</span>
                                <strong><?php echo $availableRooms; ?></strong>
                            </div>
                            <div class="hall-stat">
                                <span><i class="fas fa-user-tie me-2"></i> Staff Members</span>
                                <strong><?php echo $totalStaff; ?></strong>
                            </div>
                            <div class="hall-stat">
                                <span><i class="fas fa-clipboard-check me-2"></i> Pending Requests</span>
                                <strong><?php echo $pendingRequests; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="/HMS/students/manage.php?hall_id=<?php echo $hallId; ?>" class="action-card">
                                    <div class="action-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <h6 class="action-title">Manage Students</h6>
                                    <p class="action-subtitle">View, add or update</p>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/HMS/actions/staff_management.php?hall_id=<?php echo $hallId; ?>" class="action-card">
                                    <div class="action-icon bg-success bg-opacity-10 text-success">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <h6 class="action-title">Manage Staff</h6>
                                    <p class="action-subtitle">Assign roles & tasks</p>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/HMS/rooms/manage.php?hall_id=<?php echo $hallId; ?>" class="action-card">
                                    <div class="action-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-bed"></i>
                                    </div>
                                    <h6 class="action-title">Room Allocation</h6>
                                    <p class="action-subtitle">Assign & update rooms</p>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/HMS/maintenance/requests.php?hall_id=<?php echo $hallId; ?>" class="action-card">
                                    <div class="action-icon bg-danger bg-opacity-10 text-danger">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                    <h6 class="action-title">Maintenance</h6>
                                    <p class="action-subtitle">View & assign tasks</p>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/HMS/dashboard/notice_management.php" class="action-card">
                                    <div class="action-icon bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-bullhorn"></i>
                                    </div>
                                    <h6 class="action-title">Notices</h6>
                                    <p class="action-subtitle">Post announcements</p>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="/HMS/events/schedule.php?hall_id=<?php echo $hallId; ?>" class="action-card">
                                    <div class="action-icon bg-secondary bg-opacity-10 text-secondary">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <h6 class="action-title">Events</h6>
                                    <p class="action-subtitle">Schedule & manage</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Live clock functionality
        function updateClock() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';

            hours = hours % 12;
            hours = hours ? hours : 12; // Convert hour '0' to '12'
            const timeString = hours + ':' + minutes + ' ' + ampm;

            const clockElement = document.getElementById('current-time');
            if (clockElement) {
                clockElement.textContent = timeString;
            }
        }

        // Update the clock immediately and then every second
        updateClock();
        setInterval(updateClock, 1000);

        // Filter activities
        const filterButtons = document.querySelectorAll('[data-filter]');
        const activityItems = document.querySelectorAll('.activity-item');

        filterButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const filter = this.getAttribute('data-filter');

                activityItems.forEach(item => {
                    if (filter === 'all') {
                        item.style.display = 'block';
                    } else if (filter === 'pending') {
                        if (item.getAttribute('data-status') === 'pending') {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    } else {
                        if (item.getAttribute('data-type') === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    }
                });
            });
        });

        // Add notification badge for pending requests
        const pendingCount = <?php echo $pendingRequests; ?>;
        if (pendingCount > 0) {
            const requestsButton = document.querySelector('a[href*="all.php"]');
            if (requestsButton) {
                const badge = document.createElement('span');
                badge.className = 'notification-badge';
                badge.textContent = pendingCount;
                requestsButton.style.position = 'relative';
                requestsButton.appendChild(badge);
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>