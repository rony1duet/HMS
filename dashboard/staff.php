<?php
$title = 'Staff Dashboard';
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::hasPermission('staff')) {
    header('Location: /HMS/');
    exit();
}

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Room Management</h5>
                    <p class="card-text">Manage room assignments and maintenance status.</p>
                    <a href="staff/rooms.php" class="btn btn-primary">Manage Rooms</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Maintenance Requests</h5>
                    <p class="card-text">View and handle maintenance requests from students.</p>
                    <a href="staff/maintenance.php" class="btn btn-primary">View Requests</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Student Complaints</h5>
                    <p class="card-text">Manage and respond to student complaints.</p>
                    <a href="staff/complaints.php" class="btn btn-primary">View Complaints</a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">Profile Settings</h5>
                    <p class="card-text">Update your profile and account settings.</p>
                    <a href="staff/profile.php" class="btn btn-primary">View Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>