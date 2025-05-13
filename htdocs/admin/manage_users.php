<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();
require_role('admin');

$msg = '';

// Handle user deletion with proper foreign key constraints
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    try {
        $conn->begin_transaction();

        // Check if user exists
        $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $msg = "<div class='error-message'>User not found.</div>";
        } else {
            // Handle role-specific dependencies
            if ($user['role'] === 'faculty') {
                $stmt = $conn->prepare("DELETE FROM faculty_course WHERE faculty_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            } elseif ($user['role'] === 'parent') {
                $stmt = $conn->prepare("DELETE FROM parent_student WHERE parent_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            } elseif ($user['role'] === 'student') {
                $stmt = $conn->prepare("DELETE FROM student_course WHERE student_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM parent_student WHERE student_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // Delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $msg = "<div class='success-message'>User deleted successfully!</div>";
            }
            $stmt->close();
        }

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $msg = "<div class='error-message'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Get selected role filter from GET parameter, default to empty (no filter)
$selectedRole = isset($_GET['role_filter']) ? $_GET['role_filter'] : '';

// Prepare SQL with optional role filter
$sql = "SELECT 
            u.user_id, 
            u.username, 
            u.full_name, 
            u.email, 
            u.role, 
            u.roll_number, 
            d.dept_name
        FROM users u
        LEFT JOIN department d ON u.dept_id = d.dept_id";

$params = [];
$types = "";
if ($selectedRole !== '' && in_array($selectedRole, ['admin', 'faculty', 'student', 'parent'])) {
    $sql .= " WHERE u.role = ?";
    $params[] = $selectedRole;
    $types .= "s";
}

$sql .= " ORDER BY FIELD(u.role, 'admin','faculty','student','parent'), u.full_name";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Dashboard</title>
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
        }

        .topbar .nav {
            display: flex;
            gap: 18px;
        }

        .topbar .nav a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
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
            margin: 32px auto;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 18px #F7706622;
            padding: 28px 30px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        h1 {
            color: var(--main-dark);
            margin: 0;
        }

        .btn-add {
            background: var(--success);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
            margin-top: 10px;
        }

        .btn-add:hover {
            background: #45a049;
        }

        .filter-form {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .filter-form label {
            font-weight: 600;
            color: var(--text);
        }

        .filter-form select {
            padding: 6px 10px;
            font-size: 1em;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .filter-form button {
            background: var(--main);
            border: none;
            color: white;
            padding: 7px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
        }

        .filter-form button:hover {
            background: var(--main-dark);
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
        }

        .users-table th {
            background: var(--main);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }

        .users-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .users-table tr:hover {
            background-color: var(--main-light);
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.85em;
            font-weight: 500;
            text-transform: capitalize;
        }

        .role-admin { background: #4b0082; color: white; }
        .role-faculty { background: #1976d2; color: white; }
        .role-student { background: #43a047; color: white; }
        .role-parent { background: #ff9800; color: white; }

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
        }

        .btn-edit { background: #ff9800; }
        .btn-delete { background: var(--error); }
        .btn-edit:hover { background: #e65100; }
        .btn-delete:hover { background: #b71c1c; }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 20px;
            }
            .users-table {
                font-size: 0.9em;
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
        <a href="manage_users.php" class="active">Users</a>
        <a href="manage_courses.php">Courses</a>
        <a href="manage_departments.php">Departments</a>
        <a href="manage_sessions.php">Sessions</a>
        <a href="manage_enrollments.php">Enrollments</a>
        <a href="#" id="logoutLink">Logout</a>
    </nav>
</div>

<div class="container">
    <div class="header-row">
        <h1>User Management</h1>
        <a href="add_user.php" class="btn-add">+ Add User</a>
    </div>

    <?= $msg ?>

    <!-- Role filter form -->
    <form method="GET" class="filter-form" aria-label="Filter users by role">
        <label for="role_filter">Filter by Role:</label>
        <select name="role_filter" id="role_filter" onchange="this.form.submit()">
            <option value="" <?= $selectedRole === '' ? 'selected' : '' ?>>All Roles</option>
            <option value="admin" <?= $selectedRole === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="faculty" <?= $selectedRole === 'faculty' ? 'selected' : '' ?>>Faculty</option>
            <option value="student" <?= $selectedRole === 'student' ? 'selected' : '' ?>>Student</option>
            <option value="parent" <?= $selectedRole === 'parent' ? 'selected' : '' ?>>Parent</option>
        </select>
        <noscript><button type="submit">Filter</button></noscript>
    </form>

    <table class="users-table" aria-label="User Management Table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Department</th>
                <th>Roll Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) === 0): ?>
                <tr><td colspan="7" style="text-align:center; padding: 20px;">No users found.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
                                <?= ucfirst(htmlspecialchars($user['role'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($user['dept_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['roll_number'] ?? 'N/A') ?></td>
                        <td class="action-buttons">
                            <a href="edit_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-edit">Edit</a>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <button class="btn btn-delete" onclick="confirmDelete(<?= $user['user_id'] ?>)">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    function confirmDelete(userId) {
        if (confirm('Are you sure you want to delete this user?\nThis action cannot be undone.')) {
            window.location.href = `manage_users.php?delete=${userId}`;
        }
    }
</script>

</body>
</html>
