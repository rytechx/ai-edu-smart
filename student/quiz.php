<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
requireExperimental();
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];

function loadQuizQuestions(PDO $pdo): array {
    return $pdo->query("
        SELECT id, question, option_a, option_b, option_c, option_d
        FROM quiz_questions
        ORDER BY id ASC
        LIMIT 15
    ")->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionIds = $_SESSION['quiz_question_ids'] ?? [];

    if (!$questionIds) {
        header('Location: quiz.php');
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    $stmt = $pdo->prepare("
        SELECT id, question, correct_option, explanation,
               option_a, option_b, option_c, option_d
        FROM quiz_questions
        WHERE id IN ($placeholders)
        ORDER BY FIELD(id, $placeholders)
    ");
    $stmt->execute(array_merge($questionIds, $questionIds));
    $questions = $stmt->fetchAll();

    $score = 0;
    $results = [];

    foreach ($questions as $q) {
        $selected = strtoupper(trim($_POST['answer'][$q['id']] ?? ''));
        if (!in_array($selected, ['A','B','C','D'], true)) {
            $selected = null;
        }

        $isCorrect = ($selected === $q['correct_option']);
        if ($isCorrect) {
            $score++;
        }

        $results[] = [
            'question' => $q,
            'selected' => $selected,
            'correct' => $q['correct_option'],
            'is_correct' => $isCorrect
        ];
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO quiz_attempts (user_id, score, total_items)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user['id'], $score, count($questions)]);
        $attemptId = (int)$pdo->lastInsertId();

        $ans = $pdo->prepare("
            INSERT INTO quiz_attempt_answers
            (attempt_id, question_id, selected_option, is_correct)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($results as $r) {
            $ans->execute([
                $attemptId,
                $r['question']['id'],
                $r['selected'],
                $r['is_correct'] ? 1 : 0
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    $_SESSION['quiz_result'] = [
        'attempt_id' => $attemptId,
        'score' => $score,
        'total' => count($questions),
        'results' => $results
    ];
    unset($_SESSION['quiz_question_ids']);

    header('Location: quiz-result.php');
    exit;
}

$questions = loadQuizQuestions($pdo);

if ($questions) {
    $_SESSION['quiz_question_ids'] = array_column($questions, 'id');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quiz & Practice | AI Edu Smart</title>
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
            <span class="eyebrow">Knowledge Check</span>
            <h1 class="mt-2">Computer Hardware Quiz</h1>
            <p class="mb-0">Answer each item, submit once, and review the explanations afterward.</p>
        </div>
        <div class="progress-badge">
            <span>Items</span>
            <strong><?= count($questions) ?></strong>
        </div>
    </section>

    <?php if (!$questions): ?>
        <section class="content-card text-center py-5">
            <div class="display-5 mb-3">✅</div>
            <h2 class="h4">No quiz questions available</h2>
            <p class="text-secondary">The administrator needs to add quiz questions first.</p>
        </section>
    <?php else: ?>
        <form method="post" class="quiz-form">
            <?php foreach ($questions as $index => $q): ?>
                <section class="quiz-question-card">
                    <div class="quiz-question-number"><?= $index + 1 ?></div>
                    <div class="quiz-question-main">
                        <h2><?= htmlspecialchars($q['question']) ?></h2>

                        <?php
                        $options = [
                            'A' => $q['option_a'],
                            'B' => $q['option_b'],
                            'C' => $q['option_c'],
                            'D' => $q['option_d'],
                        ];
                        ?>
                        <div class="quiz-options">
                            <?php foreach ($options as $letter => $text): ?>
                                <label class="quiz-option">
                                    <input type="radio"
                                           name="answer[<?= (int)$q['id'] ?>]"
                                           value="<?= $letter ?>"
                                           required>
                                    <span class="quiz-letter"><?= $letter ?></span>
                                    <span class="quiz-text"><?= htmlspecialchars($text) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>

            <section class="content-card quiz-submit-card">
                <div>
                    <span class="eyebrow">Finish Quiz</span>
                    <h2 class="h4 mt-2 mb-1">Ready to submit?</h2>
                    <p class="text-secondary mb-0">You will receive your score and explanation for every item.</p>
                </div>
                <button class="btn btn-primary btn-lg" type="submit">Submit Quiz</button>
            </section>
        </form>
    <?php endif; ?>
</main>
</body>
</html>
