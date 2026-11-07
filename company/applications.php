```php
<?php
session_start();
require_once "../config/db.php";

// Allow only logged-in companies
if (
    !isset($_SESSION["user_id"]) ||
    ($_SESSION["user_role"] ?? "") !== "company"
) {
    header("Location: ../login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];
$error = "";
$success = "";

// Find the company's profile
$stmt = $pdo->prepare(
    "SELECT id, company_name
     FROM company_profiles
     WHERE user_id = ?"
);
$stmt->execute([$userId]);
$company = $stmt->fetch();

if (!$company) {
    exit("Please complete your company profile first.");
}

$companyId = (int) $company["id"];

// Update application status
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $applicationId = filter_input(
        INPUT_POST,
        "application_id",
        FILTER_VALIDATE_INT
    );

    $newStatus = $_POST["status"] ?? "";
    $allowedStatuses = ["Reviewed", "Shortlisted", "Rejected"];

    if (!$applicationId || !in_array($newStatus, $allowedStatuses, true)) {
        $error = "Invalid application or status.";
    } else {
        // Verify that this application belongs to this company
        $stmt = $pdo->prepare(
            "UPDATE applications a
             JOIN internships i ON a.internship_id = i.id
             SET a.status = ?
             WHERE a.id = ? AND i.company_id = ?"
        );
        $stmt->execute([$newStatus, $applicationId, $companyId]);

        if ($stmt->rowCount() > 0) {
            $success = "Application status updated successfully.";
        } else {
            $check = $pdo->prepare(
                "SELECT a.status
                 FROM applications a
                 JOIN internships i ON a.internship_id = i.id
                 WHERE a.id = ? AND i.company_id = ?"
            );
            $check->execute([$applicationId, $companyId]);
            $existing = $check->fetch();

            if ($existing && $existing["status"] === $newStatus) {
                $success = "Application status is already " . $newStatus . ".";
            } else {
                $error = "Application not found or could not be updated.";
            }
        }
    }
}

// Fetch applications for this company's internships
$stmt = $pdo->prepare(
    "SELECT
        a.id AS application_id,
        a.status AS application_status,
        a.applied_at,
        a.cover_letter,
        u.full_name,
        u.email,
        sp.college,
        sp.course,
        i.title AS internship_title
     FROM applications a
     JOIN internships i ON a.internship_id = i.id
     JOIN student_profiles sp ON a.student_id = sp.id
     JOIN users u ON sp.user_id = u.id
     WHERE i.company_id = ?
     ORDER BY a.applied_at DESC"
);
$stmt->execute([$companyId]);
$applications = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)($value ?? ""), ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Applications | SmartInternHub</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f6fc;
            color: #252544;
        }

        header {
            background: #5b45c6;
            color: white;
            padding: 20px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 16px;
        }

        main {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 18px;
        }

        .application {
            background: white;
            border-radius: 12px;
            padding: 22px;
            margin: 18px 0;
            box-shadow: 0 3px 14px #25254412;
        }

        .application h2 {
            margin-top: 0;
            color: #5b45c6;
        }

        .cover-letter {
            background: #f7f5ff;
            border-left: 4px solid #5b45c6;
            border-radius: 5px;
            padding: 15px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .status {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            background: #eeeaff;
            color: #4935ad;
            font-size: 13px;
        }

        select, button {
            padding: 10px 12px;
            border-radius: 6px;
            border: 1px solid #d5d2e8;
            font-size: 14px;
        }

        button {
            background: #5b45c6;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover { background: #4431a4; }

        .message {
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 15px;
            background: #e7f8eb;
            color: #17652b;
        }

        .error {
            background: #ffe9e9;
            color: #a32222;
        }

        .empty {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
        }

        @media (max-width: 600px) {
            header { padding: 18px; }
            header a { margin-left: 0; margin-right: 12px; }
            .application { padding: 16px; }
        }
    </style>
</head>
<body>

<header>
    <strong>SmartInternHub</strong>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="post-internship.php">Post Internship</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<main>
    <h1>Student Applications</h1>
    <p>
        Welcome, <?= e($company["company_name"]) ?>.
        Review applicants and their cover letters below.
    </p>

    <?php if ($success !== ""): ?>
        <div class="message"><?= e($success) ?></div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="message error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (empty($applications)): ?>
        <div class="empty">
            <h2>No applications yet</h2>
            <p>Applications from students will appear here after they apply.</p>
        </div>
    <?php else: ?>

        <?php foreach ($applications as $app): ?>
            <section class="application">
                <h2><?= e($app["internship_title"]) ?></h2>

                <p><strong>Student:</strong> <?= e($app["full_name"]) ?></p>
                <p><strong>Email:</strong> <?= e($app["email"]) ?></p>

                <p>
                    <strong>College:</strong>
                    <?= e($app["college"] ?: "Not provided") ?>
                </p>

                <p>
                    <strong>Course:</strong>
                    <?= e($app["course"] ?: "Not provided") ?>
                </p>

                <p>
                    <strong>Applied on:</strong>
                    <?= e($app["applied_at"] ?: "Not available") ?>
                </p>

                <p>
                    <strong>Current status:</strong>
                    <span class="status">
                        <?= e($app["application_status"] ?: "Pending") ?>
                    </span>
                </p>

                <h3>Cover Letter</h3>

                <div class="cover-letter"><?=
                    e(trim($app["cover_letter"] ?? "") !== ""
                        ? $app["cover_letter"]
                        : "The student did not submit a cover letter.")
                ?></div>

                <form method="POST" action="applications.php">
                    <input
                        type="hidden"
                        name="application_id"
                        value="<?= (int)$app["application_id"] ?>"
                    >

                    <p>
                        <label for="status-<?= (int)$app["application_id"] ?>">
                            <strong>Update status:</strong>
                        </label>
                    </p>

                    <select
                        id="status-<?= (int)$app["application_id"] ?>"
                        name="status"
                        required
                    >
                        <option value="Reviewed"
                            <?= $app["application_status"] === "Reviewed" ? "selected" : "" ?>>
                            Reviewed
                        </option>

                        <option value="Shortlisted"
                            <?= $app["application_status"] === "Shortlisted" ? "selected" : "" ?>>
                            Shortlisted
                        </option>

                        <option value="Rejected"
                            <?= $app["application_status"] === "Rejected" ? "selected" : "" ?>>
                            Rejected
                        </option>
                    </select>

                    <button type="submit">Update Status</button>
                </form>
            </section>
        <?php endforeach; ?>

    <?php endif; ?>
</main>

</body>
</html>
```