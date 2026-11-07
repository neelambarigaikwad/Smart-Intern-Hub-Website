
<?php
session_start();
require_once __DIR__ . "/../config/db.php";

// Only logged-in students can view their recommendations.
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["user_role"] ?? "") !== "student"
) {
    header("Location: ../login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$studentName = $_SESSION["user_name"] ?? "Student";
$studentSkills = [];
$internships = [];
$error = "";

try {
    // Find this student's profile.
    $profileStmt = $pdo->prepare(
        "SELECT id FROM student_profiles WHERE user_id = ? LIMIT 1"
    );
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch();

    if ($profile) {
        $profileId = (int) $profile["id"];

        // Get skills linked to the student's profile.
        $skillsStmt = $pdo->prepare(
            "SELECT s.skill_name
             FROM student_skills ss
             JOIN skills s ON s.id = ss.skill_id
             WHERE ss.student_id = ?
             ORDER BY s.skill_name"
        );
        $skillsStmt->execute([$profileId]);
        $studentSkills = $skillsStmt->fetchAll(
            PDO::FETCH_COLUMN
        );
    }

    // Only recommend approved internships.
    $internshipStmt = $pdo->query(
        "SELECT id, title, description, location,
                required_skills, stipend, deadline
         FROM internships
         WHERE status = 'approved'
         AND (deadline IS NULL OR deadline >= CURDATE())
         ORDER BY created_at DESC"
    );

    $internships = $internshipStmt->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = "Unable to load internship recommendations. Please try again.";
}

// Normalize skill names to compare case-insensitively.
function normalizeSkill($skill)
{
    $skill = strtolower(trim($skill));
    $skill = preg_replace('/\s+/', ' ', $skill);
    return $skill;
}

// Prepare the student's skill lookup.
$studentSkillLookup = [];

foreach ($studentSkills as $skill) {
    $studentSkillLookup[normalizeSkill($skill)] = $skill;
}

// Calculate match percentage for every internship.
foreach ($internships as &$internship) {
    // Requirements should be separated by commas,
    // semicolons, or new lines.
    $requiredSkills = preg_split(
        '/[,;\r\n]+/',
        $internship["required_skills"],
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    $requiredSkills = array_values(array_unique(array_filter(
        array_map("trim", $requiredSkills),
        function ($skill) {
            return $skill !== "";
        }
    )));

    $matchedSkills = [];
    $missingSkills = [];

    foreach ($requiredSkills as $requiredSkill) {
        $key = normalizeSkill($requiredSkill);

        if ($key !== "" && isset($studentSkillLookup[$key])) {
            $matchedSkills[] = $requiredSkill;
        } elseif ($key !== "") {
            $missingSkills[] = $requiredSkill;
        }
    }

    $totalRequired = count($requiredSkills);

    // Avoid incorrectly giving 100% when no skills are specified.
    $internship["match_percentage"] = $totalRequired > 0
        ? (int) round(
            count($matchedSkills) / $totalRequired * 100
        )
        : 0;

    $internship["matched_skills"] = $matchedSkills;
    $internship["missing_skills"] = $missingSkills;
}
unset($internship);

// Highest matching internships first.
usort($internships, function ($a, $b) {
    return $b["match_percentage"] <=> $a["match_percentage"];
});

function escapeHtml($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skill Matches | SmartInternHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="match-page">

<header class="match-navbar">
    <a class="match-logo" href="../index.php">SmartInternHub</a>

    <nav>
        <a href="../index.php">Home</a>
        <a href="matches.php" class="active">Find Internships</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main class="match-container">

    <section class="match-hero">
        <div>
            <p class="match-eyebrow">YOUR CAREER STARTS HERE</p>
            <h1>Hello, <?= escapeHtml($studentName) ?>!</h1>
            <p>Discover internships ranked by how well your skills match.</p>
        </div>

        <div class="match-hero-icon">✦</div>
    </section>

    <?php if ($error !== ""): ?>
        <div class="match-alert">
            <?= escapeHtml($error) ?>
        </div>
    <?php else: ?>

        <section class="my-skills">
            <div class="section-heading">
                <div>
                    <h2>Your Skills</h2>
                    <p>These skills are used to calculate your matches.</p>
                </div>
                <span class="skill-count">
                    <?= count($studentSkills) ?> skills
                </span>
            </div>

            <?php if (count($studentSkills) > 0): ?>
                <div class="match-tags">
                    <?php foreach ($studentSkills as $skill): ?>
                        <span class="student-skill-tag">
                            ✓ <?= escapeHtml($skill) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="match-muted">
                    You haven't added any skills yet. Add skills to your
                    student profile to get meaningful recommendations.
                </p>
            <?php endif; ?>
        </section>

        <section class="recommended-section">
            <div class="section-heading">
                <div>
                    <h2>Recommended Internships</h2>
                    <p>Sorted from highest to lowest skill match.</p>
                </div>
                <span class="skill-count">
                    <?= count($internships) ?> opportunities
                </span>
            </div>

            <?php if (count($internships) === 0): ?>
                <div class="match-empty">
                    <div class="empty-symbol">⌕</div>
                    <h3>No approved internships available yet</h3>
                    <p>Check again after a company posts an internship
                       and an administrator approves it.</p>
                </div>
            <?php else: ?>

                <div class="match-grid">
                    <?php foreach ($internships as $internship): ?>
                        <article class="match-card">

                            <div class="match-card-top">
                                <div class="match-company-icon">✧</div>

                                <div class="match-score">
                                    <?= $internship["match_percentage"] ?>%
                                    <small>match</small>
                                </div>
                            </div>

                            <h3><?= escapeHtml($internship["title"]) ?></h3>

                            <p class="match-location">
                                <?= escapeHtml(
                                    $internship["location"] ?: "Location not specified"
                                ) ?>
                            </p>

                            <div class="match-progress">
                                <div
                                    class="match-progress-fill"
                                    style="width: <?= $internship["match_percentage"] ?>%;"
                                ></div>
                            </div>

                            <p class="match-description">
                                <?= escapeHtml(
                                    mb_strimwidth(
                                        $internship["description"],
                                        0,
                                        140,
                                        "..."
                                    )
                                ) ?>
                            </p>

                            <h4>Skills that match</h4>

                            <div class="match-tags">
                                <?php if (count($internship["matched_skills"]) > 0): ?>
                                    <?php foreach ($internship["matched_skills"] as $skill): ?>
                                        <span class="student-skill-tag">
                                            ✓ <?= escapeHtml($skill) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="match-muted">
                                        No matching skills yet
                                    </span>
                                <?php endif; ?>
                            </div>

                            <?php if (count($internship["missing_skills"]) > 0): ?>
                                <h4>Skills to learn</h4>

                                <div class="match-tags">
                                    <?php foreach ($internship["missing_skills"] as $skill): ?>
                                        <span class="missing-skill-tag">
                                            + <?= escapeHtml($skill) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="match-card-footer">
                                <span class="match-stipend">
                                    ₹<?= number_format(
                                        (float) $internship["stipend"],
                                        0
                                    ) ?>/month
                                </span>

                                <span class="match-deadline">
                                    <?php if ($internship["deadline"]): ?>
                                        Deadline:
                                        <?= escapeHtml($internship["deadline"]) ?>
                                    <?php else: ?>
                                        No deadline specified
                                    <?php endif; ?>
                                </span>
                            </div>

                            <a
                                class="match-apply-button"
                                href="apply.php?internship_id=<?= (int) $internship["id"] ?>"
                            >
                                View / Apply <span>→</span>
                            </a>

                        </article>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </section>

    <?php endif; ?>

</main>

<footer class="match-footer">
    SmartInternHub &copy; <?= date("Y") ?> · Find opportunities that fit your skills.
</footer>

</body>
</html>
