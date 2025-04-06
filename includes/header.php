<?php
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
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title><?= $title ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">

    <!-- Favicon -->
    <link rel="icon" type="image/gif" href="assets/images/duet-logo.png" />

    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/HMS/assets/css/style.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">
    <?php
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

    <?php if (isset($_SESSION['user_id'])): ?>
        <nav class="navbar navbar-expand-lg shadow-sm bg-primary navbar-dark">
            <div class="container-fluid">
                <?php
                $dashboardLink = match ($_SESSION['role']) {
                    'student' => '/HMS/dashboard/student.php',
                    'admin' => '/HMS/dashboard/admin.php',
                    'staff' => '/HMS/dashboard/staff.php',
                    'provost' => '/HMS/dashboard/provost.php',
                };
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
                        <?php if ($_SESSION['role'] === 'student'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'student.php' ? 'active' : '' ?>" href="/HMS/dashboard/student.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'index.php' && str_contains($_SERVER['PHP_SELF'], '/meals/') ? 'active' : '' ?>" href="/HMS/meals/">Meals</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'bills.php' ? 'active' : '' ?>" href="/HMS/bills/">Bills</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active' : '' ?>" href="/HMS/complaints/">Complaints</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'notices.php' ? 'active' : '' ?>" href="/HMS/notices/">Notices</a></li>
                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : '' ?>" href="/HMS/dashboard/admin.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="/HMS/users/">User Management</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'rooms.php' ? 'active' : '' ?>" href="/HMS/rooms/">Room Management</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'admin_provost_approvals.php' ? 'active' : '' ?>" href="/HMS/dashboard/admin_provost_approvals.php">Provost Approvals</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'notices.php' ? 'active' : '' ?>" href="/HMS/notices/">Notices</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>" href="/HMS/reports/">Reports</a></li>
                        <?php elseif ($_SESSION['role'] === 'provost'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'provost.php' ? 'active' : '' ?>" href="/HMS/dashboard/provost.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'allocations.php' ? 'active' : '' ?>" href="/HMS/allocations/">Room Allocations</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active' : '' ?>" href="/HMS/complaints/">Complaints</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'notices.php' ? 'active' : '' ?>" href="/HMS/notices/">Notices</a></li>
                        <?php elseif ($_SESSION['role'] === 'staff'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : '' ?>" href="/HMS/dashboard/staff.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'meals.php' ? 'active' : '' ?>" href="/HMS/meals/management/">Meal Management</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'bills.php' ? 'active' : '' ?>" href="/HMS/bills/management/">Bill Management</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?= basename($_SERVER['PHP_SELF']) === 'notices.php' ? 'active' : '' ?>" href="/HMS/notices/">Notices</a></li>
                        <?php endif; ?>
                    </ul>

                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?= $_SESSION['display_name'] ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/HMS/profiles/student/"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
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