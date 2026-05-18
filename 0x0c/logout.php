<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/storage.php';

logoutAdmin();
header('Location: /0x0c/login.php');
exit;

