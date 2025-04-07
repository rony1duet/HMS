<?php
$title = "DUET HMS | Student Profile";
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../models/Student.php';
require_once '../../models/Location.php';
require_once '../../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || Session::getUserRole() !== 'student') {
    header('Location: /HMS/');
    exit();
}

$user = new User($conn);
$userData = $user->getUserById($_SESSION['id']);
$id = $userData['id'];

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

    // Define halls
    $maleHalls = [
        'Dr. Fazlur Rahman Khan Hall',
        'Shahid Muktijodda Hall',
        'Dr. Qudrat-E-Khuda Hall',
        'Shaheed Tazuddin Ahmad Hall',
        'Kazi Nazrul Islam Hall',
        'Bijoy 24 Hall'
    ];

    $femaleHalls = [
        'Madam Curie Hall'
    ];

    $allHalls = array_merge($maleHalls, $femaleHalls);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $data = [
                'student_id' => $_POST['student_id'] ?? '',
                'full_name' => $_POST['full_name'] ?? '',
                'email' => $userData['email'],
                'date_of_birth' => $_POST['date_of_birth'] ?? '',
                'gender' => $_POST['gender'] ?? '',
                'blood_group' => $_POST['blood_group'] ?? '',
                'department' => $_POST['department'] ?? '',
                'program' => $_POST['program'] ?? '',
                'year' => $_POST['year'] ?? '',
                'semester' => $_POST['semester'] ?? '',
                'phone_number' => $_POST['phone_number'] ?? '',
                'division_id' => $_POST['division_id'] ?? null,
                'district_id' => $_POST['district_id'] ?? null,
                'upazila_id' => $_POST['upazila_id'] ?? null,
                'village_area' => $_POST['village_area'] ?? '',
                'guardian_name' => $_POST['guardian_name'] ?? '',
                'guardian_phone' => $_POST['guardian_phone'] ?? '',
                'hall_name' => $_POST['hall_name'] ?? '',
                'room_number' => $_POST['room_number'] ?? ''
            ];

            // Validate required fields
            $errors = [];
            foreach ($data as $key => $value) {
                if (empty($value)) {
                    $errors[] = ucfirst(str_replace('_', ' ', $key)) . ' is required.';
                }
            }

            // Validate phone numbers
            if (!empty($data['phone_number']) && !preg_match('/^\+?[0-9]{10,13}$/', $data['phone_number'])) {
                $errors[] = 'Invalid phone number format.';
            }

            if (!empty($data['guardian_phone']) && !preg_match('/^\+?[0-9]{10,13}$/', $data['guardian_phone'])) {
                $errors[] = 'Invalid guardian phone number format.';
            }

            // Validate hall selection based on gender
            $selectedGender = $data['gender'] ?? '';
            $selectedHall = $data['hall_name'] ?? '';

            if ($selectedGender === 'Female' && !in_array($selectedHall, $femaleHalls)) {
                $errors[] = 'Female students must select Madam Curie Hall.';
            } elseif ($selectedGender !== 'Female' && in_array($selectedHall, $femaleHalls)) {
                $errors[] = 'Male students cannot select Madam Curie Hall.';
            }

            if (empty($errors)) {
                if ($student->updateProfile($_SESSION['slug'], $data)) {
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

require_once '../../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h4 class="card-title text-center mb-4">Update Profile</h4>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?= $_SESSION['success'];
                            unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['errors'])): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['errors'] as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach;
                                unset($_SESSION['errors']); ?>
                            </ul>
                        </div>
                    <?php endif; ?>

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

                        function updateHallOptions() {
                            const gender = document.querySelector('select[name="gender"]').value;
                            const hallSelect = document.querySelector('select[name="hall_name"]');
                            const currentHall = '<?= $studentData['hall_name'] ?? '' ?>';

                            hallSelect.innerHTML = '<option value="">Select Hall</option>';

                            if (gender === 'Female') {
                                <?php foreach ($femaleHalls as $hall): ?>
                                    hallSelect.innerHTML += `<option value="<?= $hall ?>" <?= ($studentData['hall_name'] ?? '') === $hall ? 'selected' : '' ?>><?= $hall ?></option>`;
                                <?php endforeach; ?>
                            } else {
                                <?php foreach ($maleHalls as $hall): ?>
                                    hallSelect.innerHTML += `<option value="<?= $hall ?>" <?= ($studentData['hall_name'] ?? '') === $hall ? 'selected' : '' ?>><?= $hall ?></option>`;
                                <?php endforeach; ?>
                            }

                            if (currentHall) {
                                hallSelect.value = currentHall;
                            }
                        }

                        // Initialize halls based on current gender
                        document.addEventListener('DOMContentLoaded', function() {
                            updateHallOptions();
                        });
                    </script>

                    <?php if ($_SESSION['role'] === 'student'): ?>
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <input type="hidden" id="current_district_id" value="<?= $studentData['district_id'] ?? '' ?>">
                            <input type="hidden" id="current_upazila_id" value="<?= $studentData['upazila_id'] ?? '' ?>">

                            <!-- Personal Information Section -->
                            <h5 class="mb-3">Personal Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Student ID</label>
                                    <input type="text" class="form-control" name="student_id" value="<?= explode('@', $userData['email'])[0] ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" value="<?= $studentData['full_name'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= $userData['email'] ?? '' ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone_number" value="<?= $studentData['phone_number'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth" value="<?= $studentData['date_of_birth'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender" required onchange="updateHallOptions()">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= ($studentData['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($studentData['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($studentData['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Blood Group</label>
                                    <select class="form-select" name="blood_group" required>
                                        <option value="">Select Blood Group</option>
                                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group): ?>
                                            <option value="<?= $group ?>" <?= ($studentData['blood_group'] ?? '') === $group ? 'selected' : '' ?>><?= $group ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Academic Information Section -->
                            <h5 class="mb-3 mt-4">Academic Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php
                                        $departments = [
                                            'CSE' => 'Computer Science & Engineering',
                                            'EEE' => 'Electrical & Electronic Engineering',
                                            'ME' => 'Mechanical Engineering',
                                            'CE' => 'Civil Engineering',
                                            'TE' => 'Textile Engineering',
                                            'IPE' => 'Industrial & Production Engineering',
                                            'Arch' => 'Architecture',
                                            'MME' => 'Material & Metallurgical Engineering',
                                            'ChE' => 'Chemical Engineering',
                                            'FE' => 'Food Engineering'
                                        ];
                                        foreach ($departments as $code => $name): ?>
                                            <option value="<?= $code ?>" <?= ($studentData['department'] ?? '') === $code ? 'selected' : '' ?>><?= $name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Program</label>
                                    <select class="form-select" name="program" required>
                                        <option value="">Select Program</option>
                                        <option value="BSc" <?= ($studentData['program'] ?? '') === 'BSc' ? 'selected' : '' ?>>BSc Engineering</option>
                                        <option value="BArch" <?= ($studentData['program'] ?? '') === 'BArch' ? 'selected' : '' ?>>Bachelor of Architecture</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Year</label>
                                    <select class="form-select" name="year" required>
                                        <option value="">Select Year</option>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <option value="<?= $i ?>" <?= ($studentData['year'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?> Year</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Semester</label>
                                    <select class="form-select" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="First" <?= ($studentData['semester'] ?? '') == 'First' ? 'selected' : '' ?>>1st Semester</option>
                                        <option value="Second" <?= ($studentData['semester'] ?? '') == 'Second' ? 'selected' : '' ?>>2nd Semester</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Address Section -->
                            <h5 class="mb-3 mt-4">Address Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Division</label>
                                    <select class="form-select" name="division_id" id="division_id" required onchange="fetchDistricts(this.value)">
                                        <option value="">Select Division</option>
                                        <?php foreach ($divisions as $division): ?>
                                            <option value="<?= $division['id'] ?>" <?= ($studentData['division_id'] ?? '') == $division['id'] ? 'selected' : '' ?>>
                                                <?= $division['name'] ?> (<?= $division['bn_name'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">District</label>
                                    <select class="form-select" name="district_id" id="district_id" required onchange="fetchUpazilas(this.value)">
                                        <option value="">Select District</option>
                                        <?php if (!empty($districts)): foreach ($districts as $district): ?>
                                                <option value="<?= $district['id'] ?>" <?= ($studentData['district_id'] ?? '') == $district['id'] ? 'selected' : '' ?>>
                                                    <?= $district['name'] ?> (<?= $district['bn_name'] ?>)
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
                                                <option value="<?= $upazila['id'] ?>" <?= ($studentData['upazila_id'] ?? '') == $upazila['id'] ? 'selected' : '' ?>>
                                                    <?= $upazila['name'] ?> (<?= $upazila['bn_name'] ?>)
                                                </option>
                                        <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Village/Area</label>
                                    <input type="text" class="form-control" name="village_area" value="<?= $studentData['village_area'] ?? '' ?>" required>
                                </div>
                            </div>

                            <!-- Guardian Information -->
                            <h5 class="mb-3 mt-4">Guardian Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" class="form-control" name="guardian_name" value="<?= $studentData['guardian_name'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Guardian Phone</label>
                                    <input type="tel" class="form-control" name="guardian_phone" value="<?= $studentData['guardian_phone'] ?? '' ?>" required>
                                </div>
                            </div>

                            <!-- Room Information -->
                            <h5 class="mb-3 mt-4">Room Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Hall Name</label>
                                    <select class="form-select" name="hall_name" required>
                                        <option value="">Select Hall</option>
                                        <?php
                                        $currentGender = $studentData['gender'] ?? '';
                                        $hallsToShow = ($currentGender === 'Female') ? $femaleHalls : $maleHalls;

                                        foreach ($hallsToShow as $hall): ?>
                                            <option value="<?= $hall ?>" <?= ($studentData['hall_name'] ?? '') === $hall ? 'selected' : '' ?>><?= $hall ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Room Number</label>
                                    <input type="text" class="form-control" name="room_number" value="<?= $studentData['room_number'] ?? '' ?>" required>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary px-4">Update Profile</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>