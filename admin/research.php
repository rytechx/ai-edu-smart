<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $update = $pdo->prepare("
        INSERT INTO research_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");

    if ($action === 'open_pretest') {
        $update->execute(['pretest_open', '1']);
        $update->execute(['posttest_open', '0']);
        $flash = 'Pretest opened. Posttest automatically closed.';
    } elseif ($action === 'close_pretest') {
        $update->execute(['pretest_open', '0']);
        $flash = 'Pretest closed.';
    } elseif ($action === 'open_posttest') {
        $update->execute(['pretest_open', '0']);
        $update->execute(['posttest_open', '1']);
        $flash = 'Posttest opened. Pretest automatically closed.';
    } elseif ($action === 'close_posttest') {
        $update->execute(['posttest_open', '0']);
        $flash = 'Posttest closed.';
    }
}

$settingsRows = $pdo->query("
    SELECT setting_key, setting_value
    FROM research_settings
    WHERE setting_key IN ('pretest_open','posttest_open')
")->fetchAll();

$settings = ['pretest_open' => '0', 'posttest_open' => '0'];
foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$questionCount = (int)$pdo->query("
    SELECT COUNT(*) FROM research_questions WHERE is_active = 1
")->fetchColumn();

$groupStats = $pdo->query("
    SELECT research_group, COUNT(*) AS total
    FROM users
    WHERE role = 'student' AND is_active = 1
    GROUP BY research_group
")->fetchAll();

$groups = ['experimental' => 0, 'control' => 0, 'unassigned' => 0];
foreach ($groupStats as $row) {
    $key = $row['research_group'] ?: 'unassigned';
    $groups[$key] = (int)$row['total'];
}

$preCount = (int)$pdo->query("
    SELECT COUNT(*) FROM research_attempts WHERE assessment_type = 'pretest'
")->fetchColumn();

$postCount = (int)$pdo->query("
    SELECT COUNT(*) FROM research_attempts WHERE assessment_type = 'posttest'
")->fetchColumn();

$rows = $pdo->query("
    SELECT
        u.id,
        u.student_id,
        u.full_name,
        u.section,
        u.research_group,
        pre.score AS pre_score,
        pre.total_items AS pre_total,
        pre.submitted_at AS pre_submitted,
        post.score AS post_score,
        post.total_items AS post_total,
        post.submitted_at AS post_submitted
    FROM users u
    LEFT JOIN research_attempts pre
      ON pre.user_id = u.id AND pre.assessment_type = 'pretest'
    LEFT JOIN research_attempts post
      ON post.user_id = u.id AND post.assessment_type = 'posttest'
    WHERE u.role = 'student' AND u.is_active = 1
    ORDER BY
      CASE u.research_group
        WHEN 'experimental' THEN 1
        WHEN 'control' THEN 2
        ELSE 3
      END,
      u.full_name ASC
")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Research Admin | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> Research Admin
        </a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="engagement.php">Engagement</a>
            <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Dashboard</a>
        </div>
    </div>
</nav>

<main class="container py-5">
    <section class="hero-card mb-4">
        <div>
            <span class="eyebrow">Formal Research</span>
            <h1 class="mt-2">Pretest / Posttest Control</h1>
            <p class="mb-0">
                Open one assessment window at a time and monitor submissions.
            </p>
        </div>
        <div class="progress-badge">
            <span>Items</span>
            <strong><?= $questionCount ?></strong>
        </div>
    </section>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="alert alert-warning">
        <strong>Validator check required:</strong>
        the achievement-test questions are taken from the thesis appendix, but the appendix
        does not contain an explicit answer key. Confirm the keyed answers with the ICT
        validators/adviser before using these scores in the study.
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <section class="research-control-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <span class="eyebrow">Before Intervention</span>
                        <h2>Pretest</h2>
                    </div>
                    <span class="assessment-status <?= $settings['pretest_open'] === '1' ? 'assessment-open' : 'assessment-closed' ?>">
                        <?= $settings['pretest_open'] === '1' ? 'OPEN' : 'CLOSED' ?>
                    </span>
                </div>

                <p><?= $preCount ?> submitted assessment(s)</p>

                <form method="post">
                    <?php if ($settings['pretest_open'] === '1'): ?>
                        <input type="hidden" name="action" value="close_pretest">
                        <button class="btn btn-outline-danger w-100" type="submit">Close Pretest</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="open_pretest">
                        <button class="btn btn-primary w-100" type="submit">Open Pretest</button>
                    <?php endif; ?>
                </form>
            </section>
        </div>

        <div class="col-md-6">
            <section class="research-control-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <span class="eyebrow">After Intervention</span>
                        <h2>Posttest</h2>
                    </div>
                    <span class="assessment-status <?= $settings['posttest_open'] === '1' ? 'assessment-open' : 'assessment-closed' ?>">
                        <?= $settings['posttest_open'] === '1' ? 'OPEN' : 'CLOSED' ?>
                    </span>
                </div>

                <p><?= $postCount ?> submitted assessment(s)</p>

                <form method="post">
                    <?php if ($settings['posttest_open'] === '1'): ?>
                        <input type="hidden" name="action" value="close_posttest">
                        <button class="btn btn-outline-danger w-100" type="submit">Close Posttest</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="open_posttest">
                        <button class="btn btn-primary w-100" type="submit">Open Posttest</button>
                    <?php endif; ?>
                </form>
            </section>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-md-4"><div class="mini-activity-card"><strong><?= $groups['experimental'] ?></strong><span>Experimental students</span></div></div>
        <div class="col-md-4"><div class="mini-activity-card"><strong><?= $groups['control'] ?></strong><span>Control students</span></div></div>
        <div class="col-md-4"><div class="mini-activity-card"><strong><?= $groups['unassigned'] ?></strong><span>Unassigned students</span></div></div>
    </div>

    <section class="content-card mt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <span class="eyebrow">Research Data</span>
                <h2 class="h4 mt-2 mb-0">Achievement Test Results</h2>
            </div>
            <a class="btn btn-outline-primary" href="research-export.php">Export CSV</a>
        </div>

        <div class="results-table-wrap">
            <table class="table align-middle mb-0 report-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Group</th>
                        <th>Pretest</th>
                        <th>Posttest</th>
                        <th>Gain</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $gain = null;
                        if ($row['pre_score'] !== null && $row['post_score'] !== null) {
                            $gain = (int)$row['post_score'] - (int)$row['pre_score'];
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                                <small class="text-secondary">
                                    <?= htmlspecialchars($row['student_id'] ?: 'No student code') ?>
                                    · <?= htmlspecialchars($row['section'] ?: 'No section') ?>
                                </small>
                            </td>
                            <td>
                                <span class="research-group-chip research-group-<?= htmlspecialchars($row['research_group'] ?: 'unassigned') ?>">
                                    <?= htmlspecialchars($row['research_group'] ? ucfirst($row['research_group']) : 'Unassigned') ?>
                                </span>
                            </td>
                            <td>
                                <?= $row['pre_score'] !== null
                                    ? (int)$row['pre_score'] . ' / ' . (int)$row['pre_total']
                                    : '—' ?>
                            </td>
                            <td>
                                <?= $row['post_score'] !== null
                                    ? (int)$row['post_score'] . ' / ' . (int)$row['post_total']
                                    : '—' ?>
                            </td>
                            <td>
                                <?= $gain !== null ? (($gain >= 0 ? '+' : '') . $gain) : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
