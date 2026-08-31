<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];

$studentCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = 1")->fetchColumn();
$lessonCount = (int)$pdo->query("SELECT COUNT(*) FROM lessons WHERE is_published = 1")->fetchColumn();
$hardwareCount = (int)$pdo->query("SELECT COUNT(*) FROM hardware_components")->fetchColumn();
$quizCount = (int)$pdo->query("SELECT COUNT(*) FROM quiz_questions")->fetchColumn();
$quizAttempts = (int)$pdo->query("SELECT COUNT(*) FROM quiz_attempts")->fetchColumn();
$hardwareAttempts = (int)$pdo->query("SELECT COUNT(*) FROM hardware_attempts")->fetchColumn();
$aiChats = (int)$pdo->query("SELECT COUNT(*) FROM ai_chat_logs")->fetchColumn();
$completedLessons = (int)$pdo->query("SELECT COUNT(*) FROM lesson_progress")->fetchColumn();

$recentStmt = $pdo->query("
    SELECT u.full_name, qa.score, qa.total_items, qa.created_at
    FROM quiz_attempts qa
    JOIN users u ON u.id = qa.user_id
    ORDER BY qa.created_at DESC, qa.id DESC
    LIMIT 5
");
$recentQuiz = $recentStmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart Admin
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
            <span class="eyebrow">Administration</span>
            <h1 class="mt-2">System Dashboard</h1>
            <p class="mb-0">Manage content and review student learning activity.</p>
        </div>
        <div class="progress-badge">
            <span>Status</span>
            <strong>Ready</strong>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card"><span>👥</span><strong><?= $studentCount ?></strong><small>Students</small></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card"><span>📘</span><strong><?= $lessonCount ?></strong><small>Published Lessons</small></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card"><span>🧩</span><strong><?= $hardwareCount ?></strong><small>Hardware Items</small></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card"><span>✅</span><strong><?= $quizCount ?></strong><small>Quiz Questions</small></div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-md-3">
            <div class="mini-activity-card"><strong><?= $completedLessons ?></strong><span>Lesson completions</span></div>
        </div>
        <div class="col-md-3">
            <div class="mini-activity-card"><strong><?= $hardwareAttempts ?></strong><span>Hardware attempts</span></div>
        </div>
        <div class="col-md-3">
            <div class="mini-activity-card"><strong><?= $quizAttempts ?></strong><span>Quiz attempts</span></div>
        </div>
        <div class="col-md-3">
            <div class="mini-activity-card"><strong><?= $aiChats ?></strong><span>AI tutor questions</span></div>
        </div>
    </div>

    <section class="content-card mt-4">
        <span class="eyebrow">Management</span>
        <h2 class="h4 mt-2">System modules</h2>
        <div class="row g-3 mt-1">
            <div class="col-md-4"><a class="admin-tile admin-tile-link" href="students.php">Students <span>→</span></a></div>
            <div class="col-md-4"><a class="admin-tile admin-tile-link" href="lessons.php">Manage Lessons <span>→</span></a></div>
            <div class="col-md-4"><a class="admin-tile admin-tile-link" href="hardware.php">Manage Hardware <span>→</span></a></div>
            <div class="col-md-4"><a class="admin-tile admin-tile-link" href="quiz.php">Manage Quiz <span>→</span></a></div>
            <div class="col-md-4"><a class="admin-tile admin-tile-link" href="results.php">Student Reports <span>→</span></a></div>
            <div class="col-md-4"><a class="admin-tile admin-tile-link" href="ai-settings.php">AI Settings <span>→</span></a></div>
            <div class="col-md-4"><a class="admin-tile admin-tile-link" href="research.php">Achievement Research <span>→</span></a></div>
            <div class="col-md-4"><a class="admin-tile admin-tile-link" href="engagement.php">Engagement Research <span>→</span></a></div>
        </div>
    </section>

    <section class="content-card mt-4">
        <span class="eyebrow">Recent Activity</span>
        <h2 class="h4 mt-2 mb-3">Latest quiz attempts</h2>

        <?php if (!$recentQuiz): ?>
            <p class="text-secondary mb-0">No quiz attempts recorded yet.</p>
        <?php else: ?>
            <div class="results-table-wrap">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentQuiz as $row): ?>
                            <?php
                            $pct = (int)$row['total_items'] > 0
                                ? round(((int)$row['score'] / (int)$row['total_items']) * 100)
                                : 0;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= (int)$row['score'] ?> / <?= (int)$row['total_items'] ?></td>
                                <td><?= $pct ?>%</td>
                                <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($row['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
