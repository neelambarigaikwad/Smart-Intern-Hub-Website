
<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
session_start();
require_once __DIR__ . "/config/db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "student";

    if ($name === "" || $email === "" || $password === "") {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    } elseif (!in_array($role, ["student", "company"], true)) {
        $error = "Please select a valid account type.";
    } else {
        try {
            $check = $pdo->prepare(
                "SELECT id FROM users WHERE email = ?"
            );
            $check->execute([$email]);

            if ($check->fetch()) {
                $error = "This email is already registered.";
            } else {
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, email, password, role)
                     VALUES (?, ?, ?, ?)"
                );

                $stmt->execute([
                    $name,
                    $email,
                    $hashedPassword,
                    $role
                ]);

                $userId = (int) $pdo->lastInsertId();

                if ($role === "student") {
                    $profile = $pdo->prepare(
                        "INSERT INTO student_profiles (user_id)
                         VALUES (?)"
                    );
                    $profile->execute([$userId]);
                }

                if ($role === "company") {
                    $profile = $pdo->prepare(
                        "INSERT INTO company_profiles
                         (user_id, company_name)
                         VALUES (?, ?)"
                    );
                    $profile->execute([$userId, $name]);
                }

                $success = "Registration successful! You can now log in.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | SmartInternHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="auth-page">

<header class="navbar">
    <a class="logo" href="index.php">SmartIntern<span>Hub</span></a>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
    </nav>
</header>

<main class="auth-container">
    <section class="auth-card">
        <div class="section-label">JOIN SMARTINTERNHUB</div>
        <h1>Create your account</h1>
        <p class="auth-subtitle">
            Start discovering opportunities and building your future.
        </p>

        <?php if ($error !== ""): ?>
            <div class="message error" role="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ""): ?>
            <div class="message success" role="status">
                <?= htmlspecialchars($success, ENT_QUOTES, "UTF-8") ?>
                <a href="login.php">Go to Login</a>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="register.php">
            <label for="full_name">Full Name / Company Name</label>
            <input type="text" id="full_name" name="full_name"
                   placeholder="Enter your name" maxlength="100"
                   autocomplete="name" required>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                   placeholder="you@example.com" maxlength="150"
                   autocomplete="email" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="At least 8 characters" minlength="8"
                   autocomplete="new-password" required>

            <label for="role">Register As</label>
            <select id="role" name="role" required>
                <option value="student">Student</option>
                <option value="company">Company</option>
            </select>

            <button class="btn btn-primary auth-submit" type="submit">
                Create Account
            </button>
        </form>

        <p class="auth-footer">
            Already have an account?
            <a href="login.php">Log in</a>
        </p>
    </section>
</main>

</body>
</html>
