<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];
$userId = (int)$user['id'];
$type = $_GET['type'] ?? $_POST['assessment_type'] ?? '';

if (!in_array($type, ['pre','post'], true)) {
    header('Location: research.php');
    exit;
}

if (empty($user['research_group'])) {
    header('Location: research.php');
    exit;
}

$settingKey = $type === 'pre' ? 'pre_engagement_open' : 'post_engagement_open';

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
    FROM engagement_attempts
    WHERE user_id = ? AND assessment_type = ?
    LIMIT 1
");
$stmt->execute([$userId, $type]);

if ($stmt->fetch()) {
    header('Location: research.php');
    exit;
}

$questions = $pdo->query("
    SELECT id, item_number, dimension, statement
    FROM engagement_questions
    WHERE is_active = 1
    ORDER BY item_number ASC
")->fetchAll();

if (!$questions) {
    exit('Engagement questionnaire items are not available.');
}

if (empty($_SESSION['engagement_csrf'])) {
    $_SESSION['engagement_csrf'] = bin2hex(random_bytes(24));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['engagement_csrf'], $token)) {
        $error = 'Security token expired. Please reload the questionnaire.';
    } else {
        $answers = $_POST['answer'] ?? [];

        if (count($answers) !== count($questions)) {
            $error = 'Please answer all 15 statements before submitting.';
        } else {
            $dimensionValues = [
                'behavioral' => [],
                'cognitive' => [],
                'emotional' => []
            ];
            $prepared = [];

            foreach ($questions as $q) {
                $value = filter_var(
                    $answers[$q['id']] ?? null,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1, 'max_range' => 4]]
                );

                if ($value === false) {
                    $error = 'One or more responses are invalid.';
                    break;
                }

                $dimensionValues[$q['dimension']][] = $value;

                $prepared[] = [
                    'question_id' => (int)$q['id'],
                    'value' => (int)$value
                ];
            }

            if ($error === '') {
                $mean = function(array $values): float {
                    return count($values) > 0
                        ? round(array_sum($values) / count($values), 2)
                        : 0.00;
                };

                $behavioral = $mean($dimensionValues['behavioral']);
                $cognitive = $mean($dimensionValues['cognitive']);
                $emotional = $mean($dimensionValues['emotional']);

                $allValues = array_merge(
                    $dimensionValues['behavioral'],
                    $dimensionValues['cognitive'],
                    $dimensionValues['emotional']
                );
                $overall = $mean($allValues);

                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        INSERT INTO engagement_attempts
                        (user_id, assessment_type, behavioral_mean, cognitive_mean, emotional_mean, overall_mean)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $userId,
                        $type,
                        $behavioral,
                        $cognitive,
                        $emotional,
                        $overall
                    ]);
                    $attemptId = (int)$pdo->lastInsertId();

                    $insertAnswer = $pdo->prepare("
                        INSERT INTO engagement_answers
                        (attempt_id, question_id, response_value)
                        VALUES (?, ?, ?)
                    ");

                    foreach ($prepared as $row) {
                        $insertAnswer->execute([
                            $attemptId,
                            $row['question_id'],
                            $row['value']
                        ]);
                    }

                    $pdo->commit();
                    unset($_SESSION['engagement_csrf']);

                    header('Location: engagement-complete.php?type=' . urlencode($type));
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                        $error = 'This questionnaire has already been submitted.';
                    } else {
                        $error = 'Unable to save the questionnaire. Please contact the administrator.';
                    }
                }
            }
        }
    }
}

$label = $type === 'pre' ? 'Pre-Engagement Questionnaire' : 'Post-Engagement Questionnaire';

$dimensionTitles = [
    'behavioral' => 'Part I. Behavioral Engagement',
    'cognitive' => 'Part II. Cognitive Engagement',
    'emotional' => 'Part III. Emotional Engagement'
];
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
            <span class="mini-mark">AI</span> Student Engagement
        </span>
        <span class="research-test-label"><?= htmlspecialchars($type === 'pre' ? 'PRE' : 'POST') ?></span>
    </div>
</nav>

<main class="container py-5">
    <section class="content-card mb-4">
        <span class="eyebrow">Student Engagement Questionnaire</span>
        <h1 class="mt-2"><?= htmlspecialchars($label) ?></h1>
        <p class="text-secondary mb-2">
            Please read each statement carefully and select the response that best represents
            your experience during Computer Hardware Fundamentals lessons.
        </p>

        <div class="likert-legend">
            <span><strong>4</strong> Strongly Agree</span>
            <span><strong>3</strong> Agree</span>
            <span><strong>2</strong> Disagree</span>
            <span><strong>1</strong> Strongly Disagree</span>
        </div>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" id="engagementForm" class="engagement-form">
        <input type="hidden" name="assessment_type" value="<?= htmlspecialchars($type) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['engagement_csrf']) ?>">

        <?php $currentDimension = ''; ?>

        <?php foreach ($questions as $q): ?>
            <?php if ($currentDimension !== $q['dimension']): ?>
                <?php $currentDimension = $q['dimension']; ?>
                <div class="research-part-title">
                    <?= htmlspecialchars($dimensionTitles[$currentDimension]) ?>
                </div>
            <?php endif; ?>

            <section class="engagement-item-card">
                <div class="engagement-item-number"><?= (int)$q['item_number'] ?></div>

                <div class="engagement-item-main">
                    <h2><?= htmlspecialchars($q['statement']) ?></h2>

                    <div class="likert-options">
                        <?php
                        $choices = [
                            4 => 'Strongly Agree',
                            3 => 'Agree',
                            2 => 'Disagree',
                            1 => 'Strongly Disagree'
                        ];
                        ?>

                        <?php foreach ($choices as $value => $text): ?>
                            <label class="likert-option">
                                <input type="radio"
                                       name="answer[<?= (int)$q['id'] ?>]"
                                       value="<?= $value ?>"
                                       required>
                                <span class="likert-number"><?= $value ?></span>
                                <span class="likert-text"><?= htmlspecialchars($text) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>

        <section class="content-card quiz-submit-card">
            <div>
                <span class="eyebrow">Final Submission</span>
                <h2 class="h4 mt-2 mb-1">Submit Questionnaire</h2>
                <p class="text-secondary mb-0">
                    You cannot change your responses after submission.
                </p>
            </div>

            <button class="btn btn-primary btn-lg" type="submit"
                    onclick="return confirm('Submit your final responses? You cannot retake this questionnaire.');">
                Submit Responses
            </button>
        </section>
    </form>
</main>

<script>
let submitted = false;

document.getElementById('engagementForm').addEventListener('submit', () => {
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
