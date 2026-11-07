
<?php
session_start();
require_once __DIR__ . "/../config/db.php";

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["user_role"] ?? "") !== "student"
) {
    header("Location: ../login.php");
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id FROM student_profiles WHERE user_id = ? LIMIT 1"
);
$stmt->execute([(int) $_SESSION["user_id"]]);
$profile = $stmt->fetch();

$applications = [];

if ($profile) {
    $stmt = $pdo->prepare(
        "SELECT a.*, i.title, i.location, i.stipend
         FROM applications a
         JOIN internships i ON i.id = a.internship_id
         WHERE a.student_id = ?
         ORDER BY a.id DESC"
    );
    $stmt->execute([(int) $profile["id"]]);
    $applications = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications | SmartInternHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="match-page">
<header class="match-navbar">
    <a class="match-logo" href="dashboard.php">SmartInternHub</a>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="matches.php">Find Internships</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main class="match-container">
    <section class="my-skills">
        <h1>My Applications</h1>

        <?php if (!$applications): ?>
            <p>You haven't applied for any internships yet.</p>
            <a class="btn-primary" href="matches.php">Find Internships</a>
        <?php else: ?>
            <div class="match-grid">
                <?php foreach ($applications as $application): ?>
                    <article class="match-card">
                        <h3><?= htmlspecialchars($application["title"], ENT_QUOTES, "UTF-8") ?></h3>
                        <p class="match-location">
                            <?= htmlspecialchars($application["location"] ?: "Location not specified", ENT_QUOTES, "UTF-8") ?>
                        </p>
                        <p>
                            <strong>Application status:</strong>
                            <?= htmlspecialchars($application["status"] ?? "Submitted", ENT_QUOTES, "UTF-8") ?>
                        </p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
