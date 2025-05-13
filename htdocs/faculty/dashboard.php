<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role('faculty');
require_once __DIR__ . '/../config/db.php';


$faculty_id = $_SESSION['user_id'];
$faculty_name = htmlspecialchars($_SESSION['full_name']);
$today = date('Y-m-d');

// Fetch today's schedule (example assumes schedule table exists)
$schedule = [];
try {
    $sql = "SELECT course_name, class_time, room 
            FROM schedule 
            WHERE faculty_id = ? AND date = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL error: " . $conn->error);
    }

    if (!$stmt) throw new Exception("SQL error: " . $conn->error);
    $stmt->bind_param('is', $faculty_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $schedule = [];
}

// Fetch present students count per course for today for this faculty
$attendanceData = [];
try {
    $sql = "SELECT c.course_name, COUNT(a.student_id) AS present_count
            FROM attendance a
            JOIN course c ON a.course_id = c.course_id
            WHERE a.status = 'Present' 
              AND a.attendance_date = ?
              AND c.course_id IN (
                  SELECT course_id FROM faculty_course WHERE faculty_id = ?
              )
            GROUP BY c.course_name";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("SQL error: " . $conn->error);
    $stmt->bind_param('si', $today, $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attendanceData[$row['course_name']] = (int)$row['present_count'];
    }
} catch (Exception $e) {
    $attendanceData = [];
}

// Dummy profile image (SVG base64 or use a real image path)
$profile_img = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNDUiIGZpbGw9IiNGNzcwNjYiLz48Y2lyY2xlIGN4PSI1MCIgY3k9IjM1IiByPSIyMCIgZmlsbD0iI2ZmZiIvPjxwYXRoIGQ9Ik0zMCA3MGg0MHEyMCAwIDIwIDIwdjEwaC04MHYtMTBxMC0yMCAyMC0yMCIgZmlsbD0iI2ZmZiIvPjwvc3ZnPg==";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard - Attendance Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            font-size: 1.15em;
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
        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 25px;
        }
        .schedule-card, .chart-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px #F7706622;
        }
        .schedule-item {
            padding: 15px;
            background: var(--main-light);
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .course-name {
            color: var(--main-dark);
            font-weight: 600;
            margin-bottom: 5px;
        }
        .course-time {
            color: var(--text);
            font-size: 0.95em;
        }
        #attendanceChart {
            max-height: 400px;
        }
        @media (max-width: 768px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 700px) {
            .container { padding: 16px 4vw; }
        }
        @media (max-width: 500px) {
            .topbar { padding: 10px 4vw; }
            .profile-section { flex-direction: column; }
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
        <a href="mark_attendance.php">Mark Attendance</a>
        <a href="view_attendance.php">View Attendance</a>
        <a href="course_students.php">View Course Students</a>
        <a href="#" id="logoutLink">Logout</a>

    </nav>
</div>

<div class="container">
    <div class="dashboard-header">
        <div class="profile-section">
            <img src="<?= $profile_img ?>" class="profile-img" alt="Profile">
            <div class="welcome-text">Welcome, <span style="color:var(--main)"><b><?= $faculty_name ?></b></span></div>
        </div>
        <div class="role-badge">Faculty</div>
    </div>

    <div class="dashboard-content">
        <div class="chart-card">
            <h3 style="color:var(--main-dark);margin-top:0;">Today's Attendance</h3>
            <canvas id="attendanceChart"></canvas>
        </div>
        <div class="schedule-card">
            <h3 style="color:var(--main-dark);margin-top:0;">Today's Schedule</h3>
            <?php if (!empty($schedule)): ?>
                <?php foreach ($schedule as $class): ?>
                    <div class="schedule-item">
                        <div class="course-name"><?= htmlspecialchars($class['course_name']) ?></div>
                        <div class="course-time"><?= htmlspecialchars($class['class_time']) ?></div>
                        <div class="course-room"><?= htmlspecialchars($class['room']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color:var(--text);">No classes scheduled for today</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const chartData = {
        labels: <?= json_encode(array_keys($attendanceData)) ?>,
        datasets: [{
            label: 'Present Students',
            data: <?= json_encode(array_values($attendanceData)) ?>,
            backgroundColor: '#F77066',
            borderColor: '#d94b3f',
            borderWidth: 2,
            borderRadius: 6
        }]
    };
    const config = {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { color: '#222b45' },
                    grid: { color: '#ffe5e2' }
                },
                x: {
                    ticks: { color: '#222b45' },
                    grid: { display: false }
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { color: '#222b45' }
                }
            }
        }
    };
    window.addEventListener('DOMContentLoaded', () => {
        new Chart(document.getElementById('attendanceChart'), config);
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
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
