<?php
require_once '../vendor/autoload.php';
require_once '../config/database.php';
require_once '../includes/session.php';

Session::init();

if (isset($_SESSION['user_id'])) {
    header('Location: /HMS/dashboard/' . $_SESSION['role'] . '.php');
    exit();
}

try {
    $config = require '../config/microsoft.php';
    $provider = new League\OAuth2\Client\Provider\GenericProvider([
        'clientId'                => $config['clientId'],
        'clientSecret'            => $config['clientSecret'],
        'redirectUri'             => $config['redirectUri'],
        'urlAuthorize'            => 'https://login.microsoftonline.com/' . $config['tenantId'] . '/oauth2/v2.0/authorize',
        'urlAccessToken'          => 'https://login.microsoftonline.com/' . $config['tenantId'] . '/oauth2/v2.0/token',
        'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
        'scopes'                  => ['User.Read']
    ]);

    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();

    header('Location: ' . $authUrl);
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to initialize Microsoft login';
    header('Location: /HMS/');
    exit();
}
