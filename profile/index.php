<?php
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Student.php';
require_once '../models/Location.php';
require_once '../includes/Session.php';

Session::init();

if (!isset($_SESSION['user_id'])) {
    header('Location: /HMS/');
    exit();
}

$user = new User($conn);

function is_2d_array($array)
{
    if (!is_array($array)) {
        return false;
    }
    foreach ($array as $value) {
        if (is_array($value)) {
            return true;
        }
    }
    return false;
}

if (is_2d_array($_SESSION)) {
    $id = $_SESSION['user_id']['id'];
} else {
    $id = $_SESSION['user_id'];
}

$userData = $user->getUserById($id);


if ($userData['role'] === 'student') {
    $student = new Student($conn);
    $studentData = $student->getByUserId($id);

    $location = new Location($conn);
    $divisions = $location->getAllDivisions();

    if (!empty($studentData['division_id'])) {
        $districts = $location->getDistrictsByDivision($studentData['division_id']);
        if (!empty($studentData['district_id'])) {
            $upazilas = $location->getUpazilasByDistrict($studentData['district_id']);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $data = [
                'student_id' => $_POST['student_id'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'department' => $_POST['department'] ?? '',
                'year' => $_POST['year'] ?? '',
                'semester' => $_POST['semester'] ?? '',
                'phone_number' => $_POST['phone_number'] ?? '',
                'division_id' => $_POST['division_id'] ?? null,
                'district_id' => $_POST['district_id'] ?? null,
                'upazila_id' => $_POST['upazila_id'] ?? null,
                'village_area' => $_POST['village_area'] ?? '',
                'guardian_name' => $_POST['guardian_name'] ?? '',
                'guardian_phone' => $_POST['guardian_phone'] ?? '',
                'room_number' => $_POST['room_number'] ?? ''
            ];

            // Validate required fields
            $errors = [];
            foreach ($data as $key => $value) {
                if (empty($value)) {
                    $errors[] = ucfirst(str_replace('_', ' ', $key)) . ' is required.';
                }
            }

            // Validate phone number format
            if (!empty($data['phone_number']) && !preg_match('/^\+?[0-9]{10,13}$/', $data['phone_number'])) {
                $errors[] = 'Invalid phone number format.';
            }

            // Validate guardian phone number format
            if (!empty($data['guardian_phone']) && !preg_match('/^\+?[0-9]{10,13}$/', $data['guardian_phone'])) {
                $errors[] = 'Invalid guardian phone number format.';
            }

            if (empty($errors)) {
                if ($student->updateProfile($id, $data)) {
                    $_SESSION['success'] = 'Profile updated successfully.';
                    header('Location: /HMS/dashboard/student.php');
                    exit();
                } else {
                    throw new Exception('Failed to update profile.');
                }
            }
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
        }
    }
}

require_once '../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="card-title text-center mb-4">Update Profile</h4>

                    <script>
                        function fetchDistricts(divisionId) {
                            if (!divisionId) return;
                            fetch(`/HMS/api/locations.php?type=districts&division_id=${divisionId}`)
                                .then(response => response.json())
                                .then(data => {
                                    const districtSelect = document.getElementById('district_id');
                                    districtSelect.innerHTML = '<option value="">Select District</option>';
                                    data.forEach(district => {
                                        districtSelect.innerHTML += `<option value="${district.id}">${district.name} (${district.bn_name})</option>`;
                                    });
                                    if (document.getElementById('current_district_id').value) {
                                        districtSelect.value = document.getElementById('current_district_id').value;
                                        fetchUpazilas(document.getElementById('current_district_id').value);
                                    }
                                });
                        }

                        function fetchUpazilas(districtId) {
                            if (!districtId) return;
                            fetch(`/HMS/api/locations.php?type=upazilas&district_id=${districtId}`)
                                .then(response => response.json())
                                .then(data => {
                                    const upazilaSelect = document.getElementById('upazila_id');
                                    upazilaSelect.innerHTML = '<option value="">Select Upazila</option>';
                                    data.forEach(upazila => {
                                        upazilaSelect.innerHTML += `<option value="${upazila.id}">${upazila.name} (${upazila.bn_name})</option>`;
                                    });
                                    if (document.getElementById('current_upazila_id').value) {
                                        upazilaSelect.value = document.getElementById('current_upazila_id').value;
                                    }
                                });
                        }
                    </script>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['errors'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php
                                foreach ($_SESSION['errors'] as $error) {
                                    echo "<li>$error</li>";
                                }
                                unset($_SESSION['errors']);
                                ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <script>
                        // Client-side validation
                        (function() {
                            'use strict';
                            window.addEventListener('load', function() {
                                var forms = document.getElementsByClassName('needs-validation');
                                var validation = Array.prototype.filter.call(forms, function(form) {
                                    form.addEventListener('submit', function(event) {
                                        if (form.checkValidity() === false) {
                                            event.preventDefault();
                                            event.stopPropagation();
                                        }
                                        form.classList.add('was-validated');
                                    }, false);
                                });
                            }, false);
                        })();
                    </script>

                    <?php if ($_SESSION['role'] === 'student'): ?>
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <input type="hidden" id="current_district_id" value="<?php echo $studentData['district_id'] ?? ''; ?>">
                            <input type="hidden" id="current_upazila_id" value="<?php echo $studentData['upazila_id'] ?? ''; ?>">
                            <div class="row">
                                <!-- Personal Information Section -->
                                <h5 class="mb-3">Personal Information</h5>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Student ID</label>
                                    <input type="text" class="form-control" name="student_id" value="<?php echo explode('@', $userData['email'])[0]; ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" value="<?php echo $studentData['full_name'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo $userData['email'] ?? ''; ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone_number" value="<?php echo $studentData['phone_number'] ?? ''; ?>" required>
                                </div>

                                <!-- Academic Information Section -->
                                <h5 class="mb-3 mt-4">Academic Information</h5>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department" required>
                                        <option value="">Select Department</option>
                                        <option value="CSE" <?php echo ($studentData['department'] ?? '') === 'CSE' ? 'selected' : ''; ?>>Computer Science & Engineering</option>
                                        <option value="EEE" <?php echo ($studentData['department'] ?? '') === 'EEE' ? 'selected' : ''; ?>>Electrical & Electronic Engineering</option>
                                        <option value="ME" <?php echo ($studentData['department'] ?? '') === 'ME' ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                        <option value="CE" <?php echo ($studentData['department'] ?? '') === 'CE' ? 'selected' : ''; ?>>Civil Engineering</option>
                                        <option value="TE" <?php echo ($studentData['department'] ?? '') === 'TE' ? 'selected' : ''; ?>>Textile Engineering</option>
                                        <option value="IPE" <?php echo ($studentData['department'] ?? '') === 'IPE' ? 'selected' : ''; ?>>Industrial & Production Engineering</option>
                                        <option value="Arch" <?php echo ($studentData['department'] ?? '') === 'Arch' ? 'selected' : ''; ?>>Architecture</option>
                                        <option value="MME" <?php echo ($studentData['department'] ?? '') === 'MME' ? 'selected' : ''; ?>>Material & Metallurgical Engineering</option>
                                        <option value="ChE" <?php echo ($studentData['department'] ?? '') === 'ChE' ? 'selected' : ''; ?>>Chemical Engineering</option>
                                        <option value="FE" <?php echo ($studentData['department'] ?? '') === 'FE' ? 'selected' : ''; ?>>Food Engineering</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Year</label>
                                    <select class="form-select" name="year" required>
                                        <option value="">Select Year</option>
                                        <option value="1" <?php echo ($studentData['year'] ?? '') == 1 ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2" <?php echo ($studentData['year'] ?? '') == 2 ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3" <?php echo ($studentData['year'] ?? '') == 3 ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4" <?php echo ($studentData['year'] ?? '') == 4 ? 'selected' : ''; ?>>4th Year</option>
                                        <option value="5" <?php echo ($studentData['year'] ?? '') == 5 ? 'selected' : ''; ?>>5th Year</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Semester</label>
                                    <select class="form-select" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="First" <?php echo ($studentData['semester'] ?? '') == 'First' ? 'selected' : ''; ?>>1st Semester</option>
                                        <option value="Second" <?php echo ($studentData['semester'] ?? '') == 'Second' ? 'selected' : ''; ?>>2nd Semester</option>
                                    </select>
                                </div>

                                <!-- Address Section -->
                                <h5 class="mb-3 mt-4">Address Information</h5>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Division</label>
                                    <select class="form-select" name="division_id" id="division_id" required onchange="fetchDistricts(this.value)">
                                        <option value="">Select Division</option>
                                        <?php foreach ($divisions as $division): ?>
                                            <option value="<?php echo $division['id']; ?>" <?php echo ($studentData['division_id'] ?? '') == $division['id'] ? 'selected' : ''; ?>>
                                                <?php echo $division['name']; ?> (<?php echo $division['bn_name']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">District</label>
                                    <select class="form-select" name="district_id" id="district_id" required onchange="fetchUpazilas(this.value)">
                                        <option value="">Select District</option>
                                        <?php if (!empty($districts)): foreach ($districts as $district): ?>
                                                <option value="<?php echo $district['id']; ?>" <?php echo ($studentData['district_id'] ?? '') == $district['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $district['name']; ?>
                                                </option>
                                        <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Upazila</label>
                                    <select class="form-select" name="upazila_id" id="upazila_id" required>
                                        <option value="">Select Upazila</option>
                                        <?php if (!empty($upazilas)): foreach ($upazilas as $upazila): ?>
                                                <option value="<?php echo $upazila['id']; ?>" <?php echo ($studentData['upazila_id'] ?? '') == $upazila['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $upazila['name']; ?>
                                                </option>
                                        <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Village/Area</label>
                                    <input type="text" class="form-control" name="village_area" value="<?php echo $studentData['village_area'] ?? ''; ?>" required>
                                </div>

                                <!-- Guardian Information -->
                                <h5 class="mb-3 mt-4">Guardian Information</h5>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" class="form-control" name="guardian_name" value="<?php echo $studentData['guardian_name'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Guardian Phone</label>
                                    <input type="tel" class="form-control" name="guardian_phone" value="<?php echo $studentData['guardian_phone'] ?? ''; ?>" required>
                                </div>

                                <!-- Room Information -->
                                <h5 class="mb-3 mt-4">Room Information</h5>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Room Number</label>
                                    <input type="text" class="form-control" name="room_number" value="<?php echo $studentData['room_number'] ?? ''; ?>" required>
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>