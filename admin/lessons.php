<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';

    if ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
            $stmt->execute([$id]);
            $flash = 'Lesson deleted.';
        }
    } else {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $title = trim($_POST['title'] ?? '');
        $summary = trim($_POST['summary'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $published = isset($_POST['is_published']) ? 1 : 0;

        if ($title === '') {
            $error = 'Lesson title is required.';
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

            if ($id) {
                $slug .= '-' . $id;
                $stmt = $pdo->prepare("
                    UPDATE lessons
                    SET title = ?, slug = ?, summary = ?, content = ?, sort_order = ?, is_published = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $slug, $summary, $content, $sortOrder, $published, $id]);
                $flash = 'Lesson updated.';
            } else {
                $slug .= '-' . time();
                $stmt = $pdo->prepare("
                    INSERT INTO lessons (title, slug, summary, content, sort_order, is_published)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $slug, $summary, $content, $sortOrder, $published]);
                $flash = 'Lesson added.';
            }
        }
    }
}

$editing = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ? LIMIT 1");
    $stmt->execute([$editId]);
    $editing = $stmt->fetch();
}

$lessons = $pdo->query("
    SELECT id, title, summary, sort_order, is_published, created_at
    FROM lessons
    ORDER BY sort_order ASC, id ASC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Lessons | AI Edu Smart</title>
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
            <span class="eyebrow">Content Management</span>
            <h1 class="mt-2">Manage Lessons</h1>
            <p class="mb-0">Create simple, clean lessons for Computer Hardware Fundamentals.</p>
        </div>
        <div class="progress-badge">
            <span>Total</span>
            <strong><?= count($lessons) ?></strong>
        </div>
    </section>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <section class="content-card">
                <span class="eyebrow"><?= $editing ? 'Edit Lesson' : 'New Lesson' ?></span>
                <h2 class="h4 mt-2 mb-4"><?= $editing ? 'Update lesson' : 'Add a lesson' ?></h2>

                <form method="post" class="vstack gap-3">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">

                    <div>
                        <label class="form-label">Lesson Title</label>
                        <input class="form-control" type="text" name="title" required
                               value="<?= htmlspecialchars($editing['title'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="form-label">Short Summary</label>
                        <textarea class="form-control" name="summary" rows="3"><?= htmlspecialchars($editing['summary'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="form-label">Lesson Content</label>
                        <textarea class="form-control lesson-editor" name="content" rows="12"
                                  placeholder="<h2>What is a motherboard?</h2><p>...</p>"><?= htmlspecialchars($editing['content'] ?? '') ?></textarea>
                        <div class="form-text">Basic HTML is supported for headings, paragraphs, lists, and emphasis.</div>
                    </div>

                    <div>
                        <label class="form-label">Order</label>
                        <input class="form-control" type="number" name="sort_order"
                               value="<?= (int)($editing['sort_order'] ?? 0) ?>">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_published" id="published"
                               <?= !isset($editing['is_published']) || (int)$editing['is_published'] === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="published">Published</label>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <?= $editing ? 'Save Changes' : 'Add Lesson' ?>
                        </button>
                        <?php if ($editing): ?>
                            <a class="btn btn-outline-secondary" href="lessons.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>
        </div>

        <div class="col-lg-7">
            <section class="content-card">
                <span class="eyebrow">Lesson Library</span>
                <h2 class="h4 mt-2 mb-4">Current lessons</h2>

                <?php if (!$lessons): ?>
                    <p class="text-secondary mb-0">No lessons yet. Add your first lesson using the form.</p>
                <?php else: ?>
                    <div class="admin-list">
                        <?php foreach ($lessons as $lesson): ?>
                            <div class="admin-list-item">
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <strong><?= htmlspecialchars($lesson['title']) ?></strong>
                                        <span class="badge <?= $lesson['is_published'] ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                            <?= $lesson['is_published'] ? 'Published' : 'Draft' ?>
                                        </span>
                                    </div>
                                    <small class="text-secondary">
                                        Order <?= (int)$lesson['sort_order'] ?>
                                        <?php if ($lesson['summary']): ?>
                                            · <?= htmlspecialchars($lesson['summary']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>

                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$lesson['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this lesson?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$lesson['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>
</main>
</body>
</html>
