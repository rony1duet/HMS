<?php
require_once '../includes/Session.php';

Session::init();
Session::destroy();

header('Location: /HMS/');
exit();
