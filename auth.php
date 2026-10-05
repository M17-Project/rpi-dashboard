<?php
// Admin authentication and CSRF protection

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => !empty($_SERVER['HTTPS']),
]);
session_name('m17dash');
session_start();

function isAdmin() {
    return !empty($_SESSION['admin']);
}

function csrfToken() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField() {
    return '<input type="hidden" name="csrf" value="' . h(csrfToken()) . '">';
}

// Reject any POST without a valid token
function csrfCheck() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        exit('Invalid or expired form token. Please reload the page and try again.');
    }
}

// Send unauthenticated visitors to the login page, then check the CSRF token
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: login.php?next=' . urlencode(basename($_SERVER['SCRIPT_NAME'])));
        exit;
    }
    csrfCheck();
}
