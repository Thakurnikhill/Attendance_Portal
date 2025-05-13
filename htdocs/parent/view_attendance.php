<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role('parent');
require_once __DIR__ . '/../config/db.php';

$parent_id = $_SESSION['user_id'];
$parent_name = htmlspecialchars($_SESSION['full_name']);

// Fetch linked student info
$stmt = $conn->prepare("
    SELECT u.user_id, u.full_name, u.username, u.email, u.roll_number
    FROM users u
    JOIN parent_student ps ON ps.student_id = u.user_id
    WHERE ps.parent_id = ?
    LIMIT 1
");
$stmt->bind_param('i', $parent_id);
$stmt->execute();
$stmt->bind_result($student_id, $student_name, $student_username, $student_email, $student_roll);
$stmt->fetch();
$stmt->close();

if (!$student_id) {
    die("No student linked to this parent account.");
}

// Fetch all attendance records for this student
$stmt = $conn->prepare("
    SELECT attendance_date, status
    FROM attendance
    WHERE student_id = ?
    ORDER BY attendance_date DESC
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$attendance_records = $result->fetch_all(MYSQLI_ASSOC);

// Dummy profile image (SVG base64 or use a real image path)
$profile_img = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNDUiIGZpbGw9IiNGNzcwNjYiLz48Y2lyY2xlIGN4PSI1MCIgY3k9IjM1IiByPSIyMCIgZmlsbD0iI2ZmZiIvPjxwYXRoIGQ9Ik0zMCA3MGg0MHEyMCAwIDIwIDIwdjEwaC04MHYtMTBxMC0yMCAyMC0yMCIgZmlsbD0iI2ZmZiIvPjwvc3ZnPg==";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance - Attendance Portal</title>
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
            max-width: 900px;
            margin: 32px auto 0 auto;
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
            margin-bottom: 20px;
        }
        .profile-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 2px solid var(--main);
            object-fit: cover;
            background: #fff;
        }
        .welcome-text {
            font-size: 1.12em;
            color: var(--main-dark);
            font-weight: 600;
        }
        .role-badge {
            font-size: 0.97em;
            color: var(--main);
            background: var(--main-light);
            padding: 4px 12px;
            border-radius: 8px;
            font-weight: 500;
        }
        .attendance-table-container {
            margin-top: 30px;
            background: var(--main-light);
            border-radius: 10px;
            box-shadow: 0 1px 6px #F7706622;
            padding: 20px 12px;
            overflow-x: auto;
        }
        table.attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: 10px;
            overflow: hidden;
            font-size: 1em;
        }
        table.attendance-table th, table.attendance-table td {
            padding: 12px 10px;
            text-align: center;
        }
        table.attendance-table th {
            background: var(--main);
            color: #fff;
            font-weight: 600;
            border-bottom: 2px solid var(--main-dark);
        }
        table.attendance-table tr:nth-child(even) {
            background: #fff6f5;
        }
        table.attendance-table tr:nth-child(odd) {
            background: #fff;
        }
        table.attendance-table td.status-present {
            color: #43b77d;
            font-weight: 600;
        }
        table.attendance-table td.status-absent {
            color: var(--main);
            font-weight: 600;
        }
        .dashboard-actions {
            margin-top: 28px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .dashboard-actions a {
            background: var(--main);
            color: #fff;
            text-decoration: none;
            padding: 11px 30px;
            border-radius: 7px;
            font-weight: 600;
            font-size: 1em;
            transition: background 0.18s;
            box-shadow: 0 1px 4px #F7706622;
        }
        .dashboard-actions a:hover {
            background: var(--main-dark);
        }
        /* Profile Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.22);
            align-items: center;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: #fff;
            border-radius: 14px;
            max-width: 340px;
            width: 92vw;
            padding: 32px 28px 24px 28px;
            box-shadow: 0 4px 32px #F7706640;
            position: relative;
            animation: modalIn 0.18s;
        }
        @keyframes modalIn {
            from { transform: translateY(-40px) scale(0.97); opacity: 0; }
            to { transform: none; opacity: 1; }
        }
        .modal-close {
            position: absolute;
            top: 10px; right: 16px;
            background: none;
            border: none;
            font-size: 1.4em;
            color: var(--main-dark);
            cursor: pointer;
        }
        .modal-content h2 {
            margin: 0 0 10px 0;
            color: var(--main-dark);
            font-size: 1.25em;
            text-align: center;
        }
        .modal-content .profile-modal-img {
            display: block;
            margin: 0 auto 18px auto;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 2px solid var(--main);
        }
        .modal-content .profile-info {
            font-size: 1.07em;
        }
        .modal-content .profile-info strong {
            color: var(--main-dark);
            display: inline-block;
            width: 110px;
        }
        @media (max-width: 700px) {
            .container { padding: 16px 4vw; }
        }
        @media (max-width: 500px) {
            .topbar { padding: 10px 4vw; }
            .profile-section { flex-direction: column; }
            .attendance-table th, .attendance-table td { padding: 8px 4px; }
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
        <a href="view_attendance.php" class="active">Attendance</a>
        <a href="#" id="profile-link">Profile</a>
        <a href="#" id="logoutLink">Logout</a>

    </nav>
</div>

<!-- Profile Modal -->
<div class="modal" id="profile-modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeProfileModal()" aria-label="Close">&times;</button>
        <img src="<?= $profile_img ?>" class="profile-modal-img" alt="Profile">
        <h2>My Profile</h2>
        <div class="profile-info">
            <div><strong>Name:</strong> <?= $parent_name ?></div>
            <div><strong>Role:</strong> Parent</div>
        </div>
        <hr style="margin:18px 0 12px 0;">
        <h2>Student Info</h2>
        <div class="profile-info">
            <div><strong>Name:</strong> <?= htmlspecialchars($student_name) ?></div>
            <div><strong>Username:</strong> <?= htmlspecialchars($student_username) ?></div>
            <div><strong>Email:</strong> <?= htmlspecialchars($student_email) ?></div>
            <div><strong>Roll Number:</strong> <?= htmlspecialchars($student_roll) ?></div>
        </div>
    </div>
</div>

<div class="container">
    <div class="dashboard-header">
        <div class="profile-section">
            <img src="<?= $profile_img ?>" class="profile-img" alt="Profile">
            <div class="welcome-text">Attendance Records for <span style="color:var(--main)"><b><?= htmlspecialchars($student_name) ?></b></span></div>
        </div>
        <div class="role-badge">Parent</div>
    </div>

    <div class="attendance-table-container">
        <table class="attendance-table">
            <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($attendance_records) === 0): ?>
                <tr>
                    <td colspan="3" style="color:var(--main-dark);font-weight:600;">No attendance records found.</td>
                </tr>
            <?php else: ?>
                <?php $i = 1; foreach ($attendance_records as $row): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= date('d M Y', strtotime($row['attendance_date'])) ?></td>
                        <td class="status-<?= strtolower($row['status']) ?>">
                            <?= htmlspecialchars($row['status']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="dashboard-actions">
        <a href="dashboard.php">&larr; Back to Dashboard</a>
        <a href="download_report.php">Download Full Report</a>
    </div>
</div>

<script>
    // Profile Modal logic
    const profileLink = document.getElementById('profile-link');
    const profileModal = document.getElementById('profile-modal');
    function openProfileModal() {
        profileModal.classList.add('active');
    }
    function closeProfileModal() {
        profileModal.classList.remove('active');
    }
    profileLink.addEventListener('click', function(e) {
        e.preventDefault();
        openProfileModal();
    });
    window.addEventListener('click', function(e) {
        if (e.target === profileModal) closeProfileModal();
    });
    window.addEventListener('keydown', function(e) {
        if (e.key === "Escape") closeProfileModal();
    });
</script>
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
