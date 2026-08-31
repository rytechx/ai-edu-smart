<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');

$type = $_GET['type'] ?? '';
if (!in_array($type, ['pretest','posttest'], true)) {
    $type = 'assessment';
}
$label = $type === 'pretest' ? 'Pretest' : ($type === 'posttest' ? 'Posttest' : 'Assessment');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assessment Submitted | AI Edu Smart</title>
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
            Your responses were recorded successfully. Scores and answer feedback are not
            displayed to students during the formal research assessment.
        </p>
        <a class="btn btn-primary" href="research.php">Return to Research Assessment</a>
    </section>
</main>
</body>
</html>
