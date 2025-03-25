<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/HMS/assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">
    <?php if (isset($_SESSION['user_id'])): ?>
        <nav class="navbar navbar-expand-lg shadow-sm bg-primary navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand text-white" href="<?php echo $_SESSION['role'] === 'student' ? '/HMS/dashboard/student.php' : ($_SESSION['role'] === 'admin' ? '/HMS/dashboard/admin.php' : '/HMS/dashboard/staff.php'); ?>">
                    <img src="/HMS/assets/images/duet-logo.png" alt="Logo" class="d-inline-block align-text-top me-2">
                    <span class="fw-bold">HMS</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php if ($_SESSION['role'] === 'student'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'student.php' ? 'active' : ''; ?>" href="/HMS/dashboard/student.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' && strpos($_SERVER['PHP_SELF'], '/meals/') !== false ? 'active' : ''; ?>" href="/HMS/meals/">Meals</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'bills.php' ? 'active' : ''; ?>" href="/HMS/bills/">Bills</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'complaints.php' ? 'active' : ''; ?>" href="/HMS/complaints/">Complaints</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'notices.php' ? 'active' : ''; ?>" href="/HMS/notices/">Notices</a></li>
                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : ''; ?>" href="/HMS/dashboard/admin.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : ''; ?>" href="/HMS/students/">Students</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : ''; ?>" href="/HMS/staff/">Staff</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : ''; ?>" href="/HMS/meals/admin.php">Meals</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'finance.php' ? 'active' : ''; ?>" href="/HMS/finance/">Finance</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : ''; ?>" href="/HMS/complaints/admin.php">Complaints</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="/HMS/reports/">Reports</a></li>
                        <?php elseif ($_SESSION['role'] === 'staff'): ?>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : ''; ?>" href="/HMS/dashboard/staff.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'staff.php' ? 'active' : ''; ?>" href="/HMS/meals/staff.php">Meals</a></li>
                            <li class="nav-item"><a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'maintenance.php' ? 'active' : ''; ?>" href="/HMS/maintenance/">Maintenance</a></li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?php echo $_SESSION['display_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/HMS/profile/"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
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