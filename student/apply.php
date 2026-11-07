
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

$userId = (int) $_SESSION["user_id"];
$internshipId = filter_input(INPUT_GET, "internship_id", FILTER_VALIDATE_INT);

if (!$internshipId) {
    exit("Invalid internship. Please return to Find Internships.");
}

$stmt = $pdo->prepare(
    "SELECT id FROM student_profiles WHERE user_id = ?"
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    exit("Your student profile was not found. Please complete your profile first.");
}

$studentId = (int) $profile["id"];

$stmt = $pdo->prepare(
    "SELECT i.*, c.company_name
     FROM internships i
     INNER JOIN company_profiles c ON c.id = i.company_id
     WHERE i.id = ? AND i.status = 'approved'
       AND (i.deadline IS NULL OR i.deadline >= CURDATE())"
);
$stmt->execute([$internshipId]);
$internship = $stmt->fetch();

if (!$internship) {
    exit("This internship is not available for applications.");
}

$stmt = $pdo->prepare(
    "SELECT id FROM applications WHERE student_id = ? AND internship_id = ?"
);
$stmt->execute([$studentId, $internshipId]);
$alreadyApplied = (bool) $stmt->fetch();

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !$alreadyApplied) {
    $coverLetter = trim($_POST["cover_letter"] ?? "");

    if (mb_strlen($coverLetter) < 30) {
        $error = "Please write at least 30 characters explaining why you are suitable.";
    } elseif (mb_strlen($coverLetter) > 5000) {
        $error = "Your cover letter must be no longer than 5000 characters.";
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO applications
                 (student_id, internship_id, match_percentage, status,
                  applied_at, cover_letter)
                 VALUES (?, ?, 0, 'Pending', NOW(), ?)"
            );
            $stmt->execute([$studentId, $internshipId, $coverLetter]);

            header("Location: applications.php?applied=1");
            exit;
        } catch (PDOException $e) {
            // The unique constraint, if present, may also prevent duplicates.
            error_log($e->getMessage());
            $error = "Application could not be submitted. Please check whether you already applied.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apply for Internship | SmartInternHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .apply-wrap{max-width:760px;margin:35px auto;padding:20px}
        .apply-card{background:white;border:1px solid #e5e2ee;border-radius:16px;padding:24px;margin:18px 0}
        .apply-card textarea{width:100%;box-sizing:border-box;padding:12px;border:1px solid #d6d2e3;border-radius:10px}
        .apply-btn{background:#6d4aff;color:white;border:0;padding:12px 18px;border-radius:9px;cursor:pointer}
    </style>
</head>
<body>
<main class="apply-wrap">
    <p><a href="find-internships.php">← Back to internships</a></p>
    <h1>Internship Application</h1>

    <section class="apply-card">
        <h2><?= htmlspecialchars($internship["title"]) ?></h2>
        <p><strong>Company:</strong> <?= htmlspecialchars($internship["company_name"]) ?></p>
        <p><strong>Location:</strong> <?= htmlspecialchars($internship["location"]) ?></p>
        <p><strong>Stipend:</strong> <?= htmlspecialchars($internship["stipend"] ?: "Not specified") ?></p>
        <p><strong>Duration:</strong> <?= htmlspecialchars($internship["duration"] ?? "Not specified") ?></p>
        <p><?= nl2br(htmlspecialchars($internship["description"])) ?></p>
    </section>

    <section class="apply-card">
        <?php if ($alreadyApplied): ?>
            <p>You have already applied for this internship.</p>
            <a href="applications.php">View My Applications</a>
        <?php else: ?>
            <h2>Why should the company select you?</h2>
            <p>Write a short cover letter about your skills, interests and suitability.</p>

            <?php if ($error): ?>
                <p style="color:#b42318"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <label for="cover_letter">Cover Letter *</label>
                <textarea id="cover_letter" name="cover_letter"
                          rows="7" minlength="30" maxlength="5000" required
                          placeholder="Introduce yourself and explain why you are suitable..."></textarea>
                <p><button class="apply-btn" type="submit">Submit Application</button></p>
            </form>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
