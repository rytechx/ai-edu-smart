<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
requireExperimental();
require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT l.id, l.title, l.slug, l.summary, l.sort_order,
           CASE WHEN lp.id IS NULL THEN 0 ELSE 1 END AS is_completed
    FROM lessons l
    LEFT JOIN lesson_progress lp
      ON lp.lesson_id = l.id AND lp.user_id = ?
    WHERE l.is_published = 1
    ORDER BY l.sort_order ASC, l.id ASC
");
$stmt->execute([$userId]);
$lessons = $stmt->fetchAll();

$completedCount = 0;
foreach ($lessons as $lesson) {
    if ((int)$lesson['is_completed'] === 1) {
        $completedCount++;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Interactive Lessons | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Dashboard</a>
    </div>
</nav>

<main class="container py-5">
    <section class="hero-card mb-4">
        <div>
            <span class="eyebrow">Interactive Learning</span>
            <h1 class="mt-2">Computer Hardware Lessons</h1>
            <p class="mb-0">Study each lesson and mark it complete when you finish.</p>
        </div>
        <div class="progress-badge">
            <span>Completed</span>
            <strong><?= $completedCount ?>/<?= count($lessons) ?></strong>
        </div>
    </section>

    <?php if (!$lessons): ?>
        <section class="content-card text-center py-5">
            <div class="display-5 mb-3">📘</div>
            <h2 class="h4">No lessons published yet</h2>
            <p class="text-secondary mb-0">Ask the administrator to add and publish lessons.</p>
        </section>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($lessons as $index => $lesson): ?>
                <div class="col-md-6 col-xl-4">
                    <a class="lesson-card <?= $lesson['is_completed'] ? 'lesson-card-complete' : '' ?>"
                       href="lesson-view.php?id=<?= (int)$lesson['id'] ?>">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="lesson-number"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></div>
                            <?php if ($lesson['is_completed']): ?>
                                <span class="completion-chip">✓ Done</span>
                            <?php endif; ?>
                        </div>

                        <span class="eyebrow">Lesson</span>
                        <h2><?= htmlspecialchars($lesson['title']) ?></h2>
                        <p><?= htmlspecialchars($lesson['summary'] ?: 'Open this lesson to begin learning.') ?></p>
                        <span class="lesson-link"><?= $lesson['is_completed'] ? 'Review Lesson →' : 'Start Lesson →' ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
