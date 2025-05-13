<?php
// includes/header.php
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>University Attendance Portal</title>
    <link rel="stylesheet" href="/public/assets/css/style.css">
</head>
<body>
<header>
    <h1>University Attendance Portal</h1>
    <nav>
        <ul>
            <?php if (is_logged_in()): ?>
                <li><a href="/public/logout.php">Logout</a></li>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="/admin/dashboard.php">Admin Dashboard</a></li>
                <?php elseif ($_SESSION['role'] == 'faculty'): ?>
                    <li><a href="/faculty/dashboard.php">Faculty Dashboard</a></li>
                <?php elseif ($_SESSION['role'] == 'student'): ?>
                    <li><a href="/student/dashboard.php">Student Dashboard</a></li>
                <?php elseif ($_SESSION['role'] == 'parent'): ?>
                    <li><a href="/parent/dashboard.php">Parent Dashboard</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="/index.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main>
