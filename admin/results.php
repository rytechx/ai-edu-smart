<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$totalLessons = (int)$pdo->query("SELECT COUNT(*) FROM lessons WHERE is_published = 1")->fetchColumn();

$students = $pdo->query("
    SELECT id, full_name, student_id, section
    FROM users
    WHERE role = 'student' AND is_active = 1
    ORDER BY full_name ASC
")->fetchAll();

$report = [];

foreach ($students as $student) {
    $userId = (int)$student['id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE user_id = ?");
    $stmt->execute([$userId]);
    $lessonsDone = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS attempts, COALESCE(SUM(is_correct),0) AS correct_answers
        FROM hardware_attempts
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $hw = $stmt->fetch();
    $hwAttempts = (int)($hw['attempts'] ?? 0);
    $hwCorrect = (int)($hw['correct_answers'] ?? 0);
    $hwAccuracy = $hwAttempts > 0 ? round(($hwCorrect / $hwAttempts) * 100) : 0;

    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS attempts,
               COALESCE(MAX(CASE WHEN total_items > 0 THEN (score / total_items) * 100 ELSE 0 END),0) AS best
        FROM quiz_attempts
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $quiz = $stmt->fetch();
    $quizAttempts = (int)($quiz['attempts'] ?? 0);
    $bestQuiz = (int)round((float)($quiz['best'] ?? 0));

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ai_chat_logs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $aiChats = (int)$stmt->fetchColumn();

    $report[] = [
        'student' => $student,
        'lessons_done' => $lessonsDone,
        'hw_attempts' => $hwAttempts,
        'hw_accuracy' => $hwAccuracy,
        'quiz_attempts' => $quizAttempts,
        'best_quiz' => $bestQuiz,
        'ai_chats' => $aiChats
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Reports | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart Admin
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Dashboard</a>
    </div>
</nav>

<main class="container py-5">
    <section class="hero-card mb-4">
        <div>
            <span class="eyebrow">Learning Analytics</span>
            <h1 class="mt-2">Student Reports</h1>
            <p class="mb-0">Simple participation and performance summary for each student.</p>
        </div>
        <div class="progress-badge">
            <span>Students</span>
            <strong><?= count($report) ?></strong>
        </div>
    </section>

    <section class="content-card">
        <?php if (!$report): ?>
            <p class="text-secondary mb-0">No active students found.</p>
        <?php else: ?>
            <div class="results-table-wrap">
                <table class="table align-middle mb-0 report-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Lessons</th>
                            <th>Hardware</th>
                            <th>Best Quiz</th>
                            <th>Quiz Attempts</th>
                            <th>AI Questions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report as $row): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($row['student']['full_name']) ?></strong><br>
                                    <small class="text-secondary">
                                        <?= htmlspecialchars($row['student']['section'] ?: 'No section') ?>
                                    </small>
                                </td>
                                <td><?= (int)$row['lessons_done'] ?> / <?= $totalLessons ?></td>
                                <td>
                                    <?= (int)$row['hw_accuracy'] ?>%
                                    <small class="text-secondary d-block"><?= (int)$row['hw_attempts'] ?> attempts</small>
                                </td>
                                <td>
                                    <span class="score-chip <?= $row['best_quiz'] >= 80 ? 'score-good' : ($row['best_quiz'] >= 60 ? 'score-mid' : 'score-low') ?>">
                                        <?= (int)$row['best_quiz'] ?>%
                                    </span>
                                </td>
                                <td><?= (int)$row['quiz_attempts'] ?></td>
                                <td><?= (int)$row['ai_chats'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="report-note mt-4">
                <strong>Note:</strong> These values are system activity indicators. They are not a substitute for the study's formal pretest/posttest statistical analysis.
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
