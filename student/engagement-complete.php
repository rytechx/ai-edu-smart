<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');

$type = $_GET['type'] ?? '';
$label = $type === 'pre'
    ? 'Pre-Engagement Questionnaire'
    : ($type === 'post' ? 'Post-Engagement Questionnaire' : 'Questionnaire');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Questionnaire Submitted | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <section class="research-complete-card mx-auto mt-5">
        <div class="research-complete-icon">✓</div>
        <span class="eyebrow">Submission Received</span>
        <h1><?= htmlspecialchars($label) ?> Complete</h1>
        <p>
            Your responses were recorded successfully. Individual engagement scores are not
            displayed to students during the research process.
        </p>
        <a class="btn btn-primary" href="research.php">Return to Research Assessment</a>
    </section>
</main>
</body>
</html>
