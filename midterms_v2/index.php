<?php
// We only need to load the config to get SITE_URL
require_once 'includes/config.php';

// Start a session
session_start();

// Redirect all requests from the root to the login page
// No other checks should happen here.
header('Location: ' . SITE_URL . 'pages/login.php');
exit;
