<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../models/User.php';

use League\OAuth2\Client\Provider\GenericProvider;

Session::init();

if (empty($_GET['code'])) {
    $_SESSION['error'] = 'Authorization code not provided';
    header('Location: /HMS/');
    exit();
}

if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    unset($_SESSION['oauth2state']);
    $_SESSION['error'] = 'Invalid state parameter';
    header('Location: /HMS/');
    exit();
}

try {
    // Load Microsoft OAuth2 configuration
    $microsoftConfig = require '../config/microsoft.php';

    // Validate the configuration
    if (!isset($microsoftConfig['clientId'], $microsoftConfig['clientSecret'], $microsoftConfig['redirectUri'], $microsoftConfig['tenantId'])) {
        throw new Exception('Microsoft OAuth2 configuration is incomplete.');
    }

    $provider = new GenericProvider([
        'clientId'                => $microsoftConfig['clientId'],
        'clientSecret'            => $microsoftConfig['clientSecret'],
        'redirectUri'             => $microsoftConfig['redirectUri'],
        'urlAuthorize'            => 'https://login.microsoftonline.com/' . $microsoftConfig['tenantId'] . '/oauth2/v2.0/authorize',
        'urlAccessToken'          => 'https://login.microsoftonline.com/' . $microsoftConfig['tenantId'] . '/oauth2/v2.0/token',
        'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
        'scopes'                  => ['User.Read']
    ]);

    // Exchange authorization code for an access token
    $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Get the user's profile from Microsoft Graph API
    $request = $provider->getAuthenticatedRequest(
        'GET',
        'https://graph.microsoft.com/v1.0/me',
        $accessToken
    );

    $response = $provider->getParsedResponse($request);

    if (!isset($response['id'], $response['mail'], $response['displayName'])) {
        throw new Exception('Invalid response from Microsoft Graph API');
    }

    // Create or update user in the database
    $user = new User($conn);
    $userData = [
        'microsoft_id' => $response['id'],
        'email' => $response['mail'],
        'display_name' => $response['displayName'],
        'role' => 'student'
    ];

    $userId = $user->findOrCreateByMicrosoftId($userData);
    $slug = $user->findByMicrosoftId($userData['microsoft_id']);

    if (!$userId) {
        throw new Exception('Failed to create or update user in the database');
    }

    // Set session variables
    Session::setUserData([
        'id' => $userId,
        'email' => $userData['email'],
        'display_name' => $userData['display_name'],
        'role' => $userData['role'],
        'slug' => $slug
    ]);

    $profileStatus = $user->getProfileStatus($slug);
    if ($profileStatus === 'not_updated') {
        header('Location: /HMS/profiles/student/');
        exit();
    }

    header('Location: /HMS/');
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: /HMS/');
    exit();
}
