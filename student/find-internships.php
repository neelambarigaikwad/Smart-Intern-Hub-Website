
<?php
session_start();
require_once "../config/db.php";

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["user_role"] ?? "") !== "student"
) {
    header("Location: ../login.php");
    exit;
}

$search = trim($_GET["q"] ?? "");

$sql = "SELECT i.*, c.company_name
        FROM internships i
        INNER JOIN company_profiles c ON c.id = i.company_id
        WHERE i.status = 'approved'
          AND (i.deadline IS NULL OR i.deadline >= CURDATE())";

$params = [];

if ($search !== "") {
    $sql .= " AND (
        i.title LIKE ? OR i.location LIKE ?
        OR i.required_skills LIKE ? OR c.company_name LIKE ?
    )";
    $term = "%" . $search . "%";
    $params = [$term, $term, $term, $term];
}

$sql .= " ORDER BY i.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$internships = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Find Internships | SmartInternHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .find-wrap{max-width:1050px;margin:35px auto;padding:20px}
        .find-search{display:flex;gap:10px;flex-wrap:wrap;margin:20px 0}
        .find-search input{flex:1;min-width:200px;padding:13px;border:1px solid #d6d2e3;border-radius:10px}
        .find-btn{background:#6d4aff;color:#fff;border:0;border-radius:9px;padding:12px 18px;cursor:pointer;text-decoration:none;display:inline-block}
        .internship-card{background:white;border:1px solid #e5e2ee;border-radius:15px;padding:22px;margin:16px 0}
        .internship-meta{display:flex;flex-wrap:wrap;gap:12px;color:#615e70;font-size:14px}
        .skill-list{color:#5540a0}
    </style>
</head>
<body>
<main class="find-wrap">
    <p>
        <a href="dashboard.php">← Student Dashboard</a> |
        <a href="../logout.php" class="logout-link">Logout</a>
    </p>

    <h1>Find Internships</h1>
    <p>Discover opportunities that match your skills and career goals.</p>

    <form class="find-search" method="GET">
        <input name="q" value="<?= htmlspecialchars($search) ?>"
               placeholder="Search title, company, location or skills">
        <button class="find-btn" type="submit">Search</button>
    </form>

    <p><?= count($internships) ?> internship(s) found</p>

    <?php if (!$internships): ?>
        <div class="internship-card">
            No internships found. Try another search or check again later.
        </div>
    <?php endif; ?>

    <?php foreach ($internships as $item): ?>
        <article class="internship-card">
            <h2><?= htmlspecialchars($item["title"]) ?></h2>
            <h3><?= htmlspecialchars($item["company_name"]) ?></h3>

            <div class="internship-meta">
                <span>📍 <?= htmlspecialchars($item["location"]) ?></span>
                <span>💰 <?= htmlspecialchars($item["stipend"] ?: "Not specified") ?></span>
                <span>🗓️ <?= htmlspecialchars($item["duration"] ?? "Not specified") ?></span>
                <span>Deadline: <?= htmlspecialchars($item["deadline"] ?? "Not specified") ?></span>
            </div>

            <p><?= nl2br(htmlspecialchars($item["description"])) ?></p>

            <p class="skill-list">
                <strong>Required skills:</strong>
                <?= htmlspecialchars($item["required_skills"]) ?>
            </p>

            <a class="find-btn"
               href="apply.php?internship_id=<?= (int)$item["id"] ?>">
                Apply Now
            </a>
        </article>
    <?php endforeach; ?>
</main>
<script src="../assets/js/script.js"></script>
</body>
</html>
