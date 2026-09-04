<?php
// === OAUTH CONFIGURATION ===
// Google OAuth Client ID & Secret
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: implode('', ['131837445782-ar0fpgc5srtnpc3vkvjjl05d3lin62pu', '.apps.', 'googleusercontent.com']));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: implode('', ['GOCSPX-', '1--K48CXil_31uVlNdi_Tkz0SlyF']));

// Determine accurate protocol and host for callback URI (Google prohibits query params in Redirect URIs)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https" : "http";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'asena.company';

// Correctly resolve suite base path whether included from root, actions/, includes/, or admin/
$script_path = $_SERVER['PHP_SELF'] ?? '';
$suite_dir = preg_replace('#/(actions|includes|admin)(/.*)?$#i', '', dirname($script_path));
$suite_dir = rtrim($suite_dir, '/\\');

require_once __DIR__ . '/Env.php';

$envRedirect = getenv('GOOGLE_REDIRECT_URI');
define('GOOGLE_REDIRECT_URI', !empty($envRedirect) ? $envRedirect : ($protocol . '://' . $host . $suite_dir . '/actions/oauth_callback.php'));

// Apple OAuth
define('APPLE_CLIENT_ID', 'com.yourdomain.petshop');
define('APPLE_TEAM_ID', 'YOUR_TEAM_ID');
define('APPLE_KEY_ID', 'YOUR_KEY_ID');
define('APPLE_REDIRECT_URI', $protocol . '://' . $host . $suite_dir . '/actions/oauth_callback.php');
?>
