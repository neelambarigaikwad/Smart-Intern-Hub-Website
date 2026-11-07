
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
$stmt = $pdo->prepare("SELECT id FROM company_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$company = $stmt->fetch();

if (!$company) {
    exit("Please save your company profile on the company dashboard first.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $stipend = trim($_POST["stipend"] ?? "");
    $skills = trim($_POST["required_skills"] ?? "");
    $deadline = $_POST["deadline"] ?? "";

    if (!$title || !$description || !$location || !$skills || !$deadline) {
        $error = "Please fill in all required fields.";
    } elseif (!strtotime($deadline) || $deadline < date("Y-m-d")) {
        $error = "Please choose today or a future application deadline.";
    } elseif (strlen($stipend) > 100) {
        $error = "Please enter a shorter stipend description.";
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO internships
             (company_id, title, description, location, required_skills,
              stipend, deadline, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')"
        );
        $stmt->execute([
            $company["id"], $title, $description, $location,
            $skills, $stipend ?: "Unpaid", $deadline
        ]);

        header("Location: dashboard.php?posted=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Post Internship | SmartInternHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .post-wrap{max-width:720px;margin:35px auto;padding:20px}
        .post-card{background:#fff;border:1px solid #e5e2ee;border-radius:16px;padding:25px}
        .post-card input,.post-card textarea{width:100%;box-sizing:border-box;padding:12px;margin:8px 0 16px;border:1px solid #d6d2e3;border-radius:9px}
        .post-btn{background:#6d4aff;color:white;border:0;padding:12px 18px;border-radius:9px;cursor:pointer}
    </style>
</head>
<body>
<main class="post-wrap">
    <p><a href="dashboard.php">← Company Dashboard</a></p>
    <section class="post-card">
        <h1>Post an Internship</h1>
        <p>Students will be able to find and apply for this opportunity.</p>

        <?php if ($error): ?>
            <p style="color:#b42318"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Internship Title *</label>
            <input name="title" maxlength="150" required
                   placeholder="e.g. Web Development Intern">

            <label>Job Description *</label>
            <textarea name="description" rows="5" required
                      placeholder="Explain the work, responsibilities and eligibility"></textarea>

            <label>Location *</label>
            <input name="location" required
                   placeholder="e.g. Pune / Remote / Hybrid">
            
            
            <label>Internship Duration *</label>
            <input name="duration" required maxlength="100"
                   placeholder="e.g. 3 months, 6 months">


            <label>Stipend</label>
            <input name="stipend" maxlength="100"
                   placeholder="e.g. ₹8,000 per month or Unpaid">

            <label>Required Skills *</label>
            <input name="required_skills" required
                   placeholder="HTML, CSS, JavaScript, PHP">

            <label>Application Deadline *</label>
            <input type="date" name="deadline" min="<?= date("Y-m-d") ?>" required>

            <button class="post-btn" type="submit">Publish Internship</button>
        </form>
    </section>
</main>
</body>
</html>
