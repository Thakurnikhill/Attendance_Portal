<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();
require_role('faculty');

$faculty_id = $_SESSION['user_id'];
$session_id = 1;

// Database connection check
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get assigned courses with prepared statement
$courses = [];
$stmt = $conn->prepare("SELECT c.course_id, c.course_code, c.course_name
                       FROM faculty_course fc
                       JOIN course c ON fc.course_id = c.course_id
                       WHERE fc.faculty_id = ? AND fc.session_id = ?");
$stmt->bind_param("ii", $faculty_id, $session_id);
$stmt->execute();
$courses = $stmt->get_result();

// Get attendance records
$attendance_records = [];
if (isset($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);
    $stmt = $conn->prepare("SELECT a.attendance_date, u.roll_number, u.full_name, a.status
                           FROM attendance a
                           JOIN users u ON a.student_id = u.user_id
                           WHERE a.course_id = ? AND a.session_id = ?
                           ORDER BY a.attendance_date DESC, u.roll_number");
    $stmt->bind_param("ii", $course_id, $session_id);
    $stmt->execute();
    $attendance_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Attendance - Faculty</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --main: #F77066;
            --main-dark: #d94b3f;
            --main-light: #ffe5e2;
            --white: #fff;
            --text: #222b45;
        }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            background: var(--main-light);
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
            max-width: 1000px;
            margin: 32px auto 0 auto;
            background: var(--white);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: var(--main-dark);
            margin-bottom: 25px;
        }
        .nav-actions {
            margin-bottom: 28px;
            display: flex;
            gap: 18px;
        }
        .nav-btn {
            background: var(--main);
            color: #fff;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 7px;
            font-weight: 600;
            font-size: 1em;
            transition: background 0.18s;
            box-shadow: 0 1px 4px #F7706622;
            display: inline-block;
        }
        .nav-btn:hover {
            background: var(--main-dark);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: var(--white);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 2px solid var(--main-light);
        }
        th {
            background: var(--main);
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background-color: var(--main-light);
        }
        .no-data {
            color: var(--text);
            padding: 20px;
            text-align: center;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            table {
                display: block;
                overflow-x: auto;
            }
        }
        .course-select-form {
            margin-bottom: 32px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .course-select-label {
            font-size: 1.08em;
            color: var(--main-dark);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 4px;
        }

        .course-select-icon {
            font-size: 1.3em;
            color: var(--main);
            vertical-align: middle;
        }

        .course-select-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .course-select-wrapper select {
            width: 100%;
            padding: 13px 42px 13px 16px;
            border-radius: 8px;
            border: 2px solid var(--main-light);
            background: var(--white);
            color: var(--text);
            font-size: 1.08em;
            font-weight: 500;
            appearance: none;
            outline: none;
            transition: border 0.18s;
            box-shadow: 0 1px 4px #F7706615;
        }

        .course-select-wrapper select:focus {
            border: 2px solid var(--main);
            background: #fff6f6;
        }

        .dropdown-arrow {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            font-size: 1.1em;
            color: var(--main-dark);
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
        <a href="mark_attendance.php">Mark Attendance</a>
        <a href="view_attendance.php" class="active">View Attendance</a>
        <a href="course_students.php">View Course Students</a>
        <a href="#" id="logoutLink">Logout</a>

    </nav>
</div>
<div class="container">
    <div class="nav-actions">
        <a href="javascript:history.back()" class="nav-btn">← Back</a>
        <a href="dashboard.php" class="nav-btn">🏠 Dashboard</a>
    </div>

    <h2>View Attendance Records</h2>

    <form method="get" class="course-select-form">
        <div class="course-select-wrapper">
            <select name="course_id" id="course_id" onchange="this.form.submit()" required>
                <option value="">-- Select Course --</option>
                <?php while ($course = $courses->fetch_assoc()): ?>
                    <option value="<?= $course['course_id'] ?>"
                        <?= (isset($_GET['course_id']) && $_GET['course_id'] == $course['course_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <span class="dropdown-arrow">&#9662;</span>
        </div>
    </form>


    <?php if (!empty($attendance_records)): ?>
        <table>
            <thead>
            <tr>
                <th>Date</th>
                <th>Roll Number</th>
                <th>Student Name</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($attendance_records as $rec): ?>
                <tr>
                    <td><?= date('d M Y', strtotime($rec['attendance_date'])) ?></td>
                    <td><?= htmlspecialchars($rec['roll_number']) ?></td>
                    <td><?= htmlspecialchars($rec['full_name']) ?></td>
                    <td><?= htmlspecialchars($rec['status']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif (isset($_GET['course_id'])): ?>
        <div class="no-data">No attendance records found for this course.</div>
    <?php endif; ?>
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
