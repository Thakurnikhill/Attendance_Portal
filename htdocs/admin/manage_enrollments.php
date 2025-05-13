<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();
require_role('admin');

// Handle enrollment deletion (composite key)
$msg = '';
if (
    isset($_GET['delete_student']) && is_numeric($_GET['delete_student']) &&
    isset($_GET['delete_course']) && is_numeric($_GET['delete_course']) &&
    isset($_GET['delete_session']) && is_numeric($_GET['delete_session'])
) {
    $student_id = intval($_GET['delete_student']);
    $course_id = intval($_GET['delete_course']);
    $session_id = intval($_GET['delete_session']);
    $stmt = $conn->prepare("DELETE FROM student_course WHERE student_id = ? AND course_id = ? AND session_id = ?");
    $stmt->bind_param("iii", $student_id, $course_id, $session_id);
    if ($stmt->execute()) {
        $msg = "<div class='success-message'>Enrollment deleted successfully!</div>";
    } else {
        $msg = "<div class='error-message'>Error deleting enrollment.</div>";
    }
    $stmt->close();
}

// Fetch all enrollments with details
$enrollments = [];
$sql = "SELECT sc.student_id, sc.course_id, sc.session_id,
               u.full_name AS student_name, u.roll_number,
               c.course_code, c.course_name,
               s.session_name
        FROM student_course sc
        JOIN users u ON sc.student_id = u.user_id
        JOIN course c ON sc.course_id = c.course_id
        JOIN session s ON sc.session_id = s.session_id
        ORDER BY s.session_name DESC, u.full_name, c.course_code";
$result = $conn->query($sql);
if ($result) {
    $enrollments = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Enrollments - Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --main: #F77066;
            --main-dark: #d94b3f;
            --main-light: #ffe5e2;
            --white: #fff;
            --gray: #f7f7f7;
            --text: #222b45;
            --success: #4CAF50;
            --error: #f44336;
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
            font-family: inherit;
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
            cursor: pointer;
        }
        .topbar .nav a.active,
        .topbar .nav a:hover {
            background: rgba(255,255,255,0.15);
        }
        .container {
            max-width: 1200px;
            margin: 32px auto 40px auto; /* <-- added 40px bottom margin */
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 18px #F7706622;
            padding: 28px 30px 22px 30px;
        }

        h1 {
            color: var(--main-dark);
            margin-bottom: 25px;
        }
        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 18px;
            border-radius: 7px;
            margin-bottom: 18px;
        }
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px 18px;
            border-radius: 7px;
            margin-bottom: 18px;
        }
        .enrollments-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--white);
        }
        .enrollments-table th {
            background: var(--main);
            color: white;
            text-align: left;
            padding: 12px 15px;
        }
        .enrollments-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .enrollments-table tr:hover {
            background-color: var(--main-light);
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn {
            padding: 6px 14px;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 0.95em;
            cursor: pointer;
            border: none;
            font-weight: 500;
        }
        .btn-delete { background: var(--error); }
        .btn-delete:hover { background: #b71c1c; }
        .add-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .enrollments-table { font-size: 0.97em; }
            .topbar { padding: 0 10px; }
        }
        @media (max-width: 500px) {
            .enrollments-table th, .enrollments-table td { padding: 8px 6px; }
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
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_users.php">Users</a>
        <a href="manage_courses.php">Courses</a>
        <a href="manage_departments.php">Departments</a>
        <a href="manage_sessions.php">Sessions</a>
        <a href="manage_enrollments.php" class="active">Enrollments</a>
        <a href="#" id="logoutLink">Logout</a>
    </nav>
</div>

<div class="container">
    <h1>Enrollment Management</h1>
    <?= $msg ?>

    <a href="add_enrollment.php" class="add-btn">+ Add New Enrollment</a>

    <table class="enrollments-table">
        <thead>
        <tr>
            <th>Session</th>
            <th>Student Name</th>
            <th>Roll Number</th>
            <th>Course</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($enrollments as $enr): ?>
            <tr>
                <td><?= htmlspecialchars($enr['session_name']) ?></td>
                <td><?= htmlspecialchars($enr['student_name']) ?></td>
                <td><?= htmlspecialchars($enr['roll_number']) ?></td>
                <td><?= htmlspecialchars($enr['course_code'] . ' - ' . $enr['course_name']) ?></td>
                <td class="action-buttons">
                    <a href="manage_enrollments.php?delete_student=<?= $enr['student_id'] ?>&delete_course=<?= $enr['course_id'] ?>&delete_session=<?= $enr['session_id'] ?>"
                       onclick="return confirm('Are you sure you want to delete this enrollment?')"
                       class="btn btn-delete">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
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
</body>
</html>
