<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ai_chat_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            user_message TEXT NOT NULL,
            ai_response TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_ai_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $message = 'Phase 5 database check completed. AI Tutor chat logging is ready.';
} catch (Throwable $e) {
    $message = 'Update failed: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phase 5 Update | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <section class="content-card mx-auto mt-5" style="max-width:780px;">
        <span class="eyebrow">Phase 5</span>
        <h1 class="mt-2">AI Tutor Update</h1>
        <div class="alert alert-info mt-4"><?= htmlspecialchars($message) ?></div>

        <p class="text-secondary">
            The tutor works immediately in Local Demo Mode. Add your API key later in
            <code>config/ai.php</code> to enable live AI responses.
        </p>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="student/ai-tutor.php">Open AI Tutor</a>
            <a class="btn btn-outline-primary" href="admin/ai-settings.php">AI Status</a>
            <a class="btn btn-outline-secondary" href="index.php">Home</a>
        </div>
    </section>
</main>
</body>
</html>
