
<?php
session_start();
require_once "../config/db.php";

if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["user_role"] ?? "") !== "company"
) {
    header("Location: ../login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$error = "";
$message = "";

$stmt = $pdo->prepare(
    "SELECT id, company_name, description, website
     FROM company_profiles WHERE user_id = ?"
);
$stmt->execute([$userId]);
$company = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["company_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $website = trim($_POST["website"] ?? "");

    if ($name === "") {
        $error = "Please enter your company name.";
    } elseif ($website !== "" && !filter_var($website, FILTER_VALIDATE_URL)) {
        $error = "Please enter a valid website URL, including https://.";
    } else {
        if ($company) {
            $stmt = $pdo->prepare(
                "UPDATE company_profiles
                 SET company_name = ?, description = ?, website = ?
                 WHERE id = ? AND user_id = ?"
            );
            $stmt->execute([
                $name, $description, $website ?: null,
                $company["id"], $userId
            ]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO company_profiles
                 (user_id, company_name, description, website)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $userId, $name, $description, $website ?: null
            ]);
        }

        header("Location: dashboard.php?saved=1");
        exit;
    }

    $company = [
        "company_name" => $name,
        "description" => $description,
        "website" => $website
    ];
}

if (isset($_GET["saved"])) {
    $message = "Company profile saved successfully.";
}

$internships = [];

if ($company && isset($company["id"])) {
    $stmt = $pdo->prepare(
        "SELECT id, title, location, stipend, deadline, status
         FROM internships
         WHERE company_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->execute([$company["id"]]);
    $internships = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Company Dashboard | SmartInternHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .company-wrap{max-width:1000px;margin:35px auto;padding:20px}
        .company-card{background:#fff;border:1px solid #e5e2ee;border-radius:16px;padding:22px;margin:20px 0}
        .company-card input,.company-card textarea{box-sizing:border-box;width:100%;padding:12px;margin:8px 0 16px;border:1px solid #d6d2e3;border-radius:9px}
        .primary-btn{display:inline-block;background:#6d4aff;color:#fff;padding:11px 17px;border:0;border-radius:9px;text-decoration:none;cursor:pointer}
        .company-table{width:100%;border-collapse:collapse}
        .company-table th,.company-table td{text-align:left;padding:12px;border-bottom:1px solid #eee}
        .notice{padding:12px;border-radius:8px;margin:12px 0}
    </style>
</head>
<body>
<main class="company-wrap">
    <header>
        <h1>Company Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION["user_name"] ?? "Company") ?>!</p>
        <a href="../index.php">Home</a> |
        <a href="../logout.php" class="logout-link">Logout</a>
    </header>

    <?php if ($message): ?>
        <p class="notice"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="notice"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <section class="company-card">
        <h2>Company Profile</h2>
        <form method="POST">
            <label for="company_name">Company Name *</label>
            <input id="company_name" name="company_name" required
                   value="<?= htmlspecialchars($company["company_name"] ?? "") ?>">

            <label for="description">About Company</label>
            <textarea id="description" name="description" rows="4"><?= htmlspecialchars($company["description"] ?? "") ?></textarea>

            <label for="website">Website (include https://)</label>
            <input id="website" type="url" name="website"
                   placeholder="https://example.com"
                   value="<?= htmlspecialchars($company["website"] ?? "") ?>">

            <button class="primary-btn" type="submit">Save Company Profile</button>
        </form>
    </section>

    <section class="company-card">
        <h2>Internships Posted</h2>
        <p><a class="primary-btn" href="post-internship.php">+ Post New Internship</a></p>
        <a class="primary-btn" href="applications.php"> View Student Applications</a>


        <?php if (!$internships): ?>
            <p>You have not posted any internships yet.</p>
        <?php else: ?>
            <div style="overflow-x:auto">
            <table class="company-table">
                <thead>
                    <tr><th>Title</th><th>Location</th><th>Stipend</th><th>Deadline</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($internships as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item["title"]) ?></td>
                        <td><?= htmlspecialchars($item["location"]) ?></td>
                        <td><?= htmlspecialchars($item["stipend"] ?? "Not specified") ?></td>
                        <td><?= htmlspecialchars($item["deadline"] ?? "Not specified") ?></td>
                        <td><?= htmlspecialchars($item["status"]) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </section>
</main>
<script src="../assets/js/script.js"></script>
</body>
</html>
