<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'];
$userId = (int)$user['id'];
$group = $user['research_group'] ?? null;

$settingsRows = $pdo->query("
    SELECT setting_key, setting_value
    FROM research_settings
    WHERE setting_key IN (
        'pretest_open',
        'posttest_open',
        'pre_engagement_open',
        'post_engagement_open'
    )
")->fetchAll();

$settings = [
    'pretest_open' => '0',
    'posttest_open' => '0',
    'pre_engagement_open' => '0',
    'post_engagement_open' => '0'
];

foreach ($settingsRows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$stmt = $pdo->prepare("
    SELECT assessment_type, submitted_at
    FROM research_attempts
    WHERE user_id = ?
");
$stmt->execute([$userId]);

$achievementDone = [];
foreach ($stmt->fetchAll() as $row) {
    $achievementDone[$row['assessment_type']] = $row['submitted_at'];
}

$stmt = $pdo->prepare("
    SELECT assessment_type, submitted_at
    FROM engagement_attempts
    WHERE user_id = ?
");
$stmt->execute([$userId]);

$engagementDone = [];
foreach ($stmt->fetchAll() as $row) {
    $engagementDone[$row['assessment_type']] = $row['submitted_at'];
}

$accessRestricted = ($_GET['access'] ?? '') === 'learning-restricted';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Research Assessment | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart
        </a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Dashboard</a>
            <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Logout</a>
        </div>
    </div>
</nav>

<main class="container py-5">
    <?php if ($accessRestricted): ?>
        <div class="alert alert-warning">
            Learning-intervention modules are available only to students assigned to the experimental group.
        </div>
    <?php endif; ?>

    <section class="hero-card mb-4">
        <div>
            <span class="eyebrow">Formal Research</span>
            <h1 class="mt-2">Research Assessments</h1>
            <p class="mb-0">
                Complete only the assessments opened by the researchers or ICT teacher.
            </p>
        </div>

        <div class="progress-badge">
            <span>Group</span>
            <strong><?= htmlspecialchars($group ? ucfirst($group) : 'Unassigned') ?></strong>
        </div>
    </section>

    <?php if (!$group): ?>
        <div class="alert alert-danger">
            Your research group has not been assigned yet. Please contact the administrator.
        </div>
    <?php endif; ?>

    <div class="research-rule-card mb-4">
        <strong>Research rules</strong>
        <span>One submission only for each research instrument.</span>
        <span>No scores or answer feedback are shown to students.</span>
        <span>Complete each instrument only when instructed.</span>
    </div>

    <section class="content-card mb-4">
        <span class="eyebrow">Academic Performance</span>
        <h2 class="h4 mt-2 mb-4">Computer Hardware Fundamentals Achievement Test</h2>

        <div class="row g-4">
            <?php
            $achievementCards = [
                'pretest' => [
                    'title' => 'Pretest',
                    'subtitle' => 'Before the intervention',
                    'open' => $settings['pretest_open'] === '1'
                ],
                'posttest' => [
                    'title' => 'Posttest',
                    'subtitle' => 'After the intervention',
                    'open' => $settings['posttest_open'] === '1'
                ]
            ];
            ?>

            <?php foreach ($achievementCards as $type => $card): ?>
                <?php $done = isset($achievementDone[$type]); ?>
                <div class="col-md-6">
                    <section class="research-assessment-card">
                        <div class="research-assessment-head">
                            <div class="research-icon"><?= $type === 'pretest' ? '01' : '02' ?></div>
                            <div>
                                <span class="eyebrow"><?= htmlspecialchars($card['subtitle']) ?></span>
                                <h2><?= htmlspecialchars($card['title']) ?></h2>
                            </div>
                        </div>

                        <p>15-item Computer Hardware Fundamentals Achievement Test.</p>

                        <?php if ($done): ?>
                            <div class="assessment-status assessment-done">
                                ✓ Submitted <?= htmlspecialchars(date('M d, Y h:i A', strtotime($achievementDone[$type]))) ?>
                            </div>
                            <button class="btn btn-outline-secondary w-100" disabled>Already Submitted</button>
                        <?php elseif (!$group): ?>
                            <div class="assessment-status assessment-closed">Group assignment required</div>
                            <button class="btn btn-outline-secondary w-100" disabled>Unavailable</button>
                        <?php elseif (!$card['open']): ?>
                            <div class="assessment-status assessment-closed">Currently closed</div>
                            <button class="btn btn-outline-secondary w-100" disabled>Assessment Closed</button>
                        <?php else: ?>
                            <div class="assessment-status assessment-open">Open for submission</div>
                            <a class="btn btn-primary w-100"
                               href="research-test.php?type=<?= urlencode($type) ?>">
                                Start <?= htmlspecialchars($card['title']) ?>
                            </a>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="content-card">
        <span class="eyebrow">Student Engagement</span>
        <h2 class="h4 mt-2 mb-2">Neutral 15-Item Engagement Questionnaire</h2>
        <p class="text-secondary mb-4">
            The same questionnaire is administered before and after the intervention to both groups.
        </p>

        <div class="row g-4">
            <?php
            $engagementCards = [
                'pre' => [
                    'title' => 'Pre-Engagement',
                    'subtitle' => 'Before the intervention',
                    'open' => $settings['pre_engagement_open'] === '1'
                ],
                'post' => [
                    'title' => 'Post-Engagement',
                    'subtitle' => 'After the intervention',
                    'open' => $settings['post_engagement_open'] === '1'
                ]
            ];
            ?>

            <?php foreach ($engagementCards as $type => $card): ?>
                <?php $done = isset($engagementDone[$type]); ?>
                <div class="col-md-6">
                    <section class="research-assessment-card">
                        <div class="research-assessment-head">
                            <div class="research-icon"><?= $type === 'pre' ? 'E1' : 'E2' ?></div>
                            <div>
                                <span class="eyebrow"><?= htmlspecialchars($card['subtitle']) ?></span>
                                <h2><?= htmlspecialchars($card['title']) ?></h2>
                            </div>
                        </div>

                        <p>
                            Behavioral, Cognitive, and Emotional Engagement using the
                            4-point Likert scale.
                        </p>

                        <?php if ($done): ?>
                            <div class="assessment-status assessment-done">
                                ✓ Submitted <?= htmlspecialchars(date('M d, Y h:i A', strtotime($engagementDone[$type]))) ?>
                            </div>
                            <button class="btn btn-outline-secondary w-100" disabled>Already Submitted</button>
                        <?php elseif (!$group): ?>
                            <div class="assessment-status assessment-closed">Group assignment required</div>
                            <button class="btn btn-outline-secondary w-100" disabled>Unavailable</button>
                        <?php elseif (!$card['open']): ?>
                            <div class="assessment-status assessment-closed">Currently closed</div>
                            <button class="btn btn-outline-secondary w-100" disabled>Questionnaire Closed</button>
                        <?php else: ?>
                            <div class="assessment-status assessment-open">Open for submission</div>
                            <a class="btn btn-primary w-100"
                               href="engagement.php?type=<?= urlencode($type) ?>">
                                Start <?= htmlspecialchars($card['title']) ?>
                            </a>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>
