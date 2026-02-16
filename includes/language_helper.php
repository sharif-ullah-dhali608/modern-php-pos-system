<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lang = $_GET['lang'] ?? $_SESSION['lang'] ?? 'en';
if (!in_array($lang, ['en', 'bn'])) {
    $lang = 'en';
}

$_SESSION['lang'] = $lang;

$lang_file = __DIR__ . "/lang/$lang.php";
if (file_exists($lang_file)) {
    $translations = include($lang_file);
} else {
    $translations = [];
}

function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}
?>
