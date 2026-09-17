<?php
/**
 * includes/session.php — Unified session bootstrapper
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
