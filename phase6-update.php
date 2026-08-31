<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS lesson_progress (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            lesson_id INT UNSIGNED NOT NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_lesson (user_id, lesson_id),
            CONSTRAINT fk_lp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_lp_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
        )
    ");

    $message = 'Phase 6 update completed. Progress tracking and reporting are ready.';
} catch (Throwable $e) {
    $message = 'Update failed: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phase 6 Update | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <section class="content-card mx-auto mt-5" style="max-width:800px;">
        <span class="eyebrow">Phase 6</span>
        <h1 class="mt-2">Final Localhost Polish</h1>
        <div class="alert alert-info mt-4"><?= htmlspecialchars($message) ?></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="student/dashboard.php">Student Dashboard</a>
            <a class="btn btn-outline-primary" href="admin/dashboard.php">Admin Dashboard</a>
            <a class="btn btn-outline-secondary" href="admin/results.php">Admin Reports</a>
        </div>
    </section>
</main>
</body>
</html>
