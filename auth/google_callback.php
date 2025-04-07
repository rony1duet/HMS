<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../models/User.php';
require_once '../models/Staff.php';

Session::init();

if (empty($_GET['code'])) {
    $_SESSION['error'] = 'Authorization code not provided';
    header('Location: /HMS/');
    exit();
}

try {
    $googleConfig = require '../config/google.php';

    if (!isset($googleConfig['clientId'], $googleConfig['clientSecret'], $googleConfig['redirectUri'])) {
        throw new Exception('Google OAuth2 configuration is incomplete.');
    }

    $client = new Google_Client();
    $client->setClientId($googleConfig['clientId']);
    $client->setClientSecret($googleConfig['clientSecret']);
    $client->setRedirectUri($googleConfig['redirectUri']);
    $client->addScope('email');
    $client->addScope('profile');

    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    $googleService = new Google\Service\Oauth2($client);
    $userDetails = $googleService->userinfo->get();

    if (!$userDetails) {
        throw new Exception('Failed to fetch user details from Google');
    }


    $user = new User($conn);
    $role = $user->determineRole($userDetails->email);
    $userData = [
        'google_id' => $userDetails->id,
        'display_name' => $userDetails->name,
        'email' => $userDetails->email,
        'role' => $role
    ];

    // Find existing user by email
    $existingUser = $user->findByEmail($userData['email']);

    // Check if user exists in staff_profiles table
    $staff = new Staff($conn);
    $isStaffMember = $staff->isExistInStaff($userData['email']);

    // If user exists, update their google_id
    if ($existingUser) {
        $user->updateGoogleId($existingUser['slug'], $userData['google_id']);
        $userData['role'] = $existingUser['role'];
        $role = $existingUser['role'];
    }

    // Check if user is trying to login as staff
    if ($_SESSION['getRole'] === 'staff') {
        // Verify if user exists in staff_profiles
        if ($isStaffMember) {
            $userId = $user->findOrCreateByGoogleId($userData);
            $slug = $user->findByGoogleId($userData['google_id']);

            if (!$userId) {
                throw new Exception('Failed to create or update user');
            }

            Session::setUserData([
                'id' => $userId,
                'role' => $userData['role'],
                'email' => $userData['email'],
                'display_name' => $userData['display_name'],
                'slug' => $slug
            ]);

            header('Location: /HMS/');
            exit();
        } else {
            $_SESSION['error'] = 'You are not a staff member';
            header('Location: /HMS/');
            exit();
        }
    }

    // Check if user is trying to login as provost
    if ($_SESSION['getRole'] === 'provost') {
        if ($role === 'provost') {
            $userId = $user->findOrCreateByGoogleId($userData);
            $slug = $user->findByGoogleId($userData['google_id']);

            if (!$userId) {
                throw new Exception('Failed to create or update user');
            }

            Session::setUserData([
                'id' => $userId,
                'role' => $userData['role'],
                'email' => $userData['email'],
                'display_name' => $userData['display_name'],
                'slug' => $slug
            ]);

            header('Location: /HMS/');
            exit();
        } else {
            $_SESSION['error'] = 'You are not a provost';
            header('Location: /HMS/');
            exit();
        }
    }

    $_SESSION['error'] = 'You are not authorized to access this page';
    header('Location: /HMS/');
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: /HMS/');
    exit();
}
