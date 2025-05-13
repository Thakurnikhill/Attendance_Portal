<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
require_login();
require_role('admin');

$departments = [];
$stmt = $conn->query("SELECT dept_id, dept_name FROM department ORDER BY dept_name");
if ($stmt) {
    $departments = $stmt->fetch_all(MYSQLI_ASSOC);
}

$error = '';
$success = '';
$formData = [
    'username' => '',
    'full_name' => '',
    'email' => '',
    'role' => 'student',
    'roll_number' => '',
    'dept_id' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = array_map('trim', $_POST);
    $role = $formData['role'];
    $dept_id = (in_array($role, ['faculty', 'student'])) ? intval($formData['dept_id']) : null;

    // Validation
    if (empty($formData['username']) || 
        empty($formData['full_name']) || 
        empty($formData['email']) || 
        empty($role)) {
        $error = "All required fields must be filled.";
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (in_array($role, ['faculty', 'student']) && empty($dept_id)) {
        $error = "Department is required for this role.";
    } else {
        // Check for existing user
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $formData['username'], $formData['email']);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            // Insert new user
            $defaultPassword = password_hash('default123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users 
                (username, password, full_name, email, role, dept_id, roll_number) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssis",
                $formData['username'],
                $defaultPassword,
                $formData['full_name'],
                $formData['email'],
                $role,
                $dept_id,
                $formData['roll_number']
            );

            if ($stmt->execute()) {
                $success = "User created successfully! Default password: <strong>default123</strong>";
                // Clear form on success
                $formData = array_fill_keys(array_keys($formData), '');
            } else {
                $error = "Error creating user: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New User - Admin Dashboard</title>
    <style>
        :root {
            --main: #F77066;
            --main-dark: #d94b3f;
            --main-light: #ffe5e2;
            --white: #fff;
            --text: #222b45;
            --success: #4CAF50;
            --error: #f44336;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
        }

        .form-container {
            background: var(--white);
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        h1 {
            color: var(--main-dark);
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            border-color: var(--main);
            outline: none;
            box-shadow: 0 0 5px rgba(247, 112, 102, 0.3);
        }

        .required {
            color: var(--error);
            margin-left: 3px;
        }

        .btn-submit {
            background-color: var(--main);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .btn-submit:hover {
            background-color: var(--main-dark);
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--main);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Add New User</h1>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="message success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username<span class="required">*</span></label>
                <input type="text" name="username" value="<?= htmlspecialchars($formData['username']) ?>" required>
            </div>

            <div class="form-group">
                <label>Full Name<span class="required">*</span></label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($formData['full_name']) ?>" required>
            </div>

            <div class="form-group">
                <label>Email<span class="required">*</span></label>
                <input type="email" name="email" value="<?= htmlspecialchars($formData['email']) ?>" required>
            </div>

            <div class="form-group">
                <label>Role<span class="required">*</span></label>
                <select name="role" id="roleSelect" required>
                    <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="faculty" <?= $formData['role'] === 'faculty' ? 'selected' : '' ?>>Faculty</option>
                    <option value="student" <?= $formData['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="parent" <?= $formData['role'] === 'parent' ? 'selected' : '' ?>>Parent</option>
                </select>
            </div>

            <div class="form-group" id="deptGroup" style="display: <?= in_array($formData['role'], ['faculty', 'student']) ? 'block' : 'none' ?>">
                <label>Department<span class="required">*</span></label>
                <select name="dept_id">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['dept_id'] ?>" 
                            <?= ($dept['dept_id'] == $formData['dept_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['dept_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Roll Number</label>
                <input type="text" name="roll_number" value="<?= htmlspecialchars($formData['roll_number']) ?>">
            </div>

            <button type="submit" class="btn-submit">Create User</button>
        </form>

        <a href="manage_users.php" class="back-link">← Back to User Management</a>
    </div>

    <script>
        // Toggle department visibility based on role
        const roleSelect = document.getElementById('roleSelect');
        const deptGroup = document.getElementById('deptGroup');

        function toggleDepartment() {
            const showDept = ['faculty', 'student'].includes(roleSelect.value);
            deptGroup.style.display = showDept ? 'block' : 'none';
            deptGroup.querySelector('select').required = showDept;
        }

        roleSelect.addEventListener('change', toggleDepartment);
        toggleDepartment(); // Initial check
    </script>
</body>
</html>
