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

    if ($action === 'open_pre') {
        $update->execute(['pre_engagement_open', '1']);
        $update->execute(['post_engagement_open', '0']);
        $flash = 'Pre-Engagement questionnaire opened. Post-Engagement automatically closed.';
    } elseif ($action === 'close_pre') {
        $update->execute(['pre_engagement_open', '0']);
        $flash = 'Pre-Engagement questionnaire closed.';
    } elseif ($action === 'open_post') {
        $update->execute(['pre_engagement_open', '0']);
        $update->execute(['post_engagement_open', '1']);
        $flash = 'Post-Engagement questionnaire opened. Pre-Engagement automatically closed.';
    } elseif ($action === 'close_post') {
        $update->execute(['post_engagement_open', '0']);
        $flash = 'Post-Engagement questionnaire closed.';
    }
}

$settingsRows = $pdo->query("
    SELECT setting_key, setting_value
    FROM research_settings
    WHERE setting_key IN ('pre_engagement_open','post_engagement_open')
")->fetchAll();

$settings = ['pre_engagement_open' => '0', 'post_engagement_open' => '0'];

foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$preCount = (int)$pdo->query("
    SELECT COUNT(*) FROM engagement_attempts WHERE assessment_type = 'pre'
")->fetchColumn();

$postCount = (int)$pdo->query("
    SELECT COUNT(*) FROM engagement_attempts WHERE assessment_type = 'post'
")->fetchColumn();

$summaryRows = $pdo->query("
    SELECT
        u.research_group,
        ea.assessment_type,
        COUNT(*) AS n,
        AVG(ea.behavioral_mean) AS behavioral_mean,
        AVG(ea.cognitive_mean) AS cognitive_mean,
        AVG(ea.emotional_mean) AS emotional_mean,
        AVG(ea.overall_mean) AS overall_mean
    FROM engagement_attempts ea
    JOIN users u ON u.id = ea.user_id
    WHERE u.research_group IN ('experimental','control')
    GROUP BY u.research_group, ea.assessment_type
")->fetchAll();

$summary = [
    'experimental' => ['pre' => null, 'post' => null],
    'control' => ['pre' => null, 'post' => null]
];

foreach ($summaryRows as $row) {
    $summary[$row['research_group']][$row['assessment_type']] = $row;
}

$individualRows = $pdo->query("
    SELECT
        u.id,
        u.student_id,
        u.full_name,
        u.section,
        u.research_group,
        pre.behavioral_mean AS pre_behavioral,
        pre.cognitive_mean AS pre_cognitive,
        pre.emotional_mean AS pre_emotional,
        pre.overall_mean AS pre_overall,
        post.behavioral_mean AS post_behavioral,
        post.cognitive_mean AS post_cognitive,
        post.emotional_mean AS post_emotional,
        post.overall_mean AS post_overall
    FROM users u
    LEFT JOIN engagement_attempts pre
      ON pre.user_id = u.id AND pre.assessment_type = 'pre'
    LEFT JOIN engagement_attempts post
      ON post.user_id = u.id AND post.assessment_type = 'post'
    WHERE u.role = 'student' AND u.is_active = 1
    ORDER BY
      CASE u.research_group
        WHEN 'experimental' THEN 1
        WHEN 'control' THEN 2
        ELSE 3
      END,
      u.full_name ASC
")->fetchAll();

function interpretation(?float $mean): string {
    if ($mean === null) return '—';
    if ($mean >= 3.25) return 'Strongly Agree';
    if ($mean >= 2.50) return 'Agree';
    if ($mean >= 1.75) return 'Disagree';
    return 'Strongly Disagree';
}

function fmt(?string $value): string {
    return $value === null ? '—' : number_format((float)$value, 2);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Engagement Research | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> Engagement Research
        </a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="research.php">Achievement</a>
            <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Dashboard</a>
        </div>
    </div>
</nav>

<main class="container py-5">
    <section class="hero-card mb-4">
        <div>
            <span class="eyebrow">Student Engagement</span>
            <h1 class="mt-2">Pre / Post Engagement Control</h1>
            <p class="mb-0">
                Behavioral, Cognitive, Emotional, and Overall Engagement.
            </p>
        </div>
        <div class="progress-badge">
            <span>Scale</span>
            <strong>1–4</strong>
        </div>
    </section>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <section class="research-control-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <span class="eyebrow">Before Intervention</span>
                        <h2>Pre-Engagement</h2>
                    </div>
                    <span class="assessment-status <?= $settings['pre_engagement_open'] === '1' ? 'assessment-open' : 'assessment-closed' ?>">
                        <?= $settings['pre_engagement_open'] === '1' ? 'OPEN' : 'CLOSED' ?>
                    </span>
                </div>

                <p><?= $preCount ?> submitted questionnaire(s)</p>

                <form method="post">
                    <?php if ($settings['pre_engagement_open'] === '1'): ?>
                        <input type="hidden" name="action" value="close_pre">
                        <button class="btn btn-outline-danger w-100" type="submit">Close Pre-Engagement</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="open_pre">
                        <button class="btn btn-primary w-100" type="submit">Open Pre-Engagement</button>
                    <?php endif; ?>
                </form>
            </section>
        </div>

        <div class="col-md-6">
            <section class="research-control-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <span class="eyebrow">After Intervention</span>
                        <h2>Post-Engagement</h2>
                    </div>
                    <span class="assessment-status <?= $settings['post_engagement_open'] === '1' ? 'assessment-open' : 'assessment-closed' ?>">
                        <?= $settings['post_engagement_open'] === '1' ? 'OPEN' : 'CLOSED' ?>
                    </span>
                </div>

                <p><?= $postCount ?> submitted questionnaire(s)</p>

                <form method="post">
                    <?php if ($settings['post_engagement_open'] === '1'): ?>
                        <input type="hidden" name="action" value="close_post">
                        <button class="btn btn-outline-danger w-100" type="submit">Close Post-Engagement</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="open_post">
                        <button class="btn btn-primary w-100" type="submit">Open Post-Engagement</button>
                    <?php endif; ?>
                </form>
            </section>
        </div>
    </div>

    <section class="content-card mt-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <span class="eyebrow">Group Comparison</span>
                <h2 class="h4 mt-2 mb-0">Mean Engagement Scores</h2>
            </div>
            <a class="btn btn-outline-primary" href="engagement-export.php">Export CSV</a>
        </div>

        <div class="results-table-wrap">
            <table class="table align-middle mb-0 report-table">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Time</th>
                        <th>N</th>
                        <th>Behavioral</th>
                        <th>Cognitive</th>
                        <th>Emotional</th>
                        <th>Overall</th>
                        <th>Interpretation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (['experimental','control'] as $group): ?>
                        <?php foreach (['pre','post'] as $time): ?>
                            <?php $row = $summary[$group][$time]; ?>
                            <tr>
                                <td>
                                    <span class="research-group-chip research-group-<?= $group ?>">
                                        <?= ucfirst($group) ?>
                                    </span>
                                </td>
                                <td><?= ucfirst($time) ?></td>
                                <td><?= $row ? (int)$row['n'] : 0 ?></td>
                                <td><?= $row ? fmt($row['behavioral_mean']) : '—' ?></td>
                                <td><?= $row ? fmt($row['cognitive_mean']) : '—' ?></td>
                                <td><?= $row ? fmt($row['emotional_mean']) : '—' ?></td>
                                <td><strong><?= $row ? fmt($row['overall_mean']) : '—' ?></strong></td>
                                <td><?= $row ? interpretation((float)$row['overall_mean']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="report-note mt-3">
            Interpretation: 3.25–4.00 Strongly Agree; 2.50–3.24 Agree;
            1.75–2.49 Disagree; 1.00–1.74 Strongly Disagree.
        </div>
    </section>

    <section class="content-card mt-4">
        <span class="eyebrow">Individual Comparison</span>
        <h2 class="h4 mt-2 mb-3">Pre / Post Overall Means</h2>

        <div class="results-table-wrap">
            <table class="table align-middle mb-0 report-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Group</th>
                        <th>Pre Behavioral</th>
                        <th>Pre Cognitive</th>
                        <th>Pre Emotional</th>
                        <th>Pre Overall</th>
                        <th>Post Behavioral</th>
                        <th>Post Cognitive</th>
                        <th>Post Emotional</th>
                        <th>Post Overall</th>
                        <th>Overall Change</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($individualRows as $row): ?>
                        <?php
                        $change = null;
                        if ($row['pre_overall'] !== null && $row['post_overall'] !== null) {
                            $change = (float)$row['post_overall'] - (float)$row['pre_overall'];
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                                <small class="text-secondary">
                                    <?= htmlspecialchars($row['student_id'] ?: 'No student code') ?>
                                </small>
                            </td>
                            <td>
                                <span class="research-group-chip research-group-<?= htmlspecialchars($row['research_group'] ?: 'unassigned') ?>">
                                    <?= htmlspecialchars($row['research_group'] ? ucfirst($row['research_group']) : 'Unassigned') ?>
                                </span>
                            </td>
                            <td><?= fmt($row['pre_behavioral']) ?></td>
                            <td><?= fmt($row['pre_cognitive']) ?></td>
                            <td><?= fmt($row['pre_emotional']) ?></td>
                            <td><strong><?= fmt($row['pre_overall']) ?></strong></td>
                            <td><?= fmt($row['post_behavioral']) ?></td>
                            <td><?= fmt($row['post_cognitive']) ?></td>
                            <td><?= fmt($row['post_emotional']) ?></td>
                            <td><strong><?= fmt($row['post_overall']) ?></strong></td>
                            <td>
                                <?= $change === null
                                    ? '—'
                                    : (($change >= 0 ? '+' : '') . number_format($change, 2)) ?>
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
