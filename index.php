<?php
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

// Initialize CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'student':
            header('Location: dashboard/student.php');
            break;
        case 'admin':
            header('Location: dashboard/admin.php');
            break;
        case 'provost':
            header('Location: dashboard/provost.php');
            break;
        case 'staff':
            header('Location: dashboard/staff.php');
            break;
    }
    exit();
}
?>

<main class="min-vh-screen max-w-screen-2xl mx-auto d-flex flex-column justify-content-center align-items-center">
    <div class="p-4">
        <div class="w-100" style="max-width: 28rem;">
            <div class="card shadow border rounded overflow-hidden bg-white">
                <div class="card-body p-4">
                    <div class="py-2 mb-2">
                        <div class="text-center">
                            <img src="assets/images/duet-logo.png" alt="DUET Logo" class="img-fluid" style="max-width: 3rem;">
                            <h6 class="h6 mb-1">DUET HMS</h6>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php
                            echo $_SESSION['error'];
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-2 text-center d-flex align-items-center flex-wrap gap-2 justify-content-center mb-4">
                        <a href="auth/google_login.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center text-uppercase">
                            <i class="fab fa-google me-2"></i>
                            <span class="small">Provost Login</span>
                        </a>
                        <a href="auth/microsoft_login.php" class="btn btn-outline-primary d-flex align-items-center justify-content-center text-uppercase">
                            <i class="fab fa-microsoft me-2"></i>
                            <span class="small">Student Login</span>
                        </a>
                    </div>

                    <div class="py-2 mt-4 text-uppercase fw-semibold text-center">
                        <div>Login with User Credentials</div>
                        <div class="small text-muted">Admin only</div>
                    </div>

                    <form action="auth/process_login.php" method="POST" class="p-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-gray-700" for="email">Email</label>
                            <input type="email" class="form-control rounded-3 border-gray-300" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium text-gray-700" for="password">Password</label>
                            <input type="password" class="form-control rounded-3 border-gray-300" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="d-flex align-items-center">
                                <input type="checkbox" class="form-check-input me-2 border-gray-300" id="remember" name="remember">
                                <span class="small text-gray-600">Remember me</span>
                            </label>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary text-uppercase px-2 py-1 rounded-2 fw-semibold small">Log in</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>