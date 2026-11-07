
<?php
session_start();
require_once __DIR__ . "/config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $error = "Please enter your email and password.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, full_name, email, password, role
                 FROM users
                 WHERE email = :email
                 LIMIT 1"
            );

            $stmt->execute(["email" => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user["password"])) {
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["full_name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                switch ($user["role"]) {
                    case "student":
                        header("Location: student/dashboard.php");
                        exit;

                    case "company":
                        header("Location: company/dashboard.php");
                        exit;

                    case "supervisor":
                        header("Location: supervisor/dashboard.php");
                        exit;

                    case "admin":
                        header("Location: admin/dashboard.php");
                        exit;

                    default:
                        session_unset();
                        session_destroy();
                        $error = "Your account role is not configured correctly.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Unable to log in right now. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SmartInternHub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" defer></script>
</head>
<body class="auth-page">

<header class="navbar">
    <div class="container">
        <a class="logo" href="index.php">SmartInternHub</a>
        <nav>
            <a href="index.php">Home</a>
            <a href="register.php">Register</a>
        </nav>
    </div>
</header>

<main class="auth-container">
    <section class="auth-card">
        <h1>Welcome Back!</h1>
        <p>Log in to your SmartInternHub account.</p>

        <?php if ($error !== ""): ?>
            <p class="message error">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="Enter your email"
                autocomplete="email"
                required
            >

            <label for="password">Password</label>
            <div class="password-field">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >
                <button type="button" id="togglePassword">Show</button>
            </div>

            <button type="submit" class="btn">Login</button>
        </form>

        <p>Don't have an account?
            <a href="register.php">Register here</a>
        </p>
    </section>
</main>

<footer>
    <p>&copy; <span id="currentYear"></span> SmartInternHub.</p>
</footer>

</body>
</html>
