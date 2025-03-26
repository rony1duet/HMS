<?php
session_start();
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

// Redirect if not logged in as student
if (!Session::isLoggedIn() || Session::getUserRole() !== 'student') {
    header('Location: /HMS/');
    exit();
}

// Constants
const MIN_RECHARGE_AMOUNT = 50;

// Process recharge request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token';
        header('Location: recharge.php');
        exit();
    }

    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);

    if ($amount && $amount >= MIN_RECHARGE_AMOUNT) {
        try {
            $conn->beginTransaction();

            // Get student profile in one query
            $student_id = $conn->query(
                "SELECT id FROM student_profiles WHERE user_id = " .
                    $conn->quote(Session::getUserId())
            )->fetchColumn();

            if (!$student_id) {
                throw new Exception('Student profile not found');
            }

            // Ensure credit record exists
            $conn->exec(
                "INSERT IGNORE INTO student_meal_credits (student_id, credits) 
                 VALUES ($student_id, 0)"
            );

            // Update credits
            $conn->exec(
                "UPDATE student_meal_credits 
                 SET credits = credits + $amount, 
                     last_recharge = NOW() 
                 WHERE student_id = $student_id"
            );

            $conn->commit();
            $_SESSION['success_message'] = "Successfully added ৳$amount to your meal credits!";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Please enter a valid amount (minimum ৳" . MIN_RECHARGE_AMOUNT . ")";
    }

    header('Location: recharge.php');
    exit();
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Recharge Meal Credits</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                            <?php unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_SESSION['success_message']) ?>
                            <?php unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Session::getCsrfToken() ?>">

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (in Taka)</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control" id="amount" name="amount"
                                    min="<?= MIN_RECHARGE_AMOUNT ?>" step="50" required>
                            </div>
                            <div class="form-text">Minimum recharge amount: ৳<?= MIN_RECHARGE_AMOUNT ?></div>
                        </div>

                        <div class="mb-3">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary quick-amount" data-amount="50">৳50</button>
                                <button type="button" class="btn btn-outline-primary quick-amount" data-amount="100">৳100</button>
                                <button type="button" class="btn btn-outline-primary quick-amount" data-amount="500">৳500</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Recharge Now</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.quick-amount').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('amount').value = btn.dataset.amount;
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>