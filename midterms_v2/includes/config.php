<?php
// Check if a session has NOT been started yet
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Your MySQL username
define('DB_PASSWORD', '');     // Your MySQL password
define('DB_NAME', 'pos_system'); // Your database name

// Other configurations
define('SITE_URL', 'http://localhost/CODES/midterms_v2/'); // Adjust as needed
?>