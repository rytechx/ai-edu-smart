<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $group = $_POST['research_group'] ?? '';

    if ($userId && in_array($group, ['experimental','control',''], true)) {
        $value = $group === '' ? null : $group;

        $stmt = $pdo->prepare("
            UPDATE users
            SET research_group = ?
            WHERE id = ? AND role = 'student'
        ");
        $stmt->execute([$value, $userId]);

        $flash = 'Research group updated.';
    }
}

$students = $pdo->query("
    SELECT id, student_id, full_name, username, section, research_group, is_active, created_at
    FROM users
    WHERE role = 'student'
    ORDER BY full_name ASC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Students | AI Edu Smart</title>
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
            <span class="eyebrow">Research Participants</span>
            <h1 class="mt-2">Students & Group Assignment</h1>
            <p class="mb-0">
                Assign each participant to Experimental or Control before data collection.
            </p>
        </div>
        <div class="progress-badge">
            <span>Total</span>
            <strong><?= count($students) ?></strong>
        </div>
    </section>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
        Control-group accounts can access the formal research assessments, but learning
        intervention modules are blocked. Experimental-group accounts can access both.
    </div>

    <section class="content-card">
        <?php if (!$students): ?>
            <p class="text-secondary mb-0">No student accounts found.</p>
        <?php else: ?>
            <div class="results-table-wrap">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Username</th>
                            <th>Section</th>
                            <th>Research Group</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($student['full_name']) ?></strong><br>
                                    <small class="text-secondary"><?= htmlspecialchars($student['student_id'] ?: 'No student code') ?></small>
                                </td>
                                <td><?= htmlspecialchars($student['username']) ?></td>
                                <td><?= htmlspecialchars($student['section'] ?: '—') ?></td>
                                <td style="min-width:240px;">
                                    <form method="post" class="d-flex gap-2">
                                        <input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>">
                                        <select class="form-select form-select-sm" name="research_group">
                                            <option value="" <?= !$student['research_group'] ? 'selected' : '' ?>>Unassigned</option>
                                            <option value="experimental" <?= $student['research_group'] === 'experimental' ? 'selected' : '' ?>>Experimental</option>
                                            <option value="control" <?= $student['research_group'] === 'control' ? 'selected' : '' ?>>Control</option>
                                        </select>
                                        <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="score-chip <?= $student['is_active'] ? 'score-good' : 'score-low' ?>">
                                        <?= $student['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
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
