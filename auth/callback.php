<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../config/database.php';
require_once '../includes/Session.php';
require_once '../models/User.php';
require_once '../vendor/autoload.php';

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

    if (!isset($response['id'], $response['userPrincipalName'])) {
        throw new Exception('Failed to get user profile from Microsoft Graph API');
    }

    // Create or update user in the database
    $user = new User($conn);
    $userData = [
        'microsoft_id' => $response['id'],
        'email' => $response['userPrincipalName'],
        'display_name' => $response['displayName'],
        'role' => 'student'
    ];

    $userId = $user->findOrCreateByMicrosoftId($userData);

    if (!$userId) {
        throw new Exception('Failed to create or update user');
    }

    // Set session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = 'student';
    $_SESSION['display_name'] = $response['displayName'];

    //if first time login, redirect to profile page
    $last_login = $user->getLastLogin($userId);

    if ($last_login === null) {
        header('Location: /HMS/profile/index.php');
        exit();
    }
    
    // Redirect to the desired page
    header('Location: /HMS/');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: /HMS/');
    exit();
}