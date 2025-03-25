<?php
session_start();
require_once '../config/database.php';
require_once '../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || Session::getUserRole() !== 'student') {
    header('Location: /HMS/');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_INT);
    $student_id = Session::getUserId();

    if ($amount && $amount > 0) {
        // Add credits to student's meal account
        try {
            $conn->beginTransaction();

            // First verify if student profile exists
            $check_profile_sql = "SELECT id FROM student_profiles WHERE user_id = :user_id";
            $check_profile_stmt = $conn->prepare($check_profile_sql);
            $check_profile_stmt->bindValue(':user_id', $student_id, PDO::PARAM_INT);
            $check_profile_stmt->execute();
            $student_profile = $check_profile_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$student_profile) {
                throw new PDOException('Student profile not found');
            }

            $student_profile_id = $student_profile['id'];

            // First check if student has a meal credit record and get current balance
            $check_sql = "SELECT id, credits FROM student_meal_credits WHERE student_id = :student_id FOR UPDATE";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bindValue(':student_id', $student_profile_id, PDO::PARAM_INT);
            $check_stmt->execute();
            $current_record = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current_record) {
                // Create new record if doesn't exist
                $insert_sql = "INSERT INTO student_meal_credits (student_id, credits) VALUES (:student_id, 0)";
                $insert_stmt = $conn->prepare($insert_sql);
                $insert_stmt->bindValue(':student_id', $student_profile_id, PDO::PARAM_INT);
                $insert_stmt->execute();
                $current_balance = 0;
            } else {
                $current_balance = $current_record['credits'];
            }

            // Update credits
            $sql = "UPDATE student_meal_credits 
                    SET credits = credits + :amount, 
                        last_recharge = NOW() 
                    WHERE student_id = :student_id";

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
            $stmt->bindValue(':student_id', $student_profile_id, PDO::PARAM_INT);

            if ($stmt->execute()) {
                $conn->commit();
                $_SESSION['success_message'] = 'Credits added successfully!';
            } else {
                throw new PDOException('Failed to update credits');
            }
        } catch (PDOException $e) {
            $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
            $conn->rollBack();
            $_SESSION['error_message'] = 'Failed to add credits. Please try again.';
        }

        header('Location: index.php');
        exit();
    } else {
        $_SESSION['error_message'] = 'Please enter a valid amount.';
    }
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
                            <?php
                            echo $_SESSION['error_message'];
                            unset($_SESSION['error_message']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount (in Taka)</label>
                            <div class="input-group">
                                <span class="input-group-text">৳</span>
                                <input type="number" class="form-control" id="amount" name="amount" min="100" step="100" required>
                            </div>
                            <div class="form-text">Minimum recharge amount: ৳100</div>
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div class="mb-3">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary quick-amount" data-amount="500">৳500</button>
                                <button type="button" class="btn btn-outline-primary quick-amount" data-amount="1000">৳1000</button>
                                <button type="button" class="btn btn-outline-primary quick-amount" data-amount="2000">৳2000</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Recharge Now</button>
                    </form>
                </div>
            </div>

            <!-- Payment Instructions -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Payment Instructions</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li>Enter the amount you want to recharge</li>
                        <li>Click on "Recharge Now"</li>
                        <li>You will be redirected to the payment gateway</li>
                        <li>Complete the payment using your preferred method</li>
                        <li>Credits will be added to your account instantly</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quick amount selection
        const quickAmountButtons = document.querySelectorAll('.quick-amount');
        const amountInput = document.getElementById('amount');

        quickAmountButtons.forEach(button => {
            button.addEventListener('click', function() {
                const amount = this.dataset.amount;
                amountInput.value = amount;
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>