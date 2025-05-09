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

            // Validate required fields (excluding profile image)
            $requiredFields = [
                'student_id',
                'full_name',
                'date_of_birth',
                'gender',
                'blood_group',
                'department',
                'program',
                'year',
                'semester',
                'phone_number',
                'division_id',
                'district_id',
                'upazila_id',
                'village_area',
                'guardian_name',
                'guardian_phone',
                'hall_name',
                'room_number'
            ];

            $errors = [];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
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

            // Handle profile picture upload
            if (!empty($_POST['cropped_image_data'])) {
                // Use the cropped image data directly as data URI
                $profilePictureData = $_POST['cropped_image_data'];

                // Validate it's a proper data URI
                if (!preg_match('/^data:image\/(png|jpeg|jpg|gif);base64,/', $profilePictureData)) {
                    $errors[] = 'Invalid image data format.';
                } else {
                    $data['profile_image_uri'] = $profilePictureData;
                }
            } elseif (!empty($_FILES['profile_picture']['name'])) {
                // Handle regular file upload
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                $fileType = $_FILES['profile_picture']['type'];

                if (!in_array($fileType, $allowedTypes)) {
                    $errors[] = 'Only JPG, PNG, and GIF files are allowed.';
                } elseif ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
                    $errors[] = 'File size must be less than 5MB.';
                } else {
                    // Convert the uploaded file to data URI
                    $fileContent = file_get_contents($_FILES['profile_picture']['tmp_name']);
                    $mimeType = mime_content_type($_FILES['profile_picture']['tmp_name']);
                    $base64 = base64_encode($fileContent);
                    $data['profile_image_uri'] = 'data:' . $mimeType . ';base64,' . $base64;
                }
            } else {
                // Keep the existing profile image if no new one is uploaded
                $data['profile_image_uri'] = $studentData['profile_image_uri'] ?? '';
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

<!-- Include CropperJS and related libraries -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    :root {
        --primary-color: #007bff;
        --accent-color: #0056b3;
        --text-color: #333;
        --border-color: #e9ecef;
        --success-color: #28a745;
        --danger-color: #dc3545;
    }

    body {
        background-color: #f8f9fc;
        color: var(--text-color);
    }

    .card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        overflow: hidden;
    }

    .card-header {
        background-color: var(--primary-color);
        color: white;
        border-bottom: none;
        padding: 1.5rem;
    }

    .card-body {
        padding: 2rem;
    }

    .section-title {
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--border-color);
    }

    .form-label {
        font-weight: 600;
        color: var(--text-color);
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        padding: 0.5rem 2rem;
        font-weight: 600;
    }

    .btn-primary:hover {
        background-color: var(--accent-color);
        border-color: var(--accent-color);
    }

    /* Modern Profile Picture Upload Styles */
    .profile-pic-container {
        position: relative;
        width: 150px;
        height: 150px;
        margin: 0 auto 2rem;
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .profile-pic-container:hover {
        transform: scale(1.05);
    }

    .profile-pic {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        border: 5px solid white;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .profile-pic-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .profile-pic-container:hover .profile-pic-overlay {
        opacity: 1;
    }

    .profile-pic-overlay i {
        color: white;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .profile-pic-overlay span {
        color: white;
        font-size: 0.9rem;
        text-align: center;
    }

    .profile-pic-input {
        display: none;
    }

    /* Modern Modal Styles */
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    .modal-header {
        background-color: var(--primary-color);
        color: white;
        border-bottom: none;
        border-radius: 15px 15px 0 0;
        padding: 1.5rem;
    }

    .modal-title {
        font-weight: 600;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        border-top: none;
        padding: 1rem 1.5rem;
        border-radius: 0 0 15px 15px;
    }

    .btn-close-white {
        filter: invert(1);
    }

    /* Cropper Container Styles */
    .cropper-container {
        margin: 0 auto;
        max-width: 100%;
    }

    .img-container {
        width: 100%;
        height: 400px;
        margin-bottom: 1rem;
        overflow: hidden;
        border-radius: 10px;
        background-color: #f8f9fa;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .img-container img {
        max-width: 100%;
        max-height: 100%;
    }

    .preview-container {
        text-align: center;
    }

    .preview {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto;
        border: 3px solid var(--border-color);
    }

    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 1rem;
    }

    .btn-crop {
        background-color: var(--success-color);
        border-color: var(--success-color);
    }

    .btn-crop:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }

    .btn-rotate {
        background-color: #6c757d;
        border-color: #6c757d;
    }

    .btn-rotate:hover {
        background-color: #5a6268;
        border-color: #545b62;
    }

    /* Drag and Drop Zone */
    .drop-zone {
        border: 2px dashed #ced4da;
        border-radius: 10px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .drop-zone:hover {
        border-color: var(--primary-color);
        background-color: rgba(0, 123, 255, 0.05);
    }

    .drop-zone.active {
        border-color: var(--success-color);
        background-color: rgba(40, 167, 69, 0.05);
    }

    .drop-zone i {
        font-size: 2.5rem;
        color: var(--primary-color);
        margin-bottom: 1rem;
    }

    .drop-zone p {
        margin-bottom: 0.5rem;
    }

    .drop-zone small {
        color: #6c757d;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem;
        }

        .profile-pic-container {
            width: 120px;
            height: 120px;
        }

        .img-container {
            height: 300px;
        }
    }
</style>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header">
                    <h4 class="card-title mb-0 text-center">Update Student Profile</h4>
                </div>

                <div class="card-body p-4">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success'];
                            unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['errors'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['errors'] as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach;
                                unset($_SESSION['errors']); ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

                            // Initialize division districts if already selected
                            const divisionId = document.getElementById('division_id').value;
                            if (divisionId) {
                                fetchDistricts(divisionId);
                            }
                        });
                    </script>

                    <?php if ($_SESSION['role'] === 'student'): ?>
                        <form method="POST" action="" class="needs-validation" novalidate enctype="multipart/form-data">
                            <input type="hidden" id="current_district_id" value="<?= $studentData['district_id'] ?? '' ?>">
                            <input type="hidden" id="current_upazila_id" value="<?= $studentData['upazila_id'] ?? '' ?>">

                            <!-- Modern Profile Picture Upload Section -->
                            <div class="profile-pic-container" data-bs-toggle="modal" data-bs-target="#profilePicModal">
                                <img src="<?= $studentData['profile_image_uri'] ?? '/HMS/assets/images/default-profile.png' ?>"
                                    class="profile-pic"
                                    id="profile-pic-preview">
                                <div class="profile-pic-overlay">
                                    <i class="fas fa-camera"></i>
                                    <span>Change Photo</span>
                                </div>
                            </div>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="profile-pic-input">
                            <input type="hidden" id="cropped_image_data" name="cropped_image_data">

                            <!-- Personal Information Section -->
                            <h5 class="section-title">Personal Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Student ID</label>
                                    <input type="text" class="form-control" name="student_id" value="<?= explode('@', $userData['email'])[0] ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" value="<?= $studentData['full_name'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= $userData['email'] ?? '' ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone_number" value="<?= $studentData['phone_number'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" name="date_of_birth" value="<?= $studentData['date_of_birth'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <select class="form-select" name="gender" required onchange="updateHallOptions()">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?= ($studentData['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                        <option value="Female" <?= ($studentData['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?= ($studentData['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
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
                            <h5 class="section-title mt-5">Academic Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label class="form-label">Program</label>
                                    <select class="form-select" name="program" required>
                                        <option value="">Select Program</option>
                                        <option value="BSc" <?= ($studentData['program'] ?? '') === 'BSc' ? 'selected' : '' ?>>BSc Engineering</option>
                                        <option value="BArch" <?= ($studentData['program'] ?? '') === 'BArch' ? 'selected' : '' ?>>Bachelor of Architecture</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Year</label>
                                    <select class="form-select" name="year" required>
                                        <option value="">Select Year</option>
                                        <option value="1" <?= ($studentData['year'] ?? '') == '1' ? 'selected' : '' ?>>1st Year</option>
                                        <option value="2" <?= ($studentData['year'] ?? '') == '2' ? 'selected' : '' ?>>2nd Year</option>
                                        <option value="3" <?= ($studentData['year'] ?? '') == '3' ? 'selected' : '' ?>>3rd Year</option>
                                        <option value="4" <?= ($studentData['year'] ?? '') == '4' ? 'selected' : '' ?>>4th Year</option>
                                        <option value="5" <?= ($studentData['year'] ?? '') == '5' ? 'selected' : '' ?>>5th Year</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Semester</label>
                                    <select class="form-select" name="semester" required>
                                        <option value="">Select Semester</option>
                                        <option value="First" <?= ($studentData['semester'] ?? '') == 'First' ? 'selected' : '' ?>>1st Semester</option>
                                        <option value="Second" <?= ($studentData['semester'] ?? '') == 'Second' ? 'selected' : '' ?>>2nd Semester</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Address Section -->
                            <h5 class="section-title mt-5">Address Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
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
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label class="form-label">Village/Area</label>
                                    <input type="text" class="form-control" name="village_area" value="<?= $studentData['village_area'] ?? '' ?>" required>
                                </div>
                            </div>

                            <!-- Guardian Information -->
                            <h5 class="section-title mt-5">Guardian Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Guardian Name</label>
                                    <input type="text" class="form-control" name="guardian_name" value="<?= $studentData['guardian_name'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Guardian Phone</label>
                                    <input type="tel" class="form-control" name="guardian_phone" value="<?= $studentData['guardian_phone'] ?? '' ?>" required>
                                </div>
                            </div>

                            <!-- Room Information -->
                            <h5 class="section-title mt-5">Room Information</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label class="form-label">Room Number</label>
                                    <input type="text" class="form-control" name="room_number" value="<?= $studentData['room_number'] ?? '' ?>" required>
                                </div>
                            </div>

                            <div class="text-center mt-5">
                                <button type="submit" class="btn btn-primary px-4 py-2">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>

                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern Profile Picture Upload Modal -->
<div class="modal fade" id="profilePicModal" tabindex="-1" aria-labelledby="profilePicModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title fw-semibold" id="profilePicModalLabel">
                    <i class="fas fa-camera me-2"></i>Upload Profile Picture
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Step 1: Upload Area -->
                <div class="upload-step" id="uploadStep" style="max-width: 480px; margin: 0 auto;">
                    <!-- Drag and Drop Zone -->
                    <div class="drop-zone" id="dropZone" style="border: 2px dashed #e2e8f0; border-radius: 12px; padding: 32px; text-align: center; position: relative; background-color: #f8fafc; transition: all 0.2s ease;">
                        <div class="drop-zone-content">
                            <div class="icon-wrapper" style="background-color: #e0f2fe; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; width: 72px; height: 72px; margin-bottom: 20px;">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #0ea5e9;">
                                    <path d="M7 16C4.79086 16 3 14.2091 3 12C3 10.0929 4.33457 8.4926 6.12071 8.09695C6.04169 7.74395 6 7.37684 6 7C6 4.23858 8.23858 2 11 2C13.4193 2 15.4373 3.71825 15.9002 6.00098C15.9334 6.00033 15.9666 6 16 6C18.7614 6 21 8.23858 21 11C21 13.419 19.2822 15.4367 17 15.9M16 13L12 9M12 9L8 13M12 9V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                            <h5 style="font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 12px;">Drag & drop your photo here</h5>
                            <p style="color: #64748b; margin-bottom: 16px;">or</p>
                            <button type="button" id="browseBtn" style="background-color: #0ea5e9; color: white; font-weight: 500; padding: 10px 24px; border-radius: 999px; border: none; cursor: pointer; display: inline-flex; align-items: center; transition: background-color 0.2s ease;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px;">
                                    <path d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" stroke="currentColor" stroke-width="2" />
                                    <path d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2" />
                                </svg>
                                Select from device
                            </button>
                            <div style="margin-top: 20px; font-size: 13px; color: #64748b; line-height: 1.5;">
                                <p style="margin: 4px 0;">Supports: JPEG, PNG, WEBP (Max 5MB)</p>
                                <p style="margin: 4px 0;">Recommended: Square image, at least 300x300px</p>
                            </div>
                        </div>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">
                    </div>
                </div>

                <!-- Step 2: Cropping Area -->
                <div class="cropping-step d-none" id="croppingStep">
                    <div class="row g-4">
                        <!-- Cropper Container -->
                        <div class="col-lg-8">
                            <div class="img-container bg-light rounded-3 overflow-hidden position-relative" id="cropperContainer" style="height: 400px;">
                                <img id="image-cropper" src="" alt="Profile Picture" class="w-100 h-100 object-fit-contain">
                                <div class="cropper-loading d-none position-absolute top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex align-items-center justify-content-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview and Controls -->
                        <div class="col-lg-4">
                            <div class="d-flex flex-column h-100">
                                <div class="preview-wrapper bg-light rounded-3 p-3 mb-3 text-center">
                                    <h6 class="fw-semibold mb-3">Preview</h6>
                                    <div class="preview mx-auto rounded-circle overflow-hidden" style="width: 150px; height: 150px;"></div>
                                </div>

                                <div class="controls-wrapper mt-auto">
                                    <div class="d-grid gap-2">
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-outline-secondary" id="rotateLeftBtn" title="Rotate Left">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" id="rotateRightBtn" title="Rotate Right">
                                                <i class="fas fa-redo-alt"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" id="flipHorizontalBtn" title="Flip Horizontal">
                                                <i class="fas fa-arrows-alt-h"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary" id="flipVerticalBtn" title="Flip Vertical">
                                                <i class="fas fa-arrows-alt-v"></i>
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-primary" id="cropBtn">
                                            <i class="fas fa-check-circle me-2"></i>Apply Changes
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" id="cancelCropBtn">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    /* Modern Drop Zone Styling */
    .drop-zone {
        border: 2px dashed #dee2e6;
        background-color: #f8f9fa;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .drop-zone:hover {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.05);
    }

    .drop-zone.active {
        border-color: #198754;
        background-color: rgba(25, 135, 84, 0.05);
    }

    /* Cropper Container */
    .img-container {
        background-color: #f8f9fa;
    }

    /* Preview Styles */
    .preview {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
    }

    /* Responsive Adjustments */
    @media (max-width: 992px) {
        .img-container {
            height: 300px !important;
        }
    }

    @media (max-width: 768px) {
        .drop-zone {
            padding: 2rem !important;
        }

        .preview {
            width: 120px !important;
            height: 120px !important;
        }
    }

    @media (max-width: 576px) {
        .img-container {
            height: 250px !important;
        }

        .btn-group {
            flex-wrap: wrap;
        }

        .btn-group .btn {
            flex: 1 0 45%;
            margin: 2px;
        }
    }
</style>

<!-- Include CropperJS and related scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // DOM Elements
        const dropZone = document.getElementById('dropZone');
        const browseBtn = document.getElementById('browseBtn');
        const profilePicInput = document.getElementById('profile_picture');
        const uploadStep = document.getElementById('uploadStep');
        const croppingStep = document.getElementById('croppingStep');
        const cropperContainer = document.getElementById('cropperContainer');
        const imageCropper = document.getElementById('image-cropper');
        const cropBtn = document.getElementById('cropBtn');
        const cancelCropBtn = document.getElementById('cancelCropBtn');
        const rotateLeftBtn = document.getElementById('rotateLeftBtn');
        const rotateRightBtn = document.getElementById('rotateRightBtn');
        const flipHorizontalBtn = document.getElementById('flipHorizontalBtn');
        const flipVerticalBtn = document.getElementById('flipVerticalBtn');
        const loadingIndicator = document.querySelector('.cropper-loading');
        let cropper;

        // Initialize modal
        const profilePicModal = new bootstrap.Modal(document.getElementById('profilePicModal'));

        // Show loading indicator
        function showLoading() {
            loadingIndicator.classList.remove('d-none');
        }

        // Hide loading indicator
        function hideLoading() {
            loadingIndicator.classList.add('d-none');
        }

        // Reset modal to initial state
        function resetModal() {
            uploadStep.classList.remove('d-none');
            croppingStep.classList.add('d-none');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        // Handle file selection via browse button
        browseBtn.addEventListener('click', function() {
            profilePicInput.click();
        });

        profilePicInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                handleFileSelect(e.target.files[0]);
            }
        });

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropZone.classList.add('active');
        }

        function unhighlight() {
            dropZone.classList.remove('active');
        }

        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const file = dt.files[0];
            handleFileSelect(file);
        });

        // Handle the selected file
        function handleFileSelect(file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                showAlert('Invalid file type', 'Please select a valid image file (JPG, PNG, GIF).', 'danger');
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showAlert('File too large', 'File size must be less than 5MB.', 'danger');
                return;
            }

            showLoading();
            const reader = new FileReader();

            reader.onloadstart = function() {
                showLoading();
            };

            reader.onload = function(e) {
                // Switch to cropping step
                uploadStep.classList.add('d-none');
                croppingStep.classList.remove('d-none');

                // Set image source
                imageCropper.src = e.target.result;

                // Initialize cropper after image loads
                imageCropper.onload = function() {
                    hideLoading();

                    if (cropper) {
                        cropper.destroy();
                    }

                    cropper = new Cropper(imageCropper, {
                        aspectRatio: 1,
                        viewMode: 1,
                        autoCropArea: 0.8,
                        responsive: true,
                        restore: false,
                        checkOrientation: true,
                        preview: '.preview',
                        guides: false,
                        center: false,
                        highlight: false,
                        background: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                        ready: function() {
                            // Enable buttons when ready
                            cropBtn.disabled = false;
                        }
                    });
                };
            };

            reader.onerror = function() {
                hideLoading();
                showAlert('Error', 'Failed to load image. Please try again.', 'danger');
            };

            reader.readAsDataURL(file);
        }

        // Rotate and Flip buttons
        rotateLeftBtn.addEventListener('click', function() {
            if (cropper) {
                cropper.rotate(-15);
            }
        });

        rotateRightBtn.addEventListener('click', function() {
            if (cropper) {
                cropper.rotate(15);
            }
        });

        flipHorizontalBtn.addEventListener('click', function() {
            if (cropper) {
                cropper.scaleX(-cropper.getData().scaleX || -1);
            }
        });

        flipVerticalBtn.addEventListener('click', function() {
            if (cropper) {
                cropper.scaleY(-cropper.getData().scaleY || -1);
            }
        });

        // Cancel cropping
        cancelCropBtn.addEventListener('click', resetModal);

        // Crop button
        cropBtn.addEventListener('click', function() {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    width: 500,
                    height: 500,
                    minWidth: 256,
                    minHeight: 256,
                    maxWidth: 2000,
                    maxHeight: 2000,
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });

                if (canvas) {
                    // Convert canvas to data URI
                    const croppedImageData = canvas.toDataURL('image/png');
                    document.getElementById('cropped_image_data').value = croppedImageData;

                    // Update preview image
                    document.getElementById('profile-pic-preview').src = croppedImageData;

                    // Close modal
                    profilePicModal.hide();
                    resetModal();
                }
            }
        });

        // Reset modal when closed
        document.getElementById('profilePicModal').addEventListener('hidden.bs.modal', resetModal);

        // Helper function to show alerts
        function showAlert(title, message, type) {
            //sweetAlert2
            Swal.fire({
                title: title,
                text: message,
                icon: type,
                confirmButtonText: 'OK'
            });
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>