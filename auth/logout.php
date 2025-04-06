<?php
require_once '../includes/session.php';

Session::init();
Session::destroy();

header('Location: /HMS/');
exit();
