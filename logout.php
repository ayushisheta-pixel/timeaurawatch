<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();

setFlash('success', 'You have been logged out of your TIMEAURA account.');
header("Location: login.php");
exit();
?>
