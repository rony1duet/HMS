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
    $_SESSION['auth_provider'] = 'google';
    $config = require '../config/google.php';

    $client = new Google_Client();

    $client->setClientId($config['clientId']);
    $client->setClientSecret($config['clientSecret']);
    $client->setRedirectUri($config['redirectUri']);
    $client->addScope('email');
    $client->addScope('profile');

    $authUrl = $client->createAuthUrl();
    $_SESSION['auth_url'] = $authUrl;

    header('Location: ' . $authUrl);
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to initialize Google login';
    header('Location: /HMS/');
    exit();
}
