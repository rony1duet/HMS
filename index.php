<?php
$title = "DUET HMS | Login to continue";
require_once 'config/database.php';
require_once 'includes/Session.php';
require_once 'includes/header.php';

Session::init();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['id'])) {
    header('Location: /HMS/dashboard/' . $_SESSION['role'] . '.php');
    exit();
}
?>

<main class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <!-- Compact Card Header -->
                    <div class="card-header bg-primary text-white py-3">
                        <div class="text-center">
                            <img src="assets/images/duet-logo.png" alt="DUET Logo" width="40" class="mb-1">
                            <h5 class="mb-0 mt-1">DUET Hall Management System</h5>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['error'])): ?>
                            <script>
                                <?php
                                showAlert('Error', $_SESSION['error'], 'error');
                                unset($_SESSION['error']);
                                ?>
                            </script>
                        <?php endif; ?>

                        <!-- Compact Role Selection -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-center gap-2">
                                <a href="auth/google_login.php?role=<?= 'provost'; ?>" class="btn btn-outline-primary btn-sm flex-grow-1 py-2">
                                    <i class="fab fa-google me-2"></i>Provost
                                </a>
                                <a href="auth/microsoft_login.php" class="btn btn-outline-primary btn-sm flex-grow-1 py-2">
                                    <i class="fab fa-microsoft me-2"></i>Student
                                </a>
                                <a href="auth/google_login.php?role=<?= 'staff'; ?>" class="btn btn-outline-primary btn-sm flex-grow-1 py-2">
                                    <i class="fas fa-user-tie me-2"></i>Staff
                                </a>
                            </div>
                        </div>

                        <!-- Compact Divider -->
                        <div class="position-relative my-3">
                            <hr class="my-2">
                            <div class="position-absolute top-50 start-50 translate-middle bg-white px-2 text-muted small">
                                OR
                            </div>
                        </div>

                        <!-- Compact Admin Login Form -->
                        <div>
                            <h6 class="text-center text-primary mb-3">Admin Login</h6>

                            <form action="auth/admin_login.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                <div class="mb-3">
                                    <label class="form-label small text-muted mb-1">Email</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" class="form-control form-control-sm" id="email" name="email" placeholder="admin@duet.ac.bd" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small text-muted mb-1">Password</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" class="form-control form-control-sm" id="password" name="password" placeholder="••••••••" required>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                        <label class="form-check-label small text-muted" for="remember">
                                            Remember me
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm w-100 py-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Compact Footer -->
                    <div class="card-footer bg-light py-2 text-center">
                        <p class="small text-muted mb-0">© <?php echo date('Y'); ?> DUET HMS</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>