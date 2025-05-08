<?php
$title = 'Provost Dashboard';
require_once '../includes/Session.php';
require_once '../models/ProvostApproval.php';
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

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <?php if ($approvalStatus['status'] !== 'approved'): ?>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="card-title mb-4">
                            <?php if ($approvalStatus['status'] === 'pending'): ?>
                                <i class="fas fa-clock text-warning"></i> Approval Pending
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i> Access Restricted
                            <?php endif; ?>
                        </h3>
                        <p class="lead">
                            <?php if ($approvalStatus['status'] === 'pending'): ?>
                                Your provost account is pending administrative approval. Please wait for an administrator to review your account.
                            <?php else: ?>
                                Your provost access request has been rejected.
                                <?php if ($approvalStatus['rejection_reason']): ?>
                                    <br><small class="text-muted">Reason: <?php echo htmlspecialchars($approvalStatus['rejection_reason']); ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Provost Dashboard Content -->
        <div class="row mb-4">
            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Students</h6>
                                <h3 class="mb-0">250</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-users text-primary fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Available Rooms</h6>
                                <h3 class="mb-0">45</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-door-open text-success fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Pending Requests</h6>
                                <h3 class="mb-0">12</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-clock text-warning fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Staff Management</h6>
                                <a href="/HMS/actions/staff_management.php" class="btn btn-primary mt-2">
                                    <i class="fas fa-user-plus"></i> Add Staff
                                </a>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-users-cog text-info fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">Total Staff</h6>
                                <h3 class="mb-0">15</h3>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-user-tie text-info fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Activities -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Activity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>2024-01-15</td>
                                        <td>John Doe</td>
                                        <td>Room Change Request</td>
                                        <td><span class="badge bg-warning">Pending</span></td>
                                    </tr>
                                    <tr>
                                        <td>2024-01-14</td>
                                        <td>Jane Smith</td>
                                        <td>Leave Application</td>
                                        <td><span class="badge bg-success">Approved</span></td>
                                    </tr>
                                    <tr>
                                        <td>2024-01-14</td>
                                        <td>Mike Johnson</td>
                                        <td>Maintenance Request</td>
                                        <td><span class="badge bg-info">In Progress</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="#" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-2"></i>Add New Student
                            </a>
                            <a href="#" class="btn btn-success">
                                <i class="fas fa-bed me-2"></i>Manage Rooms
                            </a>
                            <a href="#" class="btn btn-info">
                                <i class="fas fa-calendar-alt me-2"></i>Schedule Events
                            </a>
                            <a href="#" class="btn btn-warning">
                                <i class="fas fa-exclamation-circle me-2"></i>View Complaints
                            </a>
                            <a href="/HMS/dashboard/notice_management.php" class="btn btn-info">
                                <i class="fas fa-bullhorn me-2"></i>Manage Notices
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>