<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
requireExperimental();
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];
$userId = (int)$user['id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: lessons.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, title, summary, content
    FROM lessons
    WHERE id = ? AND is_published = 1
    LIMIT 1
");
$stmt->execute([$id]);
$lesson = $stmt->fetch();

if (!$lesson) {
    http_response_code(404);
    exit('Lesson not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'complete') {
    $stmt = $pdo->prepare("
        INSERT INTO lesson_progress (user_id, lesson_id)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE completed_at = completed_at
    ");
    $stmt->execute([$userId, $id]);

    header('Location: lesson-view.php?id=' . $id . '&completed=1');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id
    FROM lesson_progress
    WHERE user_id = ? AND lesson_id = ?
    LIMIT 1
");
$stmt->execute([$userId, $id]);
$isCompleted = (bool)$stmt->fetch();

$nextStmt = $pdo->prepare("
    SELECT id, title
    FROM lessons
    WHERE is_published = 1
      AND (sort_order > (SELECT sort_order FROM lessons WHERE id = ?)
           OR (sort_order = (SELECT sort_order FROM lessons WHERE id = ?) AND id > ?))
    ORDER BY sort_order ASC, id ASC
    LIMIT 1
");
$nextStmt->execute([$id, $id, $id]);
$nextLesson = $nextStmt->fetch();

$justCompleted = isset($_GET['completed']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($lesson['title']) ?> | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="lessons.php">All Lessons</a>
    </div>
</nav>

<main class="container py-5">
    <?php if ($justCompleted): ?>
        <div class="alert alert-success mx-auto mb-4" style="max-width:920px;">
            Lesson marked as complete.
        </div>
    <?php endif; ?>

    <article class="lesson-reader">
        <div class="lesson-reader-head">
            <div class="d-flex justify-content-between gap-3 align-items-start flex-wrap">
                <div>
                    <span class="eyebrow">Computer Hardware Fundamentals</span>
                    <h1><?= htmlspecialchars($lesson['title']) ?></h1>
                    <?php if (!empty($lesson['summary'])): ?>
                        <p><?= htmlspecialchars($lesson['summary']) ?></p>
                    <?php endif; ?>
                </div>

                <?php if ($isCompleted): ?>
                    <span class="completion-chip">✓ Completed</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="lesson-content">
            <?= $lesson['content'] ?: '<p>No lesson content has been added yet.</p>' ?>
        </div>

        <div class="lesson-reader-footer">
            <a class="btn btn-outline-secondary" href="lessons.php">← Back to Lessons</a>

            <div class="d-flex flex-wrap gap-2">
                <?php if (!$isCompleted): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="complete">
                        <button class="btn btn-success" type="submit">✓ Mark as Complete</button>
                    </form>
                <?php elseif ($nextLesson): ?>
                    <a class="btn btn-primary" href="lesson-view.php?id=<?= (int)$nextLesson['id'] ?>">Next Lesson →</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="quiz.php">Take Quiz →</a>
                <?php endif; ?>
            </div>
        </div>
    </article>
</main>
</body>
</html>
