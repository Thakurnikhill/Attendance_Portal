<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$error = '';

// If already logged in, redirect to the appropriate dashboard
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: /admin/dashboard.php");
            exit();
        case 'faculty':
            header("Location: /faculty/dashboard.php");
            exit();
        case 'student':
            header("Location: /student/dashboard.php");
            exit();
        case 'parent':
            header("Location: /parent/dashboard.php");
            exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = sanitize_input($_POST['password']);
    $role = sanitize_input($_POST['role']);

    if (empty($username) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, username, password, role, full_name FROM users WHERE username = ? AND role = ?");
        $stmt->bind_param('ss', $username, $role);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $db_username, $db_password, $db_role, $full_name);
            $stmt->fetch();
            if ($password === $db_password) { // Plain text password check (for demo only)
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $db_username;
                $_SESSION['role'] = $db_role;
                $_SESSION['full_name'] = $full_name;

                // Redirect based on role
                switch ($db_role) {
                    case 'admin':
                        header("Location: /admin/dashboard.php");
                        break;
                    case 'faculty':
                        header("Location: /faculty/dashboard.php");
                        break;
                    case 'student':
                        header("Location: /student/dashboard.php");
                        break;
                    case 'parent':
                        header("Location: /parent/dashboard.php");
                        break;
                    default:
                        header("Location: /index.php");
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username, password, or role.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Attendance Portal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --red: #e53935;
            --red-dark: #b71c1c;
            --red-light: #ffebee;
            --white: #fff;
            --gray: #f7f7f7;
            --text: #222b45;
            --background-color: #F77066;
        }
        html, body {
            height: 100%;
        }
        body {
            background-color: var(--background-color);
            min-height: 100vh;
            margin: 0;
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 16px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .login-outer {
            width: 85vw;
            max-width: 850px;
            min-width: 320px;
            max-height: 90vh;
            background: var(--white);
            border-radius: 22px;
            box-shadow: 0 6px 36px #e5737322;
            display: flex;
            align-items: stretch;
            overflow: hidden;
            margin: 48px auto 0 auto;
        }
        .illustration-side {
            background: linear-gradient(120deg, var(--red-light) 60%, var(--white) 100%);
            flex: 1.1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 110px;
            padding: 25px;
        }
        .logo-img-container {
            width: 160px;
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            box-shadow: none;
            border-radius: 0;
            margin: 0 auto;
            overflow: hidden;
            padding: 0;
        }
        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            background: none;
            border-radius: 0;
        }
        .login-form-side {
            flex: 1.3;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 35px;
            min-width: 300px;
        }
        .login-title {
            color: var(--red);
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }
        .login-desc {
            color: var(--text);
            font-size: 1.1em;
            margin-bottom: 25px;
        }
        .login-form label {
            color: var(--text);
            font-weight: 500;
            margin-bottom: 7px;
            display: block;
            font-size: 1em;
        }
        .login-form input, .login-form select {
            width: 100%;
            padding: 14px 20px;
            margin-bottom: 18px;
            border-radius: 8px;
            border: 1.7px solid var(--red-light);
            background: var(--gray);
            font-size: 1.1em;
            transition: border 0.2s;
            box-sizing: border-box;
        }
        .login-form input:focus, .login-form select:focus {
            border: 1.7px solid var(--red);
            outline: none;
            background: #fff6f6;
        }
        .password-wrapper {
            position: relative;
            width: 100%;
            margin-bottom: 18px;
        }
        .password-wrapper input[type="password"],
        .password-wrapper input[type="text"] {
            padding-right: 40px;
        }
        .password-eye {
            position: absolute;
            top: 37%;
            right: 13px;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .password-eye svg {
            width: 22px;
            height: 22px;
            fill: #b71c1c;
            background: none;
            display: block;
        }
        .login-form button {
            background: linear-gradient(90deg, var(--red) 0%, var(--red-dark) 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s;
            box-shadow: 0 2px 12px #e5737322;
            margin-top: 12px;
            letter-spacing: 1px;
            margin-bottom: 7px;
        }
        .login-links {
            margin-top: 18px;
            text-align: center;
            font-size: 1em;
        }
        .login-links a {
            color: var(--red-dark);
            text-decoration: none;
            margin: 0 6px;
        }
        .error-message {
            color: #b71c1c;
            background: #ffebee;
            border: 1.5px solid #ffcdd2;
            border-radius: 7px;
            padding: 12px 0;
            margin-bottom: 12px;
            text-align: center;
            font-size: 1em;
        }
        footer {
            margin: 8px 0 4px 0;
            text-align: center;
            font-size: 0.98em;
            color: #b55a52;
            width: 100%;
            letter-spacing: 0.2px;
        }
        @media (max-width: 600px) {
            body {
                font-size: 14px;
            }
            .login-outer {
                flex-direction: column;
                width: 95vw;
                max-height: 95vh;
            }
            .illustration-side {
                padding: 20px 0 0 0;
                justify-content: center;
            }
            .logo-img-container {
                width: 120px;
                height: 120px;
            }
            .login-form-side {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="login-outer">
        <div class="illustration-side">
            <div class="logo-img-container">
                <img src="public/gbulogo.png" alt="University Logo" class="logo-img" loading="lazy">
            </div>
        </div>
        <div class="login-form-side">
            <div class="login-title">Welcome Back!</div>
            <div class="login-desc">Login to your Attendance Portal account</div>
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form class="login-form" method="POST" action="">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>

                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required>
                    <span class="password-eye" id="togglePassword">
                        <svg id="eyeIcon" viewBox="0 0 24 24">
                            <path id="eyeOpen" d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12c-2.8 0-5-2.2-5-5s2.2-5 5-5 5 2.2 5 5-2.2 5-5 5zm0-8c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z"/>
                            <path id="eyeSlash" d="M2 2l20 20m-2.1-2.1C17.4 19.6 14.8 21 12 21 5 21 2 14 2 14s.6-1.3 1.8-2.7m3.1-3.1C8.4 7.6 10.1 7 12 7c7 0 10 7 10 7s-.5 1.2-1.6 2.6" style="display:none;"/>
                        </svg>
                    </span>
                </div>

                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="">--Select Role--</option>
                    <option value="admin">Admin</option>
                    <option value="faculty">Faculty</option>
                    <option value="student">Student</option>
                    <option value="parent">Parent</option>
                </select>
                <button type="submit">Login</button>
            </form>
            <div class="login-links">
                New student? <a href="public/register.php">Register here</a>
            </div>
        </div>
    </div>

    <script>
        const toggle = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const eyeOpen = document.getElementById('eyeOpen');
        const eyeSlash = document.getElementById('eyeSlash');

        toggle.addEventListener('click', () => {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // Toggle icons
            if (type === 'text') {
                eyeOpen.style.display = 'none';
                eyeSlash.style.display = 'block';
            } else {
                eyeOpen.style.display = 'block';
                eyeSlash.style.display = 'none';
            }
        });
    </script>
    <footer>
      &copy; <?php echo date('Y'); ?> Gautam Buddha University Attendance Portal &amp; developed by
      <a href="https://www.linkedin.com/in/nikhil-thakur-78a442260/"
         target="_blank"
         style="color:#000000;text-decoration:none;font-weight:500;">
        Nikhil Thakur
      </a>
    </footer>
</body>
</html>
