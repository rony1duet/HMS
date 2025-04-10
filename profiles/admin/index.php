<?php
$title = "DUET HMS | Admin Profile";
require_once '../../config/database.php';
require_once '../../models/User.php';
require_once '../../models/Admin.php';
require_once '../../includes/Session.php';

Session::init();

if (!Session::isLoggedIn() || !Session::getUserRole() === 'admin') {
    header('Location: /HMS/');
    exit();
}

// Initialize User model and fetch user data
$user = new User($conn);
$userData = $user->findByEmail($_SESSION['email']);

if (!$userData || $userData['role'] !== 'admin') {
    header('Location: /HMS/');
    exit();
}

// Initialize and fetch admin profile data
$adminProfile = new AdminProfile($conn);
$adminData = $adminProfile->getByUserId($userData['slug']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $errors = [];
        $data = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => $userData['email'],
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'designation' => trim($_POST['designation'] ?? ''),
            'joining_date' => trim($_POST['joining_date'] ?? '')
        ];

        // Validate required fields
        $requiredFields = ['full_name', 'designation', 'joining_date'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        // Validate phone number
        if (!empty($data['phone_number']) && !preg_match('/^\+?[0-9]{10,13}$/', $data['phone_number'])) {
            $errors[] = 'Invalid phone number format.';
        }

        // Validate joining date
        if (!empty($data['joining_date']) && !strtotime($data['joining_date'])) {
            $errors[] = 'Invalid joining date format.';
        }

        // Handle profile picture
        $profileImageUri = null;
        if (!empty($_POST['cropped_image_data'])) {
            if (preg_match('/^data:image\/(png|jpeg|jpg|gif);base64,/', $_POST['cropped_image_data'])) {
                $profileImageUri = $_POST['cropped_image_data'];
            } else {
                $errors[] = 'Invalid image data format.';
            }
        } elseif (!empty($_FILES['profile_picture']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['profile_picture']['type'];

            if (!in_array($fileType, $allowedTypes)) {
                $errors[] = 'Only JPG, PNG, and GIF files are allowed.';
            } elseif ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) {
                $errors[] = 'File size must be less than 5MB.';
            } else {
                $fileContent = file_get_contents($_FILES['profile_picture']['tmp_name']);
                $mimeType = mime_content_type($_FILES['profile_picture']['tmp_name']);
                $base64 = base64_encode($fileContent);
                $profileImageUri = 'data:' . $mimeType . ';base64,' . $base64;
            }
        }

        if (!empty($profileImageUri)) {
            $data['profile_image_uri'] = $profileImageUri;
        } elseif (empty($adminData['profile_image_uri'])) {
            $errors[] = 'Profile picture is required.';
        }

        if (empty($errors)) {
            if ($adminProfile->updateProfile($userData['slug'], $data)) {
                $_SESSION['success'] = 'Profile updated successfully.';
                header('Location: /HMS/dashboard/admin.php');
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
                    <h4 class="card-title mb-0 text-center">Update Admin Profile</h4>
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

                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <form method="POST" action="" class="needs-validation" novalidate enctype="multipart/form-data">
                            <!-- Modern Profile Picture Upload Section -->
                            <div class="profile-pic-container" data-bs-toggle="modal" data-bs-target="#profilePicModal">
                                <img src="<?= $adminData['profile_image_uri'] ?? '/HMS/assets/images/default-profile.png' ?>"
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
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="full_name" value="<?= $adminData['full_name'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= $userData['email'] ?? '' ?>" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="phone_number" value="<?= $adminData['phone_number'] ?? '' ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Designation</label>
                                    <select class="form-select" name="designation" required>
                                        <option value="">Select Designation</option>
                                        <?php
                                        $designations = [
                                            'Registrar',
                                            'Proctor',
                                            'Controller of Examinations',
                                            'Librarian',
                                            'Director of Student Welfare',
                                            'Chief Medical Officer',
                                            'Accounts Officer',
                                            'Administrative Officer',
                                            'Assistant Registrar',
                                            'Section Officer'
                                        ];
                                        foreach ($designations as $designation): ?>
                                            <option value="<?= $designation ?>" <?= ($adminData['designation'] ?? '') === $designation ? 'selected' : '' ?>><?= $designation ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Joining Date</label>
                                    <input type="date" class="form-control" name="joining_date" value="<?= $adminData['joining_date'] ?? '' ?>" required>
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