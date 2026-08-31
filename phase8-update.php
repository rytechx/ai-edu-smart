<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS engagement_questions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_number INT NOT NULL UNIQUE,
            dimension ENUM('behavioral','cognitive','emotional') NOT NULL,
            statement TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS engagement_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            assessment_type ENUM('pre','post') NOT NULL,
            behavioral_mean DECIMAL(4,2) NOT NULL,
            cognitive_mean DECIMAL(4,2) NOT NULL,
            emotional_mean DECIMAL(4,2) NOT NULL,
            overall_mean DECIMAL(4,2) NOT NULL,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_engagement_user_type (user_id, assessment_type),
            CONSTRAINT fk_ea_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS engagement_answers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attempt_id BIGINT UNSIGNED NOT NULL,
            question_id INT UNSIGNED NOT NULL,
            response_value TINYINT UNSIGNED NOT NULL,
            CONSTRAINT fk_eans_attempt FOREIGN KEY (attempt_id) REFERENCES engagement_attempts(id) ON DELETE CASCADE,
            CONSTRAINT fk_eans_question FOREIGN KEY (question_id) REFERENCES engagement_questions(id) ON DELETE CASCADE,
            CONSTRAINT chk_response_value CHECK (response_value BETWEEN 1 AND 4)
        )
    ");

    $setting = $pdo->prepare("
        INSERT INTO research_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = setting_value
    ");
    $setting->execute(['pre_engagement_open', '0']);
    $setting->execute(['post_engagement_open', '0']);

    $questions = [
        [1, 'behavioral', 'I actively participate in Computer Hardware Fundamentals activities.'],
        [2, 'behavioral', 'I stay focused during Computer Hardware Fundamentals lessons.'],
        [3, 'behavioral', 'I complete the assigned Computer Hardware Fundamentals learning activities.'],
        [4, 'behavioral', 'I continue working even when Computer Hardware Fundamentals activities are difficult.'],
        [5, 'behavioral', 'I pay attention during Computer Hardware Fundamentals lessons and activities.'],

        [6, 'cognitive', 'I make an effort to understand difficult computer hardware concepts.'],
        [7, 'cognitive', 'I carefully think about my answers during Computer Hardware Fundamentals learning activities.'],
        [8, 'cognitive', 'I use what I learn in Computer Hardware Fundamentals to solve problems and activities.'],
        [9, 'cognitive', 'I connect new computer hardware concepts with what I already know.'],
        [10, 'cognitive', 'I check my understanding when I encounter difficult computer hardware concepts.'],

        [11, 'emotional', 'I am interested in learning about computer hardware.'],
        [12, 'emotional', 'I enjoy participating in Computer Hardware Fundamentals learning activities.'],
        [13, 'emotional', 'I feel confident while learning Computer Hardware Fundamentals.'],
        [14, 'emotional', 'I feel motivated to continue learning about computer hardware.'],
        [15, 'emotional', 'I am willing to participate in Computer Hardware Fundamentals learning activities.'],
    ];

    $check = $pdo->prepare("SELECT id FROM engagement_questions WHERE item_number = ? LIMIT 1");
    $insert = $pdo->prepare("
        INSERT INTO engagement_questions (item_number, dimension, statement)
        VALUES (?, ?, ?)
    ");

    $added = 0;
    foreach ($questions as $q) {
        $check->execute([$q[0]]);
        if (!$check->fetch()) {
            $insert->execute($q);
            $added++;
        }
    }

    $message = "Phase 8 engagement module is ready. Questionnaire items added: {$added}.";
} catch (Throwable $e) {
    $message = 'Update failed: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phase 8 Update | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <section class="content-card mx-auto mt-5" style="max-width:860px;">
        <span class="eyebrow">Phase 8</span>
        <h1 class="mt-2">Student Engagement Questionnaire</h1>

        <div class="alert alert-info mt-4">
            <?= htmlspecialchars($message) ?>
        </div>

        <div class="alert alert-success">
            The same neutral 15-item questionnaire is used for both pre-engagement and
            post-engagement administration for Experimental and Control groups.
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="admin/engagement.php">Engagement Admin</a>
            <a class="btn btn-outline-primary" href="student/research.php">Student Research</a>
            <a class="btn btn-outline-secondary" href="admin/research.php">Achievement Research</a>
        </div>
    </section>
</main>
</body>
</html>
