<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();
require_role('admin');

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header('Location: manage_users.php');
    exit();
}

$user_id = intval($_GET['user_id']);

// Fetch departments for dropdown
$departments = [];
$deptStmt = $conn->query("SELECT dept_id, dept_name FROM department ORDER BY dept_name");
if ($deptStmt) {
    $departments = $deptStmt->fetch_all(MYSQLI_ASSOC);
}

// Fetch user info
$stmt = $conn->prepare("SELECT username, full_name, email, role, roll_number, dept_id FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    echo "<p>User not found.</p>";
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $roll_number = trim($_POST['roll_number']);
    $dept_id = (in_array($role, ['faculty', 'student'])) ? intval($_POST['dept_id']) : null;

    // Validation
    if (empty($username) || empty($full_name) || empty($email) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check for username/email uniqueness (excluding current user)
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $checkStmt->bind_param("ssi", $username, $email, $user_id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            $error = "Username or email already taken by another user.";
        }
        $checkStmt->close();
    }

    if (!$error) {
        $updateStmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, roll_number = ?, dept_id = ? WHERE user_id = ?");
        $updateStmt->bind_param("ssssiii", $username, $full_name, $email, $role, $roll_number, $dept_id, $user_id);

        if ($updateStmt->execute()) {
            $success = "User updated successfully.";
            // Refresh user data
            $stmt = $conn->prepare("SELECT username, full_name, email, role, roll_number, dept_id FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Failed to update user. Please try again.";
        }
        $updateStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit User</title>
    <link rel="stylesheet" href="../public/style.css" />
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            margin: 50px auto;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #FF5733;
            font-size: 2em;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 1.1em;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #FF5733;
            box-shadow: 0 0 8px rgba(255, 87, 51, 0.5);
            outline: none;
        }
        .btn-submit {
            background-color: #FF5733;
            color: white;
            padding: 12px 25px;
            font-size: 1.1em;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .btn-submit:hover {
            background-color: #c44122;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #FF5733;
            font-size: 1em;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #c44122;
        }
        .error-message, .success-message {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
        .error-message {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }
        .success-message {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h1>Edit User</h1>
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username <span aria-hidden="true" style="color:red;">*</span></label>
            <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="full_name">Full Name <span aria-hidden="true" style="color:red;">*</span></label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email <span aria-hidden="true" style="color:red;">*</span></label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="role">Role <span aria-hidden="true" style="color:red;">*</span></label>
            <select id="role" name="role" required>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="faculty" <?= $user['role'] === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="parent" <?= $user['role'] === 'parent' ? 'selected' : '' ?>>Parent</option>
            </select>
        </div>

        <div class="form-group" id="deptGroup" style="display: <?= in_array($user['role'], ['faculty', 'student']) ? 'block' : 'none' ?>;">
            <label for="dept_id">Department <span aria-hidden="true" style="color:red;">*</span></label>
            <select id="dept_id" name="dept_id" <?= in_array($user['role'], ['faculty', 'student']) ? 'required' : '' ?>>
                <option value="">-- Select Department --</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['dept_id'] ?>" <?= $dept['dept_id'] == $user['dept_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($dept['dept_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="roll_number">Roll Number</label>
            <input type="text" id="roll_number" name="roll_number" value="<?= htmlspecialchars($user['roll_number']) ?>">
        </div>

        <button type="submit" class="btn-submit">Update User</button>
    </form>

    <a href="manage_users.php" class="back-link">Back to Manage Users</a>
</div>

<script>
    // Show/hide department field based on role selection
    document.getElementById('role').addEventListener('change', function() {
        const deptGroup = document.getElementById('deptGroup');
        const deptSelect = document.getElementById('dept_id');
        if (this.value === 'faculty' || this.value === 'student') {
            deptGroup.style.display = 'block';
            deptSelect.required = true;
        } else {
            deptGroup.style.display = 'none';
            deptSelect.required = false;
            deptSelect.value = '';
        }
    });
</script>

</body>
</html>
