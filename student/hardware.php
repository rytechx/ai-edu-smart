<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
requireExperimental();
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];
$feedback = null;

function getRound(PDO $pdo): ?array {
    $components = $pdo->query("
        SELECT id, name, category, function_text, image_path
        FROM hardware_components
        ORDER BY RAND()
        LIMIT 4
    ")->fetchAll();

    if (count($components) < 2) {
        return null;
    }

    $correct = $components[array_rand($components)];
    shuffle($components);

    return [
        'correct' => $correct,
        'choices' => $components
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_id'])) {
    $selectedId = filter_var($_POST['selected_id'], FILTER_VALIDATE_INT);
    $correctId = $_SESSION['hardware_round']['correct']['id'] ?? null;

    if ($selectedId && $correctId) {
        $stmt = $pdo->prepare("SELECT id, name FROM hardware_components WHERE id = ? LIMIT 1");
        $stmt->execute([$selectedId]);
        $selected = $stmt->fetch();

        $correct = $_SESSION['hardware_round']['correct'];
        $isCorrect = ((int)$selectedId === (int)$correctId);

        $insert = $pdo->prepare("
            INSERT INTO hardware_attempts (user_id, component_id, selected_component_id, is_correct)
            VALUES (?, ?, ?, ?)
        ");
        $insert->execute([
            $user['id'],
            $correctId,
            $selectedId,
            $isCorrect ? 1 : 0
        ]);

        $feedback = [
            'correct' => $isCorrect,
            'selected_name' => $selected['name'] ?? 'Unknown',
            'correct_name' => $correct['name'],
            'explanation' => $correct['function_text'],
        ];

        unset($_SESSION['hardware_round']);
    }
}

if (!$feedback && empty($_SESSION['hardware_round'])) {
    $_SESSION['hardware_round'] = getRound($pdo);
}

$round = $_SESSION['hardware_round'] ?? null;

$statsStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS attempts,
        COALESCE(SUM(is_correct), 0) AS correct_answers
    FROM hardware_attempts
    WHERE user_id = ?
");
$statsStmt->execute([$user['id']]);
$stats = $statsStmt->fetch();

$attempts = (int)($stats['attempts'] ?? 0);
$correctAnswers = (int)($stats['correct_answers'] ?? 0);
$accuracy = $attempts > 0 ? round(($correctAnswers / $attempts) * 100) : 0;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hardware Identification | AI Edu Smart</title>
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
            <span class="eyebrow">Interactive Activity</span>
            <h1 class="mt-2">Hardware Identification</h1>
            <p class="mb-0">Look at the component and choose its correct name.</p>
        </div>
        <div class="hw-stats">
            <div><strong><?= $attempts ?></strong><span>Attempts</span></div>
            <div><strong><?= $accuracy ?>%</strong><span>Accuracy</span></div>
        </div>
    </section>

    <?php if ($feedback): ?>
        <section class="feedback-card <?= $feedback['correct'] ? 'feedback-correct' : 'feedback-wrong' ?>">
            <div class="feedback-icon"><?= $feedback['correct'] ? '✓' : '×' ?></div>
            <div>
                <span class="eyebrow"><?= $feedback['correct'] ? 'Correct Answer' : 'Try Again' ?></span>
                <h2><?= $feedback['correct'] ? 'Great job!' : 'Not quite.' ?></h2>

                <?php if (!$feedback['correct']): ?>
                    <p class="mb-2">
                        You selected <strong><?= htmlspecialchars($feedback['selected_name']) ?></strong>.
                        The correct answer is <strong><?= htmlspecialchars($feedback['correct_name']) ?></strong>.
                    </p>
                <?php else: ?>
                    <p class="mb-2">
                        You correctly identified <strong><?= htmlspecialchars($feedback['correct_name']) ?></strong>.
                    </p>
                <?php endif; ?>

                <div class="explanation-box">
                    <?= htmlspecialchars($feedback['explanation'] ?: 'No explanation has been added yet.') ?>
                </div>

                <a class="btn btn-primary mt-3" href="hardware.php">Next Component →</a>
            </div>
        </section>
    <?php elseif (!$round): ?>
        <section class="content-card text-center py-5">
            <div class="display-5 mb-3">🧩</div>
            <h2 class="h4">Not enough hardware items yet</h2>
            <p class="text-secondary">An administrator must add at least two hardware components.</p>
        </section>
    <?php else: ?>
        <section class="hardware-activity-card">
            <div class="hardware-image-wrap">
                <?php
                $image = $round['correct']['image_path'] ?? '';
                if ($image):
                ?>
                    <img src="../<?= htmlspecialchars($image) ?>" alt="Computer hardware component">
                <?php else: ?>
                    <div class="hardware-placeholder">?</div>
                <?php endif; ?>
            </div>

            <div class="hardware-question">
                <span class="eyebrow">Identify the Component</span>
                <h2>What computer hardware is shown?</h2>
                <p class="text-secondary">Choose one answer, then submit.</p>

                <form method="post" class="choice-grid">
                    <?php foreach ($round['choices'] as $choice): ?>
                        <label class="choice-option">
                            <input type="radio" name="selected_id" value="<?= (int)$choice['id'] ?>" required>
                            <span><?= htmlspecialchars($choice['name']) ?></span>
                        </label>
                    <?php endforeach; ?>

                    <button class="btn btn-primary btn-lg mt-2" type="submit">Submit Answer</button>
                </form>
            </div>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
