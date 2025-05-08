<?php
$title = 'Staff Management';
require_once '../config/database.php';
require_once '../includes/Session.php';
require_once '../models/Staff.php';

Session::init();

// Check if user is logged in and has provost role
if (!Session::isLoggedIn() || !Session::hasPermission('provost')) {
    header('Location: /HMS/');
    exit();
}

// Initialize variables
$success_message = $error_message = '';
$provostHall = '';
$Hall_id = null;

try {
    // Get provost's assigned hall
    $provostSlug = Session::get('slug');
    $query = "SELECT hall_id FROM provost_approvals WHERE slug = :provostSlug";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(":provostSlug", $provostSlug, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $Hall_id = $result ? $result['hall_id'] : null;

    if ($Hall_id) {
        $query = "SELECT name FROM halls WHERE id = :hall_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":hall_id", $Hall_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $provostHall = $result ? $result['name'] : '';
    }

    if (!$provostHall) {
        throw new Exception('Could not determine your assigned hall. Please contact the administrator.');
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $staff = new Staff($conn);

        // Validate and sanitize input
        $fullName = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $workingRole = trim($_POST['working_role']);
        $phoneNumber = trim($_POST['phone_number']);
        $joiningDate = trim($_POST['joining_date']);
        $workingHall = $provostHall;

        // Create the staff profile
        $staffData = [
            'slug' => $staff->generateSlug(),
            'full_name' => $fullName,
            'email' => $email,
            'working_hall' => $workingHall,
            'working_role' => $workingRole,
            'phone_number' => $phoneNumber,
            'joining_date' => $joiningDate
        ];

        try {
            if ($staff->createStaffProfile($staffData)) {
                $success_message = 'Staff member added successfully!';
                // Clear form data after successful submission
                $_POST = array();
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

require_once '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Add New Staff Member</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                    required>
                                <div class="invalid-feedback">Please provide a full name.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                    required>
                                <div class="invalid-feedback">Please provide a valid email.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="working_hall" class="form-label">Working Hall</label>
                                <input type="text" class="form-control" id="working_hall" name="working_hall"
                                    value="<?php echo htmlspecialchars($provostHall); ?>" readonly>
                                <small class="text-muted">Staff will be assigned to your hall automatically</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="working_role" class="form-label">Working Role <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="working_role" name="working_role"
                                    value="<?php echo isset($_POST['working_role']) ? htmlspecialchars($_POST['working_role']) : ''; ?>"
                                    required>
                                <div class="invalid-feedback">Please provide a working role.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                    value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                                <div class="invalid-feedback">Please provide a valid phone number.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="joining_date" class="form-label">Joining Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="joining_date" name="joining_date"
                                    value="<?php echo isset($_POST['joining_date']) ? htmlspecialchars($_POST['joining_date']) : ''; ?>"
                                    required>
                                <div class="invalid-feedback">Please provide a joining date.</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Add Staff Member</button>
                            <button type="reset" class="btn btn-outline-secondary">Reset Form</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Client-side form validation
    (function() {
        'use strict'

        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        var forms = document.querySelectorAll('.needs-validation')

        // Loop over them and prevent submission
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }

                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>

<?php require_once '../includes/footer.php'; ?>