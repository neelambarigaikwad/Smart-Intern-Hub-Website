
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
$message = "";
$error = "";

// Find the student's profile
$stmt = $pdo->prepare(
    "SELECT id FROM student_profiles WHERE user_id = ?"
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    exit("Student profile not found. Please create the student profile first.");
}

$studentId = (int) $profile["id"];

// Add commonly used skills if they don't exist
$starterSkills = [
    "HTML", "CSS", "JavaScript", "PHP", "MySQL",
    "Python", "Java", "C", "C++", "Data Structures",
    "Problem Solving", "Communication", "Teamwork",
    "React", "Node.js", "Git", "UI/UX Design",
    "Excel", "SQL", "Software Testing", "Leadership",
    "Time Management", "Public Speaking", "Machine Learning",
    "Data Analysis", "Cybersecurity", "Cloud Computing"
];

foreach ($starterSkills as $skillName) {
    $stmt = $pdo->prepare(
        "INSERT IGNORE INTO skills (skill_name) VALUES (?)"
    );
    $stmt->execute([$skillName]);
}

// Save selected skills
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $selectedIds = $_POST["skills"] ?? [];
    $customSkill = trim($_POST["custom_skill"] ?? "");

    if (!is_array($selectedIds)) {
        $selectedIds = [];
    }

    $selectedIds = array_values(array_unique(array_filter(
        array_map("intval", $selectedIds),
        fn($id) => $id > 0
    )));

    if ($customSkill !== "") {
        if (mb_strlen($customSkill) > 80) {
            $error = "Skill name must be 80 characters or fewer.";
        } else {
            $stmt = $pdo->prepare(
                "SELECT id FROM skills WHERE LOWER(skill_name) = LOWER(?)"
            );
            $stmt->execute([$customSkill]);
            $existing = $stmt->fetch();

            if ($existing) {
                $selectedIds[] = (int) $existing["id"];
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO skills (skill_name) VALUES (?)"
                );
                $stmt->execute([$customSkill]);
                $selectedIds[] = (int) $pdo->lastInsertId();
            }
        }
    }

    $selectedIds = array_values(array_unique($selectedIds));

    if (!$error && count($selectedIds) > 30) {
        $error = "Please select no more than 30 skills.";
    }

    if (!$error) {
        $pdo->beginTransaction();

        try {
            // Only accept IDs that exist in the skills table
            $validIds = [];

            if ($selectedIds) {
                $placeholders = implode(
                    ",",
                    array_fill(0, count($selectedIds), "?")
                );

                $stmt = $pdo->prepare(
                    "SELECT id FROM skills WHERE id IN ($placeholders)"
                );
                $stmt->execute($selectedIds);
                $validIds = array_map(
                    "intval",
                    $stmt->fetchAll(PDO::FETCH_COLUMN)
                );
            }

            $stmt = $pdo->prepare(
                "DELETE FROM student_skills WHERE student_id = ?"
            );
            $stmt->execute([$studentId]);

            $stmt = $pdo->prepare(
                "INSERT INTO student_skills (student_id, skill_id)
                 VALUES (?, ?)"
            );

            foreach ($validIds as $skillId) {
                $stmt->execute([$studentId, $skillId]);
            }

            $pdo->commit();
            $message = "Your skills have been saved successfully!";
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $error = "Could not save skills. Please check your database tables.";
        }
    }
}

// Get all skills
$stmt = $pdo->query(
    "SELECT id, skill_name FROM skills ORDER BY skill_name ASC"
);
$allSkills = $stmt->fetchAll();

// Get currently selected skills
$stmt = $pdo->prepare(
    "SELECT s.id, s.skill_name
     FROM skills s
     INNER JOIN student_skills ss ON ss.skill_id = s.id
     WHERE ss.student_id = ?
     ORDER BY s.skill_name"
);
$stmt->execute([$studentId]);
$savedSkills = $stmt->fetchAll();

$savedIds = array_map(
    "intval",
    array_column($savedSkills, "id")
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Skills | SmartInternHub</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .skills-page {
            max-width: 850px;
            margin: 35px auto;
            padding: 25px;
        }

        .skills-panel {
            background: white;
            border: 1px solid #e7e5ef;
            border-radius: 18px;
            padding: 25px;
            margin-top: 20px;
        }

        .skill-search, .custom-input {
            width: 100%;
            padding: 13px 15px;
            border: 1px solid #d8d5e5;
            border-radius: 10px;
            font-size: 15px;
            box-sizing: border-box;
            margin: 10px 0;
        }

        .skill-results {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin: 15px 0;
        }

        .skill-option {
            border: 1px solid #dcd6f8;
            background: #f7f5ff;
            color: #4e35a5;
            padding: 9px 13px;
            border-radius: 20px;
            cursor: pointer;
        }

        .skill-option:hover {
            background: #e9e3ff;
        }

        .skill-option:disabled {
            opacity: .55;
            cursor: default;
        }

        .selected-skills {
            display: flex;
            flex-wrap: wrap;
            gap: 9px;
            margin: 15px 0;
        }

        .skill-tag {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            background: #ede9fe;
            color: #4c1d95;
            padding: 9px 12px;
            border-radius: 20px;
        }

        .remove-skill {
            border: 0;
            background: transparent;
            cursor: pointer;
            color: #6d28d9;
            font-weight: bold;
            font-size: 17px;
        }

        .save-skills {
            border: 0;
            padding: 12px 22px;
            background: #6d4aff;
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
        }

        .notice {
            padding: 12px;
            border-radius: 9px;
            margin: 12px 0;
        }

        .success { background: #e7f8ed; color: #176534; }
        .failure { background: #fff0f0; color: #a11b1b; }
        .muted { color: #716f80; font-size: 14px; }
    </style>
</head>

<body>
<div class="skills-page">
    <a href="dashboard.php">← Back to Dashboard</a>

    <h1>My Skills</h1>
    <p class="muted">
        Build your profile by selecting skills you know.
        Recruiters can use these skills to find suitable candidates.
    </p>

    <?php if ($message): ?>
        <div class="notice success">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice failure">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="skillsForm">
        <div class="skills-panel">
            <h2>Search and add skills</h2>
            <label for="skillSearch">Find a skill</label>

            <input
                type="search"
                id="skillSearch"
                class="skill-search"
                placeholder="Search HTML, PHP, teamwork..."
                autocomplete="off"
            >

            <div id="skillResults" class="skill-results">
                <?php foreach ($allSkills as $skill): ?>
                    <button
                        type="button"
                        class="skill-option"
                        data-id="<?= (int)$skill["id"] ?>"
                        data-name="<?= htmlspecialchars($skill["skill_name"], ENT_QUOTES, "UTF-8") ?>"
                    >+ <?= htmlspecialchars($skill["skill_name"]) ?></button>
                <?php endforeach; ?>
            </div>

            <p class="muted">
                Can't find your skill? Add your own below.
            </p>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <input
                    type="text"
                    id="customSkill"
                    class="custom-input"
                    maxlength="80"
                    placeholder="Enter a new skill"
                    style="flex:1; min-width:180px;"
                >
                <button type="button" id="addCustomSkill" class="save-skills">
                    + Add Skill
                </button>
            </div>
        </div>

        <div class="skills-panel">
            <h2>My selected skills</h2>
            <p class="muted">
                <span id="skillCount">0</span> of 30 skills selected.
                Click × to remove a skill.
            </p>

            <div id="selectedSkills" class="selected-skills"></div>
            <p id="emptyMessage" class="muted">
                No skills selected yet. Search above to add some.
            </p>

            <div id="hiddenInputs">
                <?php foreach ($savedIds as $id): ?>
                    <input type="hidden" name="skills[]" value="<?= $id ?>">
                <?php endforeach; ?>
            </div>

            <button type="submit" class="save-skills">Save My Skills</button>
        </div>
    </form>
</div>

<script>
const initialSkills = <?= json_encode(
    $savedSkills,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

const allSkills = <?= json_encode(
    $allSkills,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
) ?>;

let selected = new Map(
    initialSkills.map(skill => [
        Number(skill.id),
        { id: Number(skill.id), name: skill.skill_name }
    ])
);

let nextTemporaryId = -1;

const searchInput = document.getElementById("skillSearch");
const results = document.getElementById("skillResults");
const selectedContainer = document.getElementById("selectedSkills");
const hiddenInputs = document.getElementById("hiddenInputs");
const count = document.getElementById("skillCount");
const emptyMessage = document.getElementById("emptyMessage");

function renderSelected() {
    selectedContainer.replaceChildren();
    hiddenInputs.replaceChildren();
    count.textContent = selected.size;
    emptyMessage.style.display = selected.size ? "none" : "block";

    selected.forEach(skill => {
        const tag = document.createElement("span");
        tag.className = "skill-tag";

        const name = document.createElement("span");
        name.textContent = skill.name;
        tag.appendChild(name);

        const remove = document.createElement("button");
        remove.type = "button";
        remove.className = "remove-skill";
        remove.textContent = "×";
        remove.setAttribute("aria-label", "Remove " + skill.name);

        remove.addEventListener("click", () => {
            selected.delete(skill.id);
            renderSelected();
            renderResults();
        });

        tag.appendChild(remove);
        selectedContainer.appendChild(tag);

        // Temporary IDs are not submitted: the server creates custom skills
        // using the custom_skill field instead.
        if (skill.id > 0) {
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "skills[]";
            hidden.value = skill.id;
            hiddenInputs.appendChild(hidden);
        }
    });
}

function addSkill(id, name) {
    if (selected.has(Number(id))) return;

    if (selected.size >= 30) {
        alert("You can select up to 30 skills.");
        return;
    }

    selected.set(Number(id), { id: Number(id), name: name });
    renderSelected();
    renderResults();
}

function renderResults() {
    const query = searchInput.value.trim().toLowerCase();
    results.replaceChildren();

    const matches = allSkills.filter(skill =>
        skill.skill_name.toLowerCase().includes(query) &&
        !selected.has(Number(skill.id))
    );

    matches.slice(0, 40).forEach(skill => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "skill-option";
        button.textContent = "+ " + skill.skill_name;

        button.addEventListener("click", () => {
            addSkill(skill.id, skill.skill_name);
        });

        results.appendChild(button);
    });

    if (matches.length === 0) {
        const note = document.createElement("p");
        note.className = "muted";
        note.textContent = "No matching skills. You can add a new skill below.";
        results.appendChild(note);
    }
}

searchInput.addEventListener("input", renderResults);

document.getElementById("addCustomSkill").addEventListener("click", () => {
    const input = document.getElementById("customSkill");
    const name = input.value.trim();

    if (!name) {
        alert("Please enter a skill name.");
        return;
    }

    const existing = allSkills.find(skill =>
        skill.skill_name.toLowerCase() === name.toLowerCase()
    );

    if (existing) {
        addSkill(existing.id, existing.skill_name);
    } else {
        const alreadyAdded = [...selected.values()].some(skill =>
            skill.name.toLowerCase() === name.toLowerCase()
        );

        if (alreadyAdded) {
            alert("This skill is already selected.");
            return;
        }

        addSkill(nextTemporaryId--, name);
    }

    input.value = "";
});

document.getElementById("customSkill").addEventListener("keydown", event => {
    if (event.key === "Enter") {
        event.preventDefault();
        document.getElementById("addCustomSkill").click();
    }
});

document.getElementById("skillsForm").addEventListener("submit", event => {
    const customSkills = [...selected.values()]
        .filter(skill => skill.id < 0);

    // This version supports adding one new custom skill per save.
    if (customSkills.length > 1) {
        event.preventDefault();
        alert("Please save one new custom skill at a time. Remove extra new skills and save again.");
        return;
    }

    let customInput = document.getElementById("customSkill");

    if (customSkills.length === 1) {
        customInput.value = customSkills[0].name;
    } else {
        customInput.value = "";
    }
});

renderSelected();
renderResults();
</script>
</body>
</html>
