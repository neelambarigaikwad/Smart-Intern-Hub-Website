
<?php
session_start();

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["user_role"] ?? "") !== "student"
) {
    header("Location: ../login.php");
    exit;
}

$name = $_SESSION["user_name"] ?? "Student";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | SmartInternHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">

<header class="match-navbar">
    <a class="match-logo" href="../index.php">SmartInternHub</a>
    <nav>
        <a href="../index.php">Home</a>
        <a href="find-internships.php">Find Internships</a><a href="skills.php">My Skills</a>
        <a href="applications.php">My Applications</a>
        <a href="../logout.php" class="logout-link">Logout</a>

    </nav>
</header>

<main class="dashboard-main">
    <div class="dashboard-heading">
        <div>
            <h1>Welcome, <?= htmlspecialchars($name, ENT_QUOTES, "UTF-8") ?>!</h1>
            <p class="muted">Manage your skills and find your next internship.</p>
        </div>
    </div>

    <div class="internship-grid">
        <div class="internship-card">
            <h3>My Skills</h3>
            <p>Add the skills you know so we can recommend suitable internships.</p>
            <a class="btn-primary" href="skills.php">Add / Update Skills</a>
        </div>

        <div class="internship-card">
            <h3>Find Matching Internships</h3>
            <p>See internship recommendations based on your skills.</p>
            <a class="btn-primary" href="matches.php">Find Internships</a>
        </div>

        <div class="internship-card">
             <h3>My Applications</h3>
             <p>Check the status of your internship applications.</p>
            <a class="btn-primary" href="applications.php">View Applications</a>
        </div>
    </div>
</main>

</body>
</html>
