<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    try {
        if ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($id) {
                $pdo->prepare("DELETE FROM quiz_questions WHERE id = ?")->execute([$id]);
                $flash = 'Quiz question deleted.';
            }
        } else {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $question = trim($_POST['question'] ?? '');
            $a = trim($_POST['option_a'] ?? '');
            $b = trim($_POST['option_b'] ?? '');
            $c = trim($_POST['option_c'] ?? '');
            $d = trim($_POST['option_d'] ?? '');
            $correct = strtoupper(trim($_POST['correct_option'] ?? ''));
            $explanation = trim($_POST['explanation'] ?? '');

            if ($question === '' || $a === '' || $b === '' || $c === '' || $d === '') {
                throw new RuntimeException('Question and all four answer choices are required.');
            }

            if (!in_array($correct, ['A','B','C','D'], true)) {
                throw new RuntimeException('Select the correct answer.');
            }

            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE quiz_questions
                    SET question = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?,
                        correct_option = ?, explanation = ?
                    WHERE id = ?
                ");
                $stmt->execute([$question, $a, $b, $c, $d, $correct, $explanation, $id]);
                $flash = 'Quiz question updated.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_questions
                    (question, option_a, option_b, option_c, option_d, correct_option, explanation)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$question, $a, $b, $c, $d, $correct, $explanation]);
                $flash = 'Quiz question added.';
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE id = ? LIMIT 1");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

$questions = $pdo->query("
    SELECT id, question, option_a, option_b, option_c, option_d, correct_option, explanation
    FROM quiz_questions
    ORDER BY id ASC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Quiz | AI Edu Smart</title>
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
            <span class="eyebrow">Assessment Content</span>
            <h1 class="mt-2">Manage Quiz Questions</h1>
            <p class="mb-0">Create multiple-choice items with immediate explanations.</p>
        </div>
        <div class="progress-badge">
            <span>Questions</span>
            <strong><?= count($questions) ?></strong>
        </div>
    </section>

    <?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <section class="content-card">
                <span class="eyebrow"><?= $editing ? 'Edit Question' : 'New Question' ?></span>
                <h2 class="h4 mt-2 mb-4"><?= $editing ? 'Update question' : 'Add question' ?></h2>

                <form method="post" class="vstack gap-3">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

                    <div>
                        <label class="form-label">Question</label>
                        <textarea class="form-control" name="question" rows="4" required><?= htmlspecialchars($editing['question'] ?? '') ?></textarea>
                    </div>

                    <?php foreach (['A','B','C','D'] as $letter): ?>
                        <?php $key = 'option_' . strtolower($letter); ?>
                        <div>
                            <label class="form-label">Option <?= $letter ?></label>
                            <input class="form-control" type="text" name="<?= $key ?>" required
                                   value="<?= htmlspecialchars($editing[$key] ?? '') ?>">
                        </div>
                    <?php endforeach; ?>

                    <div>
                        <label class="form-label">Correct Answer</label>
                        <select class="form-select" name="correct_option" required>
                            <option value="">Select answer</option>
                            <?php foreach (['A','B','C','D'] as $letter): ?>
                                <option value="<?= $letter ?>"
                                    <?= (($editing['correct_option'] ?? '') === $letter) ? 'selected' : '' ?>>
                                    <?= $letter ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Explanation</label>
                        <textarea class="form-control" name="explanation" rows="4"
                                  placeholder="Explain why the answer is correct."><?= htmlspecialchars($editing['explanation'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <?= $editing ? 'Save Changes' : 'Add Question' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a class="btn btn-outline-secondary" href="quiz.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
        </div>

        <div class="col-lg-7">
            <section class="content-card">
                <span class="eyebrow">Question Bank</span>
                <h2 class="h4 mt-2 mb-4">Current questions</h2>

                <?php if (!$questions): ?>
                    <p class="text-secondary">No quiz questions yet.</p>
                <?php else: ?>
                    <div class="question-bank">
                        <?php foreach ($questions as $i => $q): ?>
                            <article class="question-bank-item">
                                <div class="question-bank-number"><?= $i + 1 ?></div>
                                <div class="question-bank-content">
                                    <strong><?= htmlspecialchars($q['question']) ?></strong>
                                    <small>Correct answer: <?= htmlspecialchars($q['correct_option']) ?></small>
                                </div>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$q['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this quiz question?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
</body>
</html>
