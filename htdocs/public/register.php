<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_submit'])) {
    // Student Details
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $roll_number = sanitize_input($_POST['roll_number']);
    $dept_id = intval($_POST['dept_id']);

    // Parent Details
    $parent_name = sanitize_input($_POST['parent_name']);
    $parent_email = sanitize_input($_POST['parent_email']);
    $parent_username = sanitize_input($_POST['parent_username']);
    $parent_password = sanitize_input($_POST['parent_password']);

    // Validation
    if (!$username || !$password || !$confirm_password || !$full_name || !$email || !$roll_number || !$dept_id ||
        !$parent_name || !$parent_email || !$parent_username || !$parent_password) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $conn->autocommit(FALSE);

        try {
            // Check if student username/email/roll_number exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=? OR roll_number=?");
            $stmt->bind_param('sss', $username, $email, $roll_number);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) throw new Exception("Student username, email, or roll number already exists.");
            $stmt->close();

            // Check if parent username/email exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=?");
            $stmt->bind_param('ss', $parent_username, $parent_email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) throw new Exception("Parent username or email already exists.");
            $stmt->close();

            // Insert Student
            $stmt = $conn->prepare(
                "INSERT INTO users (username, password, full_name, email, role, dept_id, roll_number)
                VALUES (?, ?, ?, ?, 'student', ?, ?)"
            );
            $stmt->bind_param('ssssiss', $username, $password, $full_name, $email, $dept_id, $roll_number);
            if (!$stmt->execute()) throw new Exception("Student registration failed.");
            $student_id = $stmt->insert_id;
            $stmt->close();

            // Insert Parent (with same dept_id)
            $stmt = $conn->prepare(
                "INSERT INTO users (username, password, full_name, email, role, dept_id)
                VALUES (?, ?, ?, ?, 'parent', ?)"
            );
            $stmt->bind_param('ssssi', $parent_username, $parent_password, $parent_name, $parent_email, $dept_id);
            if (!$stmt->execute()) throw new Exception("Parent registration failed.");
            $parent_id = $stmt->insert_id;
            $stmt->close();

            // Link Parent-Student
            $stmt = $conn->prepare(
                "INSERT INTO parent_student (parent_id, student_id) VALUES (?, ?)"
            );
            $stmt->bind_param('ii', $parent_id, $student_id);
            if (!$stmt->execute()) throw new Exception("Failed to link parent to student.");
            $stmt->close();

            $conn->commit();
            $success = "Registration successful! You can now <a href='../index.php'>login</a>.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration - Attendance Portal</title>
    <style>
        :root {
            --red: #e53935;
            --red-dark: #b71c1c;
            --red-light: #ffebee;
            --white: #fff;
            --border: #e57373;
            --text: #222b45;
        }
        body {
            background: linear-gradient(135deg, var(--red-light) 0%, var(--white) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 18px #e5737322;
            width: 340px;
            padding: 22px 22px 14px 22px;
            border: 1.5px solid var(--red);
        }
        h2 {
            text-align: center;
            color: var(--red);
            margin-bottom: 14px;
            font-size: 1.22em;
            letter-spacing: 1px;
        }
        h3 {
            text-align: center;
            color: var(--red-dark);
            margin-bottom: 10px;
            font-size: 1.05em;
        }
        label {
            font-weight: 500;
            font-size: 13px;
            color: var(--text);
            margin-bottom: 2px;
            display: block;
        }
        input, select {
            width: 100%;
            padding: 7px 9px;
            margin: 0 0 11px 0;
            border-radius: 6px;
            border: 1.2px solid var(--border);
            background: var(--white);
            font-size: 13px;
            transition: border 0.2s;
            box-sizing: border-box;
        }
        input:focus, select:focus {
            border: 1.5px solid var(--red);
            outline: none;
            background: var(--red-light);
        }
        button {
            background: linear-gradient(90deg, var(--red) 0%, var(--red-dark) 100%);
            color: #fff;
            padding: 9px 0;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 15px;
            font-weight: 600;
            margin-top: 2px;
            box-shadow: 0 1px 4px #e5737322;
            transition: background 0.2s;
        }
        button:hover {
            background: linear-gradient(90deg, var(--red-dark) 0%, var(--red) 100%);
        }
        .message {
            text-align: center;
            margin-bottom: 10px;
            padding: 7px 0;
            border-radius: 7px;
            font-size: 13px;
        }
        .error { color: #b71c1c; background: #ffebee; border: 1px solid #ffcdd2; }
        .success { color: #207b3c; background: #eafff2; border: 1px solid #b6f7ce; }
        .step { display: none; }
        .step.active { display: block; }
        .switch-step {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-top: 10px;
        }
        .switch-step button {
            width: 48%;
            margin: 0;
            background: var(--red-light);
            color: var(--red);
            font-weight: 500;
            box-shadow: none;
            border: 1px solid var(--red);
        }
        .switch-step button:hover {
            background: var(--red);
            color: #fff;
        }
        p.login-link {
            text-align: center;
            margin: 14px 0 0 0;
            font-size: 13px;
        }
        p.login-link a {
            color: var(--red);
            text-decoration: underline;
        }
        @media (max-width: 400px) {
            .container { width: 99vw; padding: 7px 2vw 7px 2vw; }
        }
    </style>
    <script>
        function showParentStep() {
            var required = ['username','full_name','email','roll_number','dept_id','password','confirm_password'];
            for (var i = 0; i < required.length; i++) {
                var el = document.forms['regForm'][required[i]];
                if (!el.value) {
                    alert("Please fill all student details.");
                    el.focus();
                    return false;
                }
            }
            if (document.getElementById('password').value !== document.getElementById('confirm_password').value) {
                alert("Passwords do not match.");
                document.getElementById('confirm_password').focus();
                return false;
            }
            document.getElementById('student-step').classList.remove('active');
            document.getElementById('parent-step').classList.add('active');
            return false;
        }
        function backToStudentStep() {
            document.getElementById('parent-step').classList.remove('active');
            document.getElementById('student-step').classList.add('active');
            return false;
        }
        function validateParentStep() {
            var required = ['parent_name','parent_email','parent_username','parent_password'];
            for (var i = 0; i < required.length; i++) {
                var el = document.forms['regForm'][required[i]];
                if (!el.value) {
                    alert("Please fill all parent details.");
                    el.focus();
                    return false;
                }
            }
            if (document.getElementById('parent_password').value.length < 4) {
                alert("Parent password should be at least 4 characters.");
                document.getElementById('parent_password').focus();
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
<div class="container">
    <h2>Student Registration</h2>
    <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
        <form name="regForm" method="POST" action="" autocomplete="off">
            <div id="student-step" class="step active">
                <h3>Student Details</h3>
                <label>Username*:</label>
                <input type="text" name="username" required>

                <label>Full Name*:</label>
                <input type="text" name="full_name" required>

                <label>Email*:</label>
                <input type="email" name="email" required>

                <label>Roll Number*:</label>
                <input type="text" name="roll_number" required>

                <label>Department*:</label>
                <select name="dept_id" required>
                    <option value="">--Select Department--</option>
                    <option value="1">Computer Science Engineering</option>
                    <option value="2">Information Technology</option>
                    <option value="3">Electronics and Communication Engineering</option>
                </select>

                <label>Password*:</label>
                <input type="password" name="password" id="password" required>

                <label>Confirm Password*:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <button type="button" onclick="showParentStep()">Next</button>
            </div>

            <div id="parent-step" class="step">
                <h3>Parent Details</h3>
                <label>Parent Name*:</label>
                <input type="text" name="parent_name" required>

                <label>Parent Email*:</label>
                <input type="email" name="parent_email" required>

                <label>Parent Username*:</label>
                <input type="text" name="parent_username" required>

                <label>Parent Password*:</label>
                <input type="password" name="parent_password" id="parent_password" required>

                <div class="switch-step">
                    <button type="button" onclick="backToStudentStep()">Back</button>
                    <button type="submit" name="final_submit" onclick="return validateParentStep();">Register</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
    <p class="login-link">
        Already have an account? <a href="../index.php">Login here</a>
    </p>
</div>
</body>
</html>
