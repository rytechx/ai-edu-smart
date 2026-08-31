<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];
$userId = (int)$user['id'];
$type = $_GET['type'] ?? $_POST['assessment_type'] ?? '';

if (!in_array($type, ['pretest','posttest'], true)) {
    header('Location: research.php');
    exit;
}

if (empty($user['research_group'])) {
    header('Location: research.php');
    exit;
}

$settingKey = $type . '_open';
$stmt = $pdo->prepare("
    SELECT setting_value
    FROM research_settings
    WHERE setting_key = ?
    LIMIT 1
");
$stmt->execute([$settingKey]);
$isOpen = $stmt->fetchColumn() === '1';

if (!$isOpen) {
    header('Location: research.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT id
    FROM research_attempts
    WHERE user_id = ? AND assessment_type = ?
    LIMIT 1
");
$stmt->execute([$userId, $type]);
if ($stmt->fetch()) {
    header('Location: research.php');
    exit;
}

$questions = $pdo->query("
    SELECT *
    FROM research_questions
    WHERE is_active = 1
    ORDER BY item_number ASC
")->fetchAll();

if (!$questions) {
    exit('Research assessment questions are not available.');
}

if (empty($_SESSION['research_csrf'])) {
    $_SESSION['research_csrf'] = bin2hex(random_bytes(24));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['research_csrf'], $token)) {
        $error = 'Security token expired. Please reload the assessment.';
    } else {
        $answers = $_POST['answer'] ?? [];

        if (count($answers) !== count($questions)) {
            $error = 'Please answer all questions before submitting.';
        } else {
            $score = 0;
            $prepared = [];

            foreach ($questions as $q) {
                $selected = strtoupper(trim($answers[$q['id']] ?? ''));

                if (!in_array($selected, ['A','B','C','D'], true)) {
                    $error = 'One or more answers are invalid.';
                    break;
                }

                $isCorrect = $selected === $q['correct_option'];
                if ($isCorrect) {
                    $score++;
                }

                $prepared[] = [
                    'question_id' => (int)$q['id'],
                    'selected' => $selected,
                    'is_correct' => $isCorrect ? 1 : 0
                ];
            }

            if ($error === '') {
                try {
                    $pdo->beginTransaction();

                    $insertAttempt = $pdo->prepare("
                        INSERT INTO research_attempts
                        (user_id, assessment_type, score, total_items)
                        VALUES (?, ?, ?, ?)
                    ");
                    $insertAttempt->execute([$userId, $type, $score, count($questions)]);
                    $attemptId = (int)$pdo->lastInsertId();

                    $insertAnswer = $pdo->prepare("
                        INSERT INTO research_answers
                        (attempt_id, question_id, selected_option, is_correct)
                        VALUES (?, ?, ?, ?)
                    ");

                    foreach ($prepared as $row) {
                        $insertAnswer->execute([
                            $attemptId,
                            $row['question_id'],
                            $row['selected'],
                            $row['is_correct']
                        ]);
                    }

                    $pdo->commit();
                    unset($_SESSION['research_csrf']);

                    header('Location: research-complete.php?type=' . urlencode($type));
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                        $error = 'This assessment has already been submitted.';
                    } else {
                        $error = 'Unable to save the assessment. Please contact the administrator.';
                    }
                }
            }
        }
    }
}

$label = $type === 'pretest' ? 'Pretest' : 'Posttest';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($label) ?> | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body research-test-body">
<nav class="navbar app-navbar">
    <div class="container">
        <span class="navbar-brand brand-inline">
            <span class="mini-mark">AI</span> Formal Research Assessment
        </span>
        <span class="research-test-label"><?= htmlspecialchars($label) ?></span>
    </div>
</nav>

<main class="container py-5">
    <section class="content-card mb-4">
        <span class="eyebrow">Computer Hardware Fundamentals Achievement Test</span>
        <h1 class="mt-2"><?= htmlspecialchars($label) ?></h1>
        <p class="text-secondary mb-0">
            Read each question carefully and choose the letter of the best answer.
            All 15 questions are required.
        </p>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" id="researchForm" class="quiz-form">
        <input type="hidden" name="assessment_type" value="<?= htmlspecialchars($type) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['research_csrf']) ?>">

        <?php $currentPart = ''; ?>

        <?php foreach ($questions as $q): ?>
            <?php if ($currentPart !== $q['part_title']): ?>
                <?php $currentPart = $q['part_title']; ?>
                <div class="research-part-title"><?= htmlspecialchars($currentPart) ?></div>
            <?php endif; ?>

            <section class="quiz-question-card">
                <div class="quiz-question-number"><?= (int)$q['item_number'] ?></div>

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
                <span class="eyebrow">Final Submission</span>
                <h2 class="h4 mt-2 mb-1">Submit <?= htmlspecialchars($label) ?></h2>
                <p class="text-secondary mb-0">
                    You cannot change your answers after submission.
                </p>
            </div>

            <button class="btn btn-primary btn-lg" type="submit"
                    onclick="return confirm('Submit your final answers? You cannot retake this assessment.');">
                Submit Final Answers
            </button>
        </section>
    </form>
</main>

<script>
let submitted = false;

document.getElementById('researchForm').addEventListener('submit', () => {
    submitted = true;
});

window.addEventListener('beforeunload', (event) => {
    if (!submitted) {
        event.preventDefault();
        event.returnValue = '';
    }
});
</script>
</body>
</html>
