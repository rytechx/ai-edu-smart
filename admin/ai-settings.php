<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai.php';

$liveMode = trim((string)GEMINI_API_KEY) !== '';

$totalChats = (int)$pdo->query("SELECT COUNT(*) FROM ai_chat_logs")->fetchColumn();
$todayChats = (int)$pdo->query("
    SELECT COUNT(*) FROM ai_chat_logs WHERE DATE(created_at) = CURDATE()
")->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gemini AI Settings | AI Edu Smart</title>
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
            <span class="eyebrow">Gemini AI Tutor</span>
            <h1 class="mt-2">AI Status & Configuration</h1>
            <p class="mb-0">The Gemini API key stays on your PHP server and is never sent to student JavaScript.</p>
        </div>
        <div class="progress-badge">
            <span>Mode</span>
            <strong><?= $liveMode ? 'LIVE' : 'DEMO' ?></strong>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card">
                <span>✨</span>
                <strong><?= htmlspecialchars(GEMINI_MODEL) ?></strong>
                <small>Gemini Model</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <span>💬</span>
                <strong><?= $todayChats ?></strong>
                <small>Questions Today</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <span>📚</span>
                <strong><?= $totalChats ?></strong>
                <small>Total Tutor Questions</small>
            </div>
        </div>
    </div>

    <section class="content-card mt-4">
        <span class="eyebrow">Configuration</span>
        <h2 class="h4 mt-2">Current Gemini mode</h2>

        <?php if ($liveMode): ?>
            <div class="alert alert-success mt-3">
                Gemini Live Mode is enabled. A Gemini API key is configured on the PHP server.
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-3">
                Local Demo Mode is active. Add a Gemini API key to enable live generated answers.
            </div>
        <?php endif; ?>

        <div class="config-code-card">
            <strong>Enable Gemini:</strong>
            <ol class="mb-0 mt-2">
                <li>Create a Gemini API key in Google AI Studio.</li>
                <li>Open <code>C:\xampp\htdocs\ai-edu-smart\config\ai.php</code></li>
                <li>Paste it into <code>GEMINI_API_KEY</code>.</li>
                <li>Save the file and refresh this page.</li>
            </ol>
        </div>

        <div class="alert alert-danger mt-3 mb-0">
            Never place your Gemini API key in HTML, JavaScript, screenshots, GitHub, or a public repository.
        </div>
    </section>
</main>
</body>
</html>
