<?php
// Application Configuration
define('APP_NAME', 'Consultancy Management System');
define('APP_URL', 'http://localhost/consultancy_management');

// Include database and session configs
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

// Timezone
date_default_timezone_set('UTC');
?>
