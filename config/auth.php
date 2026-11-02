<?php
// Shared access-control helpers.
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . dirname($_SERVER['SCRIPT_NAME']) . "/../index.php");
        exit();
    }
}
function require_admin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: " . dirname($_SERVER['SCRIPT_NAME']) . "/../index.php");
        exit();
    }
}
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
