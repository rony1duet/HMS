<?php
$title = 'Student Dashboard';
require_once '../config/database.php';
require_once '../includes/Session.php';
require_once '../models/User.php';

Session::init();
$user = new User($conn);

$profileStatus = $user->getProfileStatus($_SESSION['slug']);

if (!Session::isLoggedIn() || !Session::hasPermission('student')) {
    header('Location: /HMS/');
    exit();
}

try {
    $profileStatus = $user->getProfileStatus($_SESSION['slug'] ?? '');

    // Redirect if profile needs updating
    if ($profileStatus === 'not_updated') {
        header('Location: /HMS/profiles/student/');
        exit();
    }
} catch (Exception $e) {
    // Log the error and redirect to error page or show message
    error_log("Profile status check failed: " . $e->getMessage());
    header('Location: /HMS/error.php');
    exit();
}


require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Room Details Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">My Room Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Room Number</h6>
                            <p class="h5">201</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Bed Number</h6>
                            <p class="h5">A</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <h6 class="text-muted">Room Type</h6>
                            <p class="h5">Double Sharing</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Room Overview Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Room Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Room Details</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Room Number:</span>
                                    <strong>201</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Floor:</span>
                                    <strong>2nd</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Type:</span>
                                    <strong>Double Sharing</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="text-muted mb-2">Facilities</h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="fas fa-wifi me-2 text-primary"></i> Wi-Fi
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-fan me-2 text-primary"></i> Fan
                                    </li>
                                    <li>
                                        <i class="fas fa-plug me-2 text-primary"></i> Power Backup
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Summary -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Activity Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <i class="fas fa-utensils fa-2x text-primary mb-2"></i>
                                <h3 class="mb-1">45</h3>
                                <p class="text-muted mb-0">Meals This Month</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <i class="fas fa-exclamation-circle fa-2x text-warning mb-2"></i>
                                <h3 class="mb-1">2</h3>
                                <p class="text-muted mb-0">Active Complaints</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                <h3 class="mb-1">12</h3>
                                <p class="text-muted mb-0">Events Attended</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notices & Announcements -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Notices & Announcements</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Hall Week Celebration</h6>
                                <small class="text-muted">3 days ago</small>
                            </div>
                            <p class="mb-1">Annual hall week celebration starts from next Monday.</p>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Maintenance Schedule</h6>
                                <small class="text-muted">1 week ago</small>
                            </div>
                            <p class="mb-1">Regular maintenance work scheduled for this weekend.</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Hall Dues Overview -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Hall Dues Overview</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Hall Rent</span>
                            <span class="fw-bold">৳5,000</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Mess Bill</span>
                            <span class="fw-bold">৳3,500</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Other Charges</span>
                            <span class="fw-bold">৳500</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">Total Due</span>
                            <span class="fw-bold text-danger">৳9,000</span>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100">Pay Now</button>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="../complaints/" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Submit Complaint
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="../meals/" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            View Menu
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <a href="../profile/" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Update Profile
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>