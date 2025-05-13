<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();
require_role('admin');

// Database connection check
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch dashboard stats
$stats = [
    'total_students' => 0,
    'total_faculty' => 0,
    'total_courses' => 0,
    'total_departments' => 0,
    'total_sessions' => 0
];

try {
    $result = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role='student'");
    $stats['total_students'] = $result ? $result->fetch_assoc()['count'] : 0;
    $result = $conn->query("SELECT COUNT(*) AS count FROM users WHERE role='faculty'");
    $stats['total_faculty'] = $result ? $result->fetch_assoc()['count'] : 0;
    $result = $conn->query("SELECT COUNT(*) AS count FROM course");
    $stats['total_courses'] = $result ? $result->fetch_assoc()['count'] : 0;
    $result = $conn->query("SELECT COUNT(*) AS count FROM department");
    $stats['total_departments'] = $result ? $result->fetch_assoc()['count'] : 0;
    $result = $conn->query("SELECT COUNT(*) AS count FROM session");
    $stats['total_sessions'] = $result ? $result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    die("Error loading stats: " . $e->getMessage());
}

$admin_name = htmlspecialchars($_SESSION['full_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Attendance Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --main: #F77066;
            --main-dark: #d94b3f;
            --main-light: #ffe5e2;
            --white: #fff;
            --gray: #f7f7f7;
            --text: #222b45;
        }
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--main-light);
            min-height: 100vh;
        }
        .topbar {
            width: 100%;
            background: linear-gradient(90deg, var(--main) 0%, var(--main-dark) 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            height: 54px;
            box-sizing: border-box;
            box-shadow: 0 2px 8px #F7706622;
        }
        .logo-row {
            display: flex;
            align-items: center;
            gap: 13px;
        }
        .uni-logo {
            height: 36px;
            width: 36px;
            object-fit: contain;
            border-radius: 7px;
            box-shadow: 0 1px 4px #F7706615;
        }
        .logo-text {
            font-size: 1.2em;
            font-weight: bold;
            letter-spacing: 1px;
            color: #fff;
        }
        .topbar .nav {
            display: flex;
            gap: 18px;
        }
        .topbar .nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            font-size: 1em;
            padding: 8px 14px;
            border-radius: 5px;
            transition: background 0.15s;
        }
        .topbar .nav a.active,
        .topbar .nav a:hover {
            background: rgba(255,255,255,0.15);
        }
        .container {
            max-width: 1200px;
            margin: 32px auto 40px auto;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 18px #F7706622;
            padding: 28px 30px 22px 30px;
        }
        .dashboard-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .welcome-message h1 {
            color: var(--main-dark);
            margin-bottom: 5px;
            font-size: 1.8em;
        }
        .welcome-message p {
            color: var(--text);
            margin-top: 0;
            font-size: 1.1em;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--main-light);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-value {
            font-size: 2.3em;
            color: var(--main-dark);
            font-weight: 700;
        }
        .stat-label {
            color: var(--text);
            font-size: 1.1em;
            margin-top: 10px;
        }
        .admin-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
        }
        .admin-card {
            background: var(--white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid var(--main-light);
            text-align: center;
        }
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card-icon {
            font-size: 2.2em;
            color: var(--main);
            margin-bottom: 15px;
        }
        .card-title {
            color: var(--main-dark);
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .card-description {
            color: var(--text);
            margin-bottom: 18px;
            line-height: 1.5;
            font-size: 1em;
        }
        .card-link {
            display: inline-block;
            background: var(--main);
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .card-link:hover {
            background: var(--main-dark);
        }
        /* Logout Modal Styles */
        .custom-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(34,43,69,0.22);
            backdrop-filter: blur(1.5px);
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .custom-modal.active {
            display: flex !important;
        }
        .modal-content {
            background: var(--white, #fff);
            border-radius: 16px;
            box-shadow: 0 4px 32px #F7706640;
            padding: 36px 30px 28px 30px;
            width: 95vw;
            max-width: 350px;
            text-align: center;
            position: relative;
            animation: modalIn 0.22s cubic-bezier(.5,1.8,.5,1);
        }
        @keyframes modalIn {
            from { transform: scale(0.89) translateY(30px); opacity: 0; }
            to { transform: none; opacity: 1; }
        }
        .modal-close {
            position: absolute;
            top: 10px; right: 18px;
            font-size: 1.6em;
            color: var(--main-dark, #d94b3f);
            cursor: pointer;
            background: none;
            border: none;
            transition: color 0.15s;
        }
        .modal-close:hover { color: #b71c1c; }
        .modal-icon {
            font-size: 2.6em;
            color: var(--main, #F77066);
            margin-bottom: 12px;
        }
        .modal-title {
            font-size: 1.25em;
            font-weight: 700;
            color: var(--main-dark, #d94b3f);
            margin-bottom: 8px;
        }
        .modal-message {
            font-size: 1.07em;
            color: var(--text, #222b45);
            margin-bottom: 28px;
        }
        .modal-actions {
            display: flex;
            gap: 18px;
            justify-content: center;
        }
        .modal-btn {
            padding: 10px 26px;
            border-radius: 8px;
            border: none;
            font-size: 1.05em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
        }
        .modal-cancel {
            background: var(--main-light, #ffe5e2);
            color: var(--main-dark, #d94b3f);
        }
        .modal-cancel:hover {
            background: #f3bdb7;
        }
        .modal-logout {
            background: var(--main, #F77066);
            color: #fff;
        }
        .modal-logout:hover {
            background: var(--main-dark, #d94b3f);
        }
        @media (max-width: 768px) {
            .topbar {
                padding: 0 15px;
            }
            .container {
                margin: 20px 10px 30px 10px;
                padding: 20px 15px;
            }
        }
    </style>
</head>
<body>
<div class="topbar">
    <div class="logo-row">
        <img src="gbulogo.png" alt="University Logo" class="uni-logo">
        <span class="logo-text">Attendance Portal</span>
    </div>
    <nav class="nav">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="manage_users.php">Users</a>
        <a href="manage_courses.php">Courses</a>
        <a href="manage_departments.php">Departments</a>
        <a href="manage_sessions.php">Sessions</a>
        <a href="manage_enrollments.php">Enrollments</a>
        <a href="#" id="logoutLink">Logout</a>
    </nav>
</div>

<div class="container">
    <div class="dashboard-header">
        <div class="welcome-message">
            <h1>Welcome, <?= $admin_name ?></h1>
            <p>Administrator Dashboard</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_students'] ?></div>
            <div class="stat-label">Students</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_faculty'] ?></div>
            <div class="stat-label">Faculty</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_courses'] ?></div>
            <div class="stat-label">Courses</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_departments'] ?></div>
            <div class="stat-label">Departments</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_sessions'] ?></div>
            <div class="stat-label">Sessions</div>
        </div>
    </div>

    <h2>Management Options</h2>
    <div class="admin-actions">
        <div class="admin-card">
            <div class="card-icon">👥</div>
            <div class="card-title">User Management</div>
            <div class="card-description">
                Add, edit, or remove users. Manage student, faculty, parent, and admin accounts.
            </div>
            <a href="manage_users.php" class="card-link">Manage Users</a>
        </div>
        <div class="admin-card">
            <div class="card-icon">📚</div>
            <div class="card-title">Course Management</div>
            <div class="card-description">
                Create and manage courses, assign faculty, and set course schedules.
            </div>
            <a href="manage_courses.php" class="card-link">Manage Courses</a>
        </div>
        <div class="admin-card">
            <div class="card-icon">🏢</div>
            <div class="card-title">Department Management</div>
            <div class="card-description">
                Add and manage academic departments.
            </div>
            <a href="manage_departments.php" class="card-link">Manage Departments</a>
        </div>
        <div class="admin-card">
            <div class="card-icon">🗓️</div>
            <div class="card-title">Session Management</div>
            <div class="card-description">
                Create and manage academic sessions.
            </div>
            <a href="manage_sessions.php" class="card-link">Manage Sessions</a>
        </div>
        <div class="admin-card">
            <div class="card-icon">📝</div>
            <div class="card-title">Enrollment Management</div>
            <div class="card-description">
                Manage student enrollments in courses and sessions.
            </div>
            <a href="manage_enrollments.php" class="card-link">Manage Enrollments</a>
        </div>
    </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="custom-modal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-content">
        <button type="button" class="modal-close" id="closeLogoutModal" aria-label="Close">&times;</button>
        <div class="modal-icon">&#128274;</div>
        <div class="modal-title">Confirm Logout</div>
        <div class="modal-message">Are you sure you want to logout?</div>
        <div class="modal-actions">
            <button type="button" class="modal-btn modal-cancel" id="cancelLogoutBtn">Cancel</button>
            <button type="button" class="modal-btn modal-logout" id="confirmLogoutBtn">Logout</button>
        </div>
    </div>
</div>
<script>
    // Show modal on logout link click
    var logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('logoutModal').classList.add('active');
            setTimeout(function() {
                var cancelBtn = document.getElementById('cancelLogoutBtn');
                if (cancelBtn) cancelBtn.focus();
            }, 100);
        });
    }

    // Hide modal on close/cancel
    document.getElementById('closeLogoutModal').onclick = function() {
        document.getElementById('logoutModal').classList.remove('active');
    };
    document.getElementById('cancelLogoutBtn').onclick = function() {
        document.getElementById('logoutModal').classList.remove('active');
    };

    // Logout on confirm
    document.getElementById('confirmLogoutBtn').onclick = function() {
        window.location.href = '../public/logout.php';
    };

    // Hide modal on outside click
    window.onclick = function(event) {
        var modal = document.getElementById('logoutModal');
        if (event.target === modal) modal.classList.remove('active');
    };

    // Hide modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === "Escape") {
            var modal = document.getElementById('logoutModal');
            if (modal && modal.classList.contains('active')) {
                modal.classList.remove('active');
            }
        }
    });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
