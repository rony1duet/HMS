<?php
$title = 'Admin Dashboard';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::hasPermission('admin')) {
    header('Location: /HMS/');
    exit();
}

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Hall Overview -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <h6 class="card-title">Total Students</h6>
                            <h2 class="mb-0">450</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <h6 class="card-title">Available Rooms</h6>
                            <h2 class="mb-0">25</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white h-100">
                        <div class="card-body">
                            <h6 class="card-title">Today's Meals</h6>
                            <h2 class="mb-0">380</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <h6 class="card-title">Pending Requests</h6>
                            <h2 class="mb-0">12</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Change Requests -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Room Change Requests</h5>
                    <button class="btn btn-light btn-sm">View All</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Current Room</th>
                                    <th>Requested Room</th>
                                    <th>Reason</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>ST2023001</td>
                                    <td>201</td>
                                    <td>305</td>
                                    <td>Medical Condition</td>
                                    <td>
                                        <button class="btn btn-success btn-sm me-1">Approve</button>
                                        <button class="btn btn-danger btn-sm">Reject</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Provost Approvals -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pending Provost Approvals</h5>
                    <a href="/HMS/actions/admin_provost_approvals.php" class="btn btn-light btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Request Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                require_once '../models/ProvostApproval.php';
                                $provostApproval = new ProvostApproval($conn);
                                $pendingApprovals = $provostApproval->getPendingApprovals();

                                if (empty($pendingApprovals)) {
                                    echo '<tr><td colspan="4" class="text-center">No pending approvals</td></tr>';
                                } else {
                                    foreach (array_slice($pendingApprovals, 0, 5) as $approval) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($approval['display_name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($approval['email']) . '</td>';
                                        echo '<td>' . date('Y-m-d H:i', strtotime($approval['created_at'])) . '</td>';
                                        echo '<td>';
                                        echo '<a href="/HMS/actions/admin_provost_approvals.php" class="btn btn-primary btn-sm">Review</a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Latest Complaints -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Latest Complaints</h5>
                    <button class="btn btn-light btn-sm">View All</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Student</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>#C001</td>
                                    <td>John Doe</td>
                                    <td>Maintenance</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td>2023-08-15</td>
                                    <td><button class="btn btn-primary btn-sm">View</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Recent Payments -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Recent Payments</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Jane Smith</h6>
                                <small class="text-success">৳5,000</small>
                            </div>
                            <p class="mb-1">Hall Rent - August 2023</p>
                            <small class="text-muted">2 hours ago</small>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Mike Johnson</h6>
                                <small class="text-success">৳3,500</small>
                            </div>
                            <p class="mb-1">Mess Bill - August 2023</p>
                            <small class="text-muted">5 hours ago</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Upcoming Events</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Hall Week Celebration</h6>
                                <small class="text-primary">Aug 20</small>
                            </div>
                            <p class="mb-1">Annual cultural program and sports events</p>
                        </div>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Monthly Meeting</h6>
                                <small class="text-primary">Aug 25</small>
                            </div>
                            <p class="mb-1">Staff and hall administration meeting</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary">Add New Student</button>
                        <button class="btn btn-outline-primary">Generate Reports</button>
                        <button class="btn btn-outline-primary">Update Meal Menu</button>
                        <button class="btn btn-outline-primary">Send Announcement</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>