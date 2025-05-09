<?php

/**
 * Header template for the HMS (Hostel Management System)
 * 
 * Includes all necessary styles, scripts, and navigation elements
 */

// Function to display alert messages using SweetAlert2
function showAlert($title, $message, $type)
{
    if (is_array($message)) {
        $messageHtml = '<ul style="list-style-type: none; margin: 0; padding: 0; text-align: left;">';
        foreach ($message as $msg) {
            $messageHtml .= "<li>" . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . "</li>";
        }
        $messageHtml .= '</ul>';
        echo "Swal.fire({
            title: '" . addslashes($title) . "',
            html: '" . addslashes($messageHtml) . "',
            icon: '" . addslashes($type) . "',
            confirmButtonText: 'OK'
        });";
    } else {
        echo "Swal.fire({
            title: '" . addslashes($title) . "',
            text: '" . addslashes($message) . "',
            icon: '" . addslashes($type) . "',
            confirmButtonText: 'OK'
        });";
    }
}

// Determine dashboard link based on role - using a safer approach than match for backward compatibility
function getDashboardLink($role)
{
    switch ($role) {
        case 'student':
            return '/HMS/dashboard/student.php';
        case 'admin':
            return '/HMS/dashboard/admin.php';
        case 'staff':
            return '/HMS/dashboard/staff.php';
        case 'provost':
            return '/HMS/dashboard/provost.php';
        default:
            return '/HMS/index.php';
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title><?= isset($title) ? htmlspecialchars($title) : 'Hostel Management System' ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">

    <!-- Bootstrap JS (added - was missing) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/gif" href="/HMS/assets/images/duet-logo.png" />

    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <!-- Quill Editor (consolidated to single version) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/HMS/assets/css/style.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    <?php
    // Display any flash messages
    if (isset($_SESSION['success'])) {
        echo "<script>";
        showAlert('Success', $_SESSION['success'], 'success');
        echo "</script>";
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['errors'])) {
        echo "<script>";
        showAlert('Error', $_SESSION['errors'], 'error');
        echo "</script>";
        unset($_SESSION['errors']);
    }
    ?>

    <?php if (isset($_SESSION['id'])): ?>
        <nav class="navbar navbar-expand-lg shadow-sm bg-primary navbar-dark">
            <div class="container-fluid">
                <?php
                $dashboardLink = getDashboardLink($_SESSION['role'] ?? '');
                ?>
                <a class="navbar-brand text-white" href="<?= $dashboardLink ?>">
                    <img src="/HMS/assets/images/duet-logo.png" alt="Logo" class="d-inline-block align-text-top me-2">
                    <span class="fw-bold">HMS</span>
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php
                        // Get current path info for active menu highlighting
                        $currentPath = $_SERVER['PHP_SELF'];
                        $currentPage = basename($currentPath);
                        $currentDir = dirname($currentPath);

                        // Student Navigation
                        if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?= $currentPage === 'student.php' && $currentDir === '/HMS/dashboard' ? 'active' : '' ?>" href="/HMS/dashboard/student.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/meals') === 0 ? 'active' : '' ?>" href="/HMS/meals/">Meals</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/bills') === 0 ? 'active' : '' ?>" href="/HMS/bills/">Bills</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= ($currentPage === 'student_notices.php' || $currentPage === 'view_notice.php') && $currentDir === '/HMS/dashboard' ? 'active' : '' ?>" href="/HMS/dashboard/student_notices.php">Notices</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/complaints') === 0 ? 'active' : '' ?>" href="/HMS/complaints/">Complaints</a></li>

                        <?php // Admin Navigation
                        elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?= $currentPage === 'admin.php' && $currentDir === '/HMS/dashboard' ? 'active' : '' ?>" href="/HMS/dashboard/admin.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/users') === 0 ? 'active' : '' ?>" href="/HMS/users/">User Management</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/rooms') === 0 ? 'active' : '' ?>" href="/HMS/rooms/">Room Management</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= $currentPage === 'admin_provost_approvals.php' && strpos($currentDir, '/HMS/actions') === 0 ? 'active' : '' ?>" href="/HMS/actions/admin_provost_approvals.php">Provost Approvals</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/notices') === 0 ? 'active' : '' ?>" href="/HMS/notices/">Notices</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/reports') === 0 ? 'active' : '' ?>" href="/HMS/reports/">Reports</a></li>

                        <?php // Provost Navigation
                        elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'provost'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?= $currentPage === 'provost.php' && $currentDir === '/HMS/dashboard' ? 'active' : '' ?>" href="/HMS/dashboard/provost.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/allocations') === 0 ? 'active' : '' ?>" href="/HMS/allocations/">Room Allocations</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/complaints') === 0 ? 'active' : '' ?>" href="/HMS/complaints/">Complaints</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/notices') === 0 ? 'active' : '' ?>" href="/HMS/notices/">Notices</a></li>

                        <?php // Staff Navigation
                        elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?= $currentPage === 'staff.php' && $currentDir === '/HMS/dashboard' ? 'active' : '' ?>" href="/HMS/dashboard/staff.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/meals/management') === 0 ? 'active' : '' ?>" href="/HMS/meals/management/">Meal Management</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/bills/management') === 0 ? 'active' : '' ?>" href="/HMS/bills/management/">Bill Management</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= strpos($currentDir, '/HMS/notices') === 0 ? 'active' : '' ?>" href="/HMS/notices/">Notices</a></li>
                        <?php endif; ?>
                    </ul>

                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?= htmlspecialchars($_SESSION['display_name'] ?? 'User') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/HMS/profiles/<?php echo htmlspecialchars($_SESSION['role'] ?? 'guest') ?>/"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="/HMS/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <main class="container-fluid py-4">
        <!-- Content will be injected here by individual pages -->