<?php
require_once __DIR__ . '/config/ai.php';

$result = null;
$error = null;
$interactionId = null;

if (trim((string)GEMINI_API_KEY) === '') {
    $error = 'No Gemini API key is configured. Open config/ai.php and add GEMINI_API_KEY.';
} else {
    $url = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    $payload = [
        'model' => GEMINI_MODEL,
        'input' => 'Reply with exactly this sentence: Gemini Interactions API connection successful.',
        'generation_config' => [
            'max_output_tokens' => 60,
            'thinking_level' => 'minimal'
        ],
        'store' => false
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-key: ' . GEMINI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
    ]);

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false || $curlError) {
        $error = 'Connection error: ' . $curlError;
    } else {
        $data = json_decode($raw, true);

        if ($status >= 200 && $status < 300) {
            $interactionId = $data['id'] ?? null;
            $text = '';

            foreach (($data['steps'] ?? []) as $step) {
                if (($step['type'] ?? '') !== 'model_output') {
                    continue;
                }

                foreach (($step['content'] ?? []) as $content) {
                    if (($content['type'] ?? '') === 'text' && isset($content['text'])) {
                        $text .= $content['text'];
                    }
                }
            }

            if ($text === '' && isset($data['output_text'])) {
                $text = $data['output_text'];
            }

            $result = trim($text) ?: 'Gemini responded successfully.';
        } else {
            $error = $data['error']['message'] ?? ('HTTP error ' . $status);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gemini Interactions API Test | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <section class="content-card mx-auto mt-5" style="max-width:780px;">
        <span class="eyebrow">Connection Test</span>
        <h1 class="mt-2">Gemini Interactions API Test</h1>

        <p class="text-secondary mb-1">
            Model: <code><?= htmlspecialchars(GEMINI_MODEL) ?></code>
        </p>
        <p class="text-secondary">
            Endpoint: <code>/v1beta/interactions</code>
        </p>

        <?php if ($result): ?>
            <div class="alert alert-success">
                <strong>PASS</strong><br>
                <?= htmlspecialchars($result) ?>
                <?php if ($interactionId): ?>
                    <hr>
                    <small>Interaction ID: <?= htmlspecialchars($interactionId) ?></small>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <strong>FAILED</strong><br>
                <?= htmlspecialchars($error ?? 'Unknown error') ?>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="gemini-test.php">Test Again</a>
            <a class="btn btn-outline-primary" href="student/ai-tutor.php">Open AI Tutor</a>
            <a class="btn btn-outline-secondary" href="admin/ai-settings.php">AI Status</a>
        </div>
    </section>
</main>
</body>
</html>
