<?php
session_start();
include __DIR__ . '/db_connect.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($email === '' || $new_password === '' || $confirm_password === '') {

        $error = 'Please fill in all fields.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = 'Please enter a valid email address.';

    } elseif (strlen($new_password) < 6) {

        $error = 'Password must contain at least 6 characters.';

    } elseif ($new_password !== $confirm_password) {

        $error = 'Passwords do not match.';

    } else {

        /*
         * Check whether the email exists
         */
        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id, full_name, email
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error = 'Database error. Please try again.';

        } else {

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {

                $user = mysqli_fetch_assoc($result);

                /*
                 * Hash the new password
                 */
                $hashedPassword = password_hash(
                    $new_password,
                    PASSWORD_DEFAULT
                );

                /*
                 * Update password
                 */
                $update = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET password = ?
                     WHERE user_id = ?"
                );

                if (!$update) {

                    $error = 'Unable to update password.';

                } else {

                    mysqli_stmt_bind_param(
                        $update,
                        "si",
                        $hashedPassword,
                        $user['user_id']
                    );

                    if (mysqli_stmt_execute($update)) {

                        $message = 'Password changed successfully! You can now login with your new password.';

                    } else {

                        $error = 'Unable to change password. Please try again.';
                    }

                    mysqli_stmt_close($update);
                }

            } else {

                $error = 'No SkillMate account was found with this email address.';
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>

<meta charset="utf-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1">

<title>SkillMate — Forgot Password</title>

<style>

:root {

    --primary: #4f46e5;
    --primary-dark: #4338ca;
    --green: #22c55e;
    --bg: #f5f7ff;
    --surface: #ffffff;
    --text: #14213d;
    --muted: #64748b;
    --border: #e2e8f0;

}

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    min-height: 100vh;

    display: flex;

    align-items: center;

    justify-content: center;

    padding: 20px;

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    color: var(--text);

    background:
        linear-gradient(
            135deg,
            #f8fbff 0%,
            #eef2ff 50%,
            #f0fdf4 100%
        );

}

.card {

    width: 430px;

    max-width: 100%;

    background: rgba(255,255,255,0.96);

    padding: 32px;

    border-radius: 26px;

    border: 1px solid rgba(15,23,42,0.08);

    box-shadow:
        0 25px 70px rgba(15,23,42,0.10);

}

.logo {

    width: 64px;

    height: 64px;

    border-radius: 18px;

    overflow: hidden;

    margin-bottom: 20px;

    box-shadow:
        0 12px 30px rgba(79,70,229,0.15);

}

.logo img {

    width: 100%;

    height: 100%;

    object-fit: cover;

}

h1 {

    margin: 0 0 8px;

    font-size: 28px;

}

.subtitle {

    color: var(--muted);

    line-height: 1.6;

    margin-bottom: 25px;

}

label {

    display: block;

    font-weight: 700;

    font-size: 14px;

    margin-bottom: 7px;

}

.input {

    width: 100%;

    padding: 14px 15px;

    margin-bottom: 16px;

    border-radius: 13px;

    border: 1px solid var(--border);

    outline: none;

    font-size: 15px;

    background: #fff;

    color: var(--text);

    transition: 0.2s;

}

.input:focus {

    border-color: rgba(79,70,229,0.45);

    box-shadow:
        0 0 0 4px rgba(79,70,229,0.08);

}

.btn {

    width: 100%;

    padding: 14px;

    border: none;

    border-radius: 13px;

    cursor: pointer;

    font-size: 15px;

    font-weight: 800;

    color: white;

    background:
        linear-gradient(
            135deg,
            var(--green),
            #16a34a
        );

    box-shadow:
        0 12px 25px rgba(34,197,94,0.16);

    transition: 0.2s;

}

.btn:hover {

    transform: translateY(-2px);

}

.message {

    padding: 13px 15px;

    border-radius: 12px;

    margin-bottom: 18px;

    background: #ecfdf5;

    color: #166534;

    border: 1px solid #bbf7d0;

    line-height: 1.5;

    font-size: 14px;

}

.error {

    padding: 13px 15px;

    border-radius: 12px;

    margin-bottom: 18px;

    background: #fef2f2;

    color: #991b1b;

    border: 1px solid #fecaca;

    line-height: 1.5;

    font-size: 14px;

}

.links {

    margin-top: 20px;

    text-align: center;

}

.links a {

    color: var(--primary);

    text-decoration: none;

    font-weight: 700;

    font-size: 14px;

}

.links a:hover {

    text-decoration: underline;

}

.success-actions {

    margin-top: 18px;

}

.success-btn {

    display: block;

    text-align: center;

    padding: 13px;

    border-radius: 13px;

    background: var(--primary);

    color: white;

    text-decoration: none;

    font-weight: 800;

}

.success-btn:hover {

    background: var(--primary-dark);

}

</style>

</head>

<body>

<div class="card">

    <div class="logo">

        <img
            src="PROJECT LOGO.png"
            alt="SkillMate Logo"
        >

    </div>

    <h1>Forgot Password?</h1>

    <div class="subtitle">

        Enter your registered SkillMate email address
        and create a new password.

    </div>


    <?php if ($error !== ''): ?>

        <div class="error">

            <?php
            echo htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

        </div>

    <?php endif; ?>


    <?php if ($message !== ''): ?>

        <div class="message">

            <?php
            echo htmlspecialchars(
                $message,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>

        </div>

        <div class="success-actions">

            <a
                class="success-btn"
                href="login.php"
            >
                Go to Login
            </a>

        </div>

    <?php else: ?>

        <form
            method="POST"
            action="forgot_password.php"
        >

            <label for="email">
                Registered Email
            </label>

            <input
                class="input"
                type="email"
                id="email"
                name="email"
                placeholder="Enter your SkillMate email"
                required
            >


            <label for="new_password">
                New Password
            </label>

            <input
                class="input"
                type="password"
                id="new_password"
                name="new_password"
                placeholder="Create new password"
                minlength="6"
                required
            >


            <label for="confirm_password">
                Confirm New Password
            </label>

            <input
                class="input"
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Confirm new password"
                minlength="6"
                required
            >


            <button
                class="btn"
                type="submit"
            >
                Reset Password
            </button>

        </form>

    <?php endif; ?>


    <div class="links">

        <a href="login.php">
            ← Back to Login
        </a>

    </div>

</div>

</body>

</html>