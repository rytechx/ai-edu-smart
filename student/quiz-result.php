<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
requireExperimental();

$result = $_SESSION['quiz_result'] ?? null;

if (!$result) {
    header('Location: quiz.php');
    exit;
}

$score = (int)$result['score'];
$total = (int)$result['total'];
$percent = $total > 0 ? round(($score / $total) * 100) : 0;

function optionText(array $question, ?string $letter): string {
    if (!$letter) return 'No answer';
    $map = [
        'A' => $question['option_a'],
        'B' => $question['option_b'],
        'C' => $question['option_c'],
        'D' => $question['option_d']
    ];
    return $map[$letter] ?? 'Unknown';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quiz Result | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart
        </a>
        <a class="btn btn-outline-secondary btn-sm" href="results.php">Result History</a>
    </div>
</nav>

<main class="container py-5">
    <section class="quiz-result-hero">
        <div class="score-ring">
            <strong><?= $percent ?>%</strong>
            <span><?= $score ?>/<?= $total ?></span>
        </div>
        <div>
            <span class="eyebrow">Quiz Complete</span>
            <h1 class="mt-2">
                <?= $percent >= 80 ? 'Excellent work!' : ($percent >= 60 ? 'Good effort!' : 'Keep practicing!') ?>
            </h1>
            <p class="mb-0">Review each item below to see the correct answer and explanation.</p>
        </div>
    </section>

    <div class="quiz-review-list mt-4">
        <?php foreach ($result['results'] as $index => $item): ?>
            <?php $q = $item['question']; ?>
            <section class="quiz-review-item <?= $item['is_correct'] ? 'review-correct' : 'review-wrong' ?>">
                <div class="review-status"><?= $item['is_correct'] ? '✓' : '×' ?></div>
                <div>
                    <span class="eyebrow">Question <?= $index + 1 ?></span>
                    <h2><?= htmlspecialchars($q['question']) ?></h2>

                    <div class="review-answer-line">
                        <strong>Your answer:</strong>
                        <?= htmlspecialchars(($item['selected'] ? $item['selected'] . '. ' : '') . optionText($q, $item['selected'])) ?>
                    </div>

                    <?php if (!$item['is_correct']): ?>
                        <div class="review-answer-line">
                            <strong>Correct answer:</strong>
                            <?= htmlspecialchars($item['correct'] . '. ' . optionText($q, $item['correct'])) ?>
                        </div>
                    <?php endif; ?>

                    <div class="explanation-box">
                        <?= htmlspecialchars($q['explanation'] ?: 'No explanation has been added for this question.') ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <a class="btn btn-primary" href="quiz.php">Retake Quiz</a>
        <a class="btn btn-outline-primary" href="results.php">View Result History</a>
        <a class="btn btn-outline-secondary" href="dashboard.php">Dashboard</a>
    </div>
</main>
</body>
</html>
