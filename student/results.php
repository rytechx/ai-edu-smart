<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
requireExperimental();
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];

$stmt = $pdo->prepare("
    SELECT id, score, total_items, created_at
    FROM quiz_attempts
    WHERE user_id = ?
    ORDER BY created_at DESC, id DESC
");
$stmt->execute([$user['id']]);
$attempts = $stmt->fetchAll();

$best = 0;
foreach ($attempts as $a) {
    if ((int)$a['total_items'] > 0) {
        $pct = round(((int)$a['score'] / (int)$a['total_items']) * 100);
        $best = max($best, $pct);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Results | AI Edu Smart</title>
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
            <span class="eyebrow">Student Progress</span>
            <h1 class="mt-2">Quiz Result History</h1>
            <p class="mb-0">Review your previous quiz attempts and best score.</p>
        </div>
        <div class="hw-stats">
            <div><strong><?= count($attempts) ?></strong><span>Attempts</span></div>
            <div><strong><?= $best ?>%</strong><span>Best</span></div>
        </div>
    </section>

    <section class="content-card">
        <?php if (!$attempts): ?>
            <div class="text-center py-4">
                <div class="display-5 mb-3">📊</div>
                <h2 class="h4">No quiz attempts yet</h2>
                <p class="text-secondary">Take the quiz to create your first result.</p>
                <a class="btn btn-primary" href="quiz.php">Take Quiz</a>
            </div>
        <?php else: ?>
            <div class="results-table-wrap">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Attempt</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attempts as $i => $a): ?>
                            <?php
                            $pct = (int)$a['total_items'] > 0
                                ? round(((int)$a['score'] / (int)$a['total_items']) * 100)
                                : 0;
                            ?>
                            <tr>
                                <td>#<?= count($attempts) - $i ?></td>
                                <td><?= (int)$a['score'] ?> / <?= (int)$a['total_items'] ?></td>
                                <td>
                                    <span class="score-chip <?= $pct >= 80 ? 'score-good' : ($pct >= 60 ? 'score-mid' : 'score-low') ?>">
                                        <?= $pct ?>%
                                    </span>
                                </td>
                                <td><?= htmlspecialchars(date('M d, Y h:i A', strtotime($a['created_at']))) ?></td>
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
