<?php
// includes/functions.php

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

// Example: Check if a value is a valid email
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>
