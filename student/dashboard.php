<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];
$userId = (int)$user['id'];
$group = $user['research_group'] ?? null;
$isExperimental = $group === 'experimental';

$stmt = $pdo->prepare("
    SELECT assessment_type
    FROM research_attempts
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$researchDone = array_column($stmt->fetchAll(), 'assessment_type');

if ($isExperimental) {
    $totalLessons = (int)$pdo->query("SELECT COUNT(*) FROM lessons WHERE is_published = 1")->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE user_id = ?");
    $stmt->execute([$userId]);
    $completedLessons = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS attempts, COALESCE(SUM(is_correct),0) AS correct_answers
        FROM hardware_attempts
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $hardwareStats = $stmt->fetch();
    $hardwareAttempts = (int)($hardwareStats['attempts'] ?? 0);
    $hardwareCorrect = (int)($hardwareStats['correct_answers'] ?? 0);
    $hardwareAccuracy = $hardwareAttempts > 0 ? round(($hardwareCorrect / $hardwareAttempts) * 100) : 0;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS attempts,
               COALESCE(MAX(CASE WHEN total_items > 0 THEN (score / total_items) * 100 ELSE 0 END),0) AS best
        FROM quiz_attempts
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $quizStats = $stmt->fetch();
    $quizAttempts = (int)($quizStats['attempts'] ?? 0);
    $quizBest = (int)round((float)($quizStats['best'] ?? 0));

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ai_chat_logs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $aiChats = (int)$stmt->fetchColumn();

    $lessonProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

    $overall = round(
        ($lessonProgress * 0.40) +
        (($hardwareAttempts > 0 ? 100 : 0) * 0.20) +
        (($quizAttempts > 0 ? 100 : 0) * 0.20) +
        (($aiChats > 0 ? 100 : 0) * 0.20)
    );

    $nextLessonStmt = $pdo->prepare("
        SELECT l.id, l.title
        FROM lessons l
        LEFT JOIN lesson_progress lp
            ON lp.lesson_id = l.id AND lp.user_id = ?
        WHERE l.is_published = 1
          AND lp.id IS NULL
        ORDER BY l.sort_order ASC, l.id ASC
        LIMIT 1
    ");
    $nextLessonStmt->execute([$userId]);
    $nextLesson = $nextLessonStmt->fetch();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Dashboard | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart
        </a>
        <div class="d-flex align-items-center gap-3">
            <span class="d-none d-sm-inline text-secondary"><?= htmlspecialchars($user['full_name']) ?></span>
            <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container py-5">
    <section class="hero-card mb-4">
        <div>
            <span class="eyebrow">Student Dashboard</span>
            <h1 class="mt-2">Welcome, <?= htmlspecialchars($user['full_name']) ?>!</h1>
            <p class="mb-0">
                <?= $isExperimental
                    ? 'Continue learning Computer Hardware Fundamentals.'
                    : 'Use this account for the formal research assessments.' ?>
            </p>
        </div>

        <div class="progress-badge">
            <span>Group</span>
            <strong><?= htmlspecialchars($group ? ucfirst($group) : 'Unassigned') ?></strong>
        </div>
    </section>

    <?php if (!$group): ?>
        <div class="alert alert-warning">
            Your research group has not been assigned. Learning modules are unavailable until an administrator assigns your group.
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-md-6 <?= $isExperimental ? 'col-xl-3' : '' ?>">
            <a class="module-card research-module-card" href="research.php">
                <div class="module-icon">🧪</div>
                <h3>Research Assessment</h3>
                <p>Access the formal Computer Hardware Fundamentals pretest and posttest.</p>
                <span>
                    <?= count($researchDone) ?>/2 submitted →
                </span>
            </a>
        </div>

        <?php if ($isExperimental): ?>
            <div class="col-md-6 col-xl-3">
                <a class="module-card" href="lessons.php">
                    <div class="module-icon">📘</div>
                    <h3>Lessons</h3>
                    <p>Study visual lessons and mark each one complete.</p>
                    <span>Open lessons →</span>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a class="module-card" href="hardware.php">
                    <div class="module-icon">🧩</div>
                    <h3>Hardware ID</h3>
                    <p>Identify computer parts and get immediate explanations.</p>
                    <span>Start activity →</span>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a class="module-card" href="ai-tutor.php">
                    <div class="module-icon">🤖</div>
                    <h3>AI Tutor</h3>
                    <p>Ask focused questions about Computer Hardware Fundamentals.</p>
                    <span>Ask AI →</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($isExperimental): ?>
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <span>📘</span>
                    <strong><?= $completedLessons ?>/<?= $totalLessons ?></strong>
                    <small>Lessons Completed</small>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <span>🧩</span>
                    <strong><?= $hardwareAccuracy ?>%</strong>
                    <small>Hardware Accuracy</small>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <span>✅</span>
                    <strong><?= $quizBest ?>%</strong>
                    <small>Best Practice Quiz</small>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <span>🤖</span>
                    <strong><?= $aiChats ?></strong>
                    <small>AI Tutor Questions</small>
                </div>
            </div>
        </div>

        <section class="content-card">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <span class="eyebrow">Learning Progress</span>
                    <h2 class="h4 mt-2"><?= $overall ?>% complete</h2>
                    <div class="modern-progress mt-3">
                        <div class="modern-progress-bar" style="width: <?= max(0, min(100, $overall)) ?>%"></div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="next-step-card">
                        <span class="eyebrow">Recommended Next Step</span>

                        <?php if ($nextLesson): ?>
                            <h3 class="h5 mt-2"><?= htmlspecialchars($nextLesson['title']) ?></h3>
                            <a class="btn btn-primary btn-sm" href="lesson-view.php?id=<?= (int)$nextLesson['id'] ?>">Continue Lesson</a>
                        <?php else: ?>
                            <h3 class="h5 mt-2">Practice & Review</h3>
                            <a class="btn btn-primary btn-sm" href="quiz.php">Practice Quiz</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <section class="content-card">
            <span class="eyebrow">Research Access</span>
            <h2 class="h4 mt-2">
                <?= $group === 'control' ? 'Control Group Account' : 'Waiting for Group Assignment' ?>
            </h2>
            <p class="text-secondary mb-0">
                <?= $group === 'control'
                    ? 'The learning intervention is intentionally hidden for control-group participants. You can access the formal pretest/posttest when opened by the researchers.'
                    : 'Please contact the administrator before participating in the study.' ?>
            </p>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
