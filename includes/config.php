<?php
// Load Environment Configuration First
require_once __DIR__ . '/Env.php';

// === OAUTH CONFIGURATION ===
// Google OAuth Client ID & Secret strictly loaded from .env
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');

// Determine accurate protocol and host for callback URI (Google prohibits query params in Redirect URIs)
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? "https" : "http";
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'asena.company';

// Correctly resolve suite base path whether included from root, actions/, includes/, or admin/
$script_path = $_SERVER['PHP_SELF'] ?? '';
$suite_dir = preg_replace('#/(actions|includes|admin)(/.*)?$#i', '', dirname($script_path));
$suite_dir = rtrim($suite_dir, '/\\');

$envRedirect = getenv('GOOGLE_REDIRECT_URI');
define('GOOGLE_REDIRECT_URI', !empty($envRedirect) ? $envRedirect : ($protocol . '://' . $host . $suite_dir . '/actions/oauth_callback.php'));

// Apple OAuth strictly loaded from .env
define('APPLE_CLIENT_ID', getenv('APPLE_CLIENT_ID') ?: '');
define('APPLE_TEAM_ID', getenv('APPLE_TEAM_ID') ?: '');
define('APPLE_KEY_ID', getenv('APPLE_KEY_ID') ?: '');
define('APPLE_REDIRECT_URI', $protocol . '://' . $host . $suite_dir . '/actions/oauth_callback.php');
?>
