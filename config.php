<?php
/**
 * ASENA Enterprise - Master Configuration
 * Auto-detects Localhost (XAMPP) vs Production Hosting (cPanel)
 * Fully compliant with open_basedir restrictions.
 */

$is_local = false;
if (DIRECTORY_SEPARATOR === '\\') {
    // Running on Windows local development (XAMPP)
    $is_local = true;
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    if (strpos($host, 'localhost') !== false || 
        strpos($host, '127.0.0.1') !== false || 
        strpos($host, '192.168.') !== false || 
        strpos($host, '10.') === 0 || 
        strpos($host, '.local') !== false) {
        $is_local = true;
    }
}

// If executing inside cPanel /home/ structure, force production mode without checking external paths
if (strpos(__DIR__, '/home/') !== false || (isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['DOCUMENT_ROOT'], '/home/') !== false)) {
    $is_local = false;
}

if ($is_local) {
    // Local Development (XAMPP)
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', 'asena_premium');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
} else {
    // Production Hosting (cPanel)
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_NAME')) define('DB_NAME', 'asencomp_asena_db');
    if (!defined('DB_USER')) define('DB_USER', 'asencomp_admin');
    if (!defined('DB_PASS')) define('DB_PASS', 'X3~YN,HY9M:j%jx');
}

if (!defined('SITE_URL')) {
    define('SITE_URL', '');
}
?>
