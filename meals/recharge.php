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
define('MIN_RECHARGE_AMOUNT', 50);

// Process recharge request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Session::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token';
        header('Location: recharge.php');
        exit();
    }

    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);

    if ($amount !== false && $amount >= MIN_RECHARGE_AMOUNT) {
        try {
            $conn->beginTransaction();

            // Get student profile using slug
            $slug = Session::getSlug();
            $stmt = $conn->prepare("SELECT id FROM student_profiles WHERE slug = :slug");
            $stmt->bindParam(':slug', $slug, PDO::PARAM_STR);
            $stmt->execute();
            $student_id = $stmt->fetchColumn();

            if (!$student_id) {
                // Profile not found - redirect to profile creation
                $_SESSION['error_message'] = 'Please complete your profile first';
                $conn->commit();
                header('Location: /HMS/profiles/student/');
                exit();

                // Redirect to profile page to complete registration
                $_SESSION['warning_message'] = 'Please complete your profile information';
                $conn->commit();
                header('Location: /HMS/profile/index.php');
                exit();
            }

            // Ensure credit record exists
            $stmt = $conn->prepare(
                "INSERT IGNORE INTO student_meal_credits (student_id, credits) 
                 VALUES (:student_id, 0)"
            );
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();

            // Get current balance
            $stmt = $conn->prepare(
                "SELECT credits FROM student_meal_credits 
                 WHERE student_id = :student_id FOR UPDATE"
            );
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();
            $current_credits = $stmt->fetchColumn();
            $current_credits = $current_credits !== false ? (float)$current_credits : 0.0;

            // Update credits
            $new_balance = $current_credits + $amount;
            $stmt = $conn->prepare(
                "UPDATE student_meal_credits 
                 SET credits = :new_balance, 
                     last_recharge = NOW() 
                 WHERE student_id = :student_id"
            );
            $stmt->bindParam(':new_balance', $new_balance, PDO::PARAM_STR);
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->execute();

            // Record transaction
            $stmt = $conn->prepare(
                "INSERT INTO credit_transactions 
                 (student_id, amount, type, balance_after) 
                 VALUES (:student_id, :amount, 'recharge', :balance_after)"
            );
            $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':balance_after', $new_balance, PDO::PARAM_STR);
            $stmt->execute();

            $conn->commit();
            $_SESSION['success_message'] = "Successfully added ৳" . $amount . " to your account.";
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['error_message'] = 'Error processing your request. Please try again.';
            error_log("Recharge Error: " . $e->getMessage());
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['error_message'] = 'An unexpected error occurred.';
            error_log("Recharge Error: " . $e->getMessage());
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
                            <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') ?>
                            <?php unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['success_message'])): ?>
                        <div class="alert alert-success">
                            <?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') ?>
                            <?php unset($_SESSION['success_message']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::getCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">

                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (in Taka)</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control" id="amount" name="amount"
                                    min="<?= MIN_RECHARGE_AMOUNT ?>" step="50" required
                                    value="<?= isset($_POST['amount']) ? htmlspecialchars($_POST['amount'], ENT_QUOTES, 'UTF-8') : '' ?>">
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
                const amountInput = document.getElementById('amount');
                amountInput.value = btn.dataset.amount;
                amountInput.focus();
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>