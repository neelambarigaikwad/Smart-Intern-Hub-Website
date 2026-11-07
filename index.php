
<?php
session_start();

$isLoggedIn = isset($_SESSION["user_id"]);
$role = $_SESSION["user_role"] ?? "";

$dashboardLinks = [
    "student" => "student/dashboard.php",
    "company" => "company/dashboard.php",
    "supervisor" => "supervisor/dashboard.php",
    "admin" => "admin/dashboard.php"
];

$dashboardUrl = $dashboardLinks[$role] ?? "login.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SmartInternHub helps students discover internships through skill-based matching, manage applications and track internship progress.">
    <title>SmartInternHub | Find Your Future</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="home-page">

<header class="home-navbar">
    <a class="home-logo" href="index.php">
        <span class="logo-symbol">S</span>
        SmartInternHub
    </a>

    <nav class="home-nav">
        <a href="#home">Home</a>
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>

        <?php if ($isLoggedIn): ?>
            <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, "UTF-8") ?>"
               class="home-login">Dashboard</a>
            <a href="logout.php" class="home-button">Logout</a>
        <?php else: ?>
            <a href="login.php" class="home-login">Login</a>
            <a href="register.php" class="home-button">Get Started <span>→</span></a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="home-hero" id="home">
        <div class="home-hero-content">
            <span class="hero-label">
                <span class="hero-dot"></span>
                YOUR CAREER JOURNEY STARTS HERE
            </span>

            <h1>
                Turn Your Skills Into
                <span>Real Opportunities.</span>
            </h1>

            <p>
                Discover internships that match your skills, connect with
                companies, manage your applications and take your first
                step toward a successful career.
            </p>

            <div class="hero-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, "UTF-8") ?>"
                       class="home-button hero-primary">
                        Open Dashboard <span>→</span>
                    </a>
                <?php else: ?>
                    <a href="register.php" class="home-button hero-primary">
                        Start Your Journey <span>→</span>
                    </a>
                <?php endif; ?>

                <a href="#how-it-works" class="hero-secondary">
                    Explore How It Works <span>↗</span>
                </a>
            </div>

            <div class="hero-trust">
                <span class="trust-icon">✓</span>
                Skill-based recommendations
                <span class="trust-divider">·</span>
                Easy application tracking
            </div>
        </div>

        <div class="hero-visual">
            <div class="visual-glow"></div>

            <div class="visual-card">
                <div class="visual-card-header">
                    <div>
                        <span class="visual-overline">YOUR CAREER SPACE</span>
                        <h3>Find your perfect fit</h3>
                    </div>
                    <span class="visual-sparkle">✦</span>
                </div>

                <div class="visual-profile">
                    <div class="profile-avatar">S</div>
                    <div>
                        <strong>Student Profile</strong>
                        <p>Build skills. Grow confidently.</p>
                    </div>
                    <span class="profile-check">✓</span>
                </div>

                <div class="visual-match">
                    <div class="visual-match-heading">
                        <span>Skill compatibility</span>
                        <strong>75%</strong>
                    </div>
                    <div class="visual-progress">
                        <span></span>
                    </div>
                    <p>Your skills can open new doors.</p>
                </div>

                <div class="visual-opportunity">
                    <div class="opportunity-icon">⌘</div>
                    <div>
                        <strong>Web Development Intern</strong>
                        <p>Example internship card</p>
                    </div>
                    <span class="opportunity-arrow">↗</span>
                </div>

                <div class="visual-decoration decoration-one">✳</div>
                <div class="visual-decoration decoration-two">✦</div>
            </div>

            <div class="floating-chip">
                <span class="floating-check">✓</span>
                Find opportunities that fit
            </div>
        </div>
    </section>

    <section class="home-benefits">
        <div>
            <span class="benefit-icon">⌘</span>
            <div>
                <strong>Skill-based matching</strong>
                <p>Find relevant opportunities</p>
            </div>
        </div>
        <div>
            <span class="benefit-icon">↗</span>
            <div>
                <strong>Application tracking</strong>
                <p>Stay updated at every step</p>
            </div>
        </div>
        <div>
            <span class="benefit-icon">◎</span>
            <div>
                <strong>Progress monitoring</strong>
                <p>Keep your goals on track</p>
            </div>
        </div>
    </section>

    <section class="home-features" id="features">
        <div class="section-intro">
            <span class="section-label">ONE PLATFORM. MORE POSSIBILITIES.</span>
            <h2>Everything you need to<br><span>move forward.</span></h2>
            <p>
                SmartInternHub brings students, companies and supervisors
                together in one easy-to-use internship platform.
            </p>
        </div>

        <div class="home-feature-grid">
            <article class="home-feature-card">
                <div class="feature-icon feature-purple">✳</div>
                <h3>Smart Skill Matching</h3>
                <p>
                    Compare your skills with internship requirements
                    and see your compatibility percentage.
                </p>
                <a href="register.php">Build your profile <span>→</span></a>
            </article>

            <article class="home-feature-card">
                <div class="feature-icon feature-blue">⌕</div>
                <h3>Discover Internships</h3>
                <p>
                    Explore approved internship opportunities and
                    find roles that suit your interests.
                </p>
                <a href="register.php">Explore opportunities <span>→</span></a>
            </article>

            <article class="home-feature-card">
                <div class="feature-icon feature-green">✓</div>
                <h3>Track Applications</h3>
                <p>
                    Keep your applications organized and check
                    their progress from your dashboard.
                </p>
                <a href="login.php">Track your progress <span>→</span></a>
            </article>

            <article class="home-feature-card">
                <div class="feature-icon feature-orange">◎</div>
                <h3>Internship Progress</h3>
                <p>
                    Submit progress reports and keep supervisors
                    informed about your work.
                </p>
                <a href="register.php">Get started <span>→</span></a>
            </article>
        </div>
    </section>

    <section class="home-how" id="how-it-works">
        <div class="section-intro">
            <span class="section-label">SIMPLE. CLEAR. EFFECTIVE.</span>
            <h2>Your internship journey<br><span>in three steps.</span></h2>
        </div>

        <div class="steps-grid">
            <article class="step-card">
                <span class="step-number">01</span>
                <h3>Create Your Profile</h3>
                <p>Register and add your education, skills and interests.</p>
            </article>

            <article class="step-card">
                <span class="step-number">02</span>
                <h3>Find Your Match</h3>
                <p>Explore internships and compare your skills with requirements.</p>
            </article>

            <article class="step-card">
                <span class="step-number">03</span>
                <h3>Apply and Grow</h3>
                <p>Apply for suitable roles and track your internship journey.</p>
            </article>
        </div>
    </section>

    <section class="home-cta">
        <div>
            <span class="section-label">YOUR NEXT CHAPTER BEGINS HERE</span>
            <h2>Ready to turn potential<br>into experience?</h2>
            <p>Create your profile and take the next step in your career journey.</p>
        </div>
        <a href="<?= $isLoggedIn
            ? htmlspecialchars($dashboardUrl, ENT_QUOTES, "UTF-8")
            : "register.php" ?>" class="home-button cta-button">
            <?= $isLoggedIn ? "Go to Dashboard" : "Create Your Account" ?>
            <span>→</span>
        </a>
    </section>
</main>

<footer class="home-footer">
    <a class="home-logo footer-logo" href="index.php">
        <span class="logo-symbol">S</span>
        SmartInternHub
    </a>
    <p>Connecting skills with opportunities.</p>
    <div>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </div>
    <small>&copy; <?= date("Y") ?> SmartInternHub. All rights reserved.</small>
</footer>

</body>
</html>
