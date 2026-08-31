<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('student');
requireExperimental();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai.php';

$user = $_SESSION['user'];
$liveMode = trim((string)GEMINI_API_KEY) !== '';

$stmt = $pdo->prepare("
    SELECT user_message, ai_response, created_at
    FROM ai_chat_logs
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 12
");
$stmt->execute([$user['id']]);
$history = array_reverse($stmt->fetchAll());
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Hardware Tutor | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
<nav class="navbar app-navbar">
    <div class="container">
        <a class="navbar-brand brand-inline" href="dashboard.php">
            <span class="mini-mark">AI</span> AI Edu Smart
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="ai-mode-chip <?= $liveMode ? 'ai-mode-live' : 'ai-mode-demo' ?>">
                <?= $liveMode ? 'Gemini Live' : 'Local Demo' ?>
            </span>
            <a class="btn btn-outline-secondary btn-sm" href="dashboard.php">Dashboard</a>
        </div>
    </div>
</nav>

<main class="container py-5">
    <section class="hero-card mb-4">
        <div>
            <span class="eyebrow">Guided Learning</span>
            <h1 class="mt-2">AI Hardware Tutor</h1>
            <p class="mb-0">Ask questions about Computer Hardware Fundamentals only.</p>
        </div>
        <div class="tutor-orb">🤖</div>
    </section>

    <section class="ai-chat-shell">
        <div class="ai-chat-head">
            <div>
                <strong>Hardware Tutor</strong>
                <small>
                    <?= $liveMode
                        ? 'Connected to Google Gemini'
                        : 'Running locally with limited built-in answers' ?>
                </small>
            </div>
            <button class="btn btn-sm btn-outline-secondary" id="clearVisual" type="button">Clear Screen</button>
        </div>

        <div class="ai-chat-messages" id="chatMessages">
            <div class="chat-row tutor-row">
                <div class="chat-avatar">AI</div>
                <div class="chat-bubble tutor-bubble">
                    Hi! Ask me about CPUs, motherboards, RAM, storage, GPUs, power supplies,
                    peripherals, cooling, or basic computer troubleshooting.
                </div>
            </div>

            <?php foreach ($history as $chat): ?>
                <div class="chat-row student-row">
                    <div class="chat-bubble student-bubble"><?= nl2br(htmlspecialchars($chat['user_message'])) ?></div>
                </div>
                <div class="chat-row tutor-row">
                    <div class="chat-avatar">AI</div>
                    <div class="chat-bubble tutor-bubble"><?= nl2br(htmlspecialchars($chat['ai_response'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="quick-prompts">
            <button type="button" data-prompt="What is the function of RAM?">What is RAM?</button>
            <button type="button" data-prompt="What is the difference between RAM and SSD?">RAM vs SSD</button>
            <button type="button" data-prompt="What does a motherboard do?">Motherboard</button>
            <button type="button" data-prompt="Why does a computer need a cooling fan?">Cooling</button>
        </div>

        <form class="ai-chat-form" id="chatForm">
            <textarea id="messageInput"
                      name="message"
                      rows="2"
                      maxlength="1000"
                      placeholder="Ask a Computer Hardware Fundamentals question..."
                      required></textarea>
            <button class="btn btn-primary" id="sendButton" type="submit">Send</button>
        </form>

        <div class="ai-chat-note">
            Gemini responses may be imperfect. Use the approved lessons and teacher guidance as the main learning reference.
        </div>
    </section>
</main>

<script>
const form = document.getElementById('chatForm');
const input = document.getElementById('messageInput');
const messages = document.getElementById('chatMessages');
const sendButton = document.getElementById('sendButton');

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function addStudentMessage(text) {
    messages.insertAdjacentHTML('beforeend', `
        <div class="chat-row student-row">
            <div class="chat-bubble student-bubble">${escapeHtml(text)}</div>
        </div>
    `);
}

function addTutorMessage(text, error = false) {
    messages.insertAdjacentHTML('beforeend', `
        <div class="chat-row tutor-row">
            <div class="chat-avatar">AI</div>
            <div class="chat-bubble tutor-bubble ${error ? 'chat-error' : ''}">
                ${escapeHtml(text).replace(/\n/g, '<br>')}
            </div>
        </div>
    `);
}

function scrollToBottom() {
    messages.scrollTop = messages.scrollHeight;
}

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const text = input.value.trim();
    if (!text) return;

    addStudentMessage(text);
    input.value = '';
    sendButton.disabled = true;
    sendButton.textContent = 'Thinking...';

    const loadingId = 'loading-' + Date.now();
    messages.insertAdjacentHTML('beforeend', `
        <div class="chat-row tutor-row" id="${loadingId}">
            <div class="chat-avatar">AI</div>
            <div class="chat-bubble tutor-bubble">
                <span class="thinking-dots"><i></i><i></i><i></i></span>
            </div>
        </div>
    `);
    scrollToBottom();

    try {
        const body = new FormData();
        body.append('message', text);

        const response = await fetch('../api/ai-chat.php', {
            method: 'POST',
            body
        });

        const data = await response.json();
        document.getElementById(loadingId)?.remove();

        if (!response.ok || !data.ok) {
            addTutorMessage(data.error || 'Something went wrong. Please try again.', true);
        } else {
            addTutorMessage(data.reply);
        }
    } catch (error) {
        document.getElementById(loadingId)?.remove();
        addTutorMessage('Unable to contact the tutor. Check Apache, MySQL, and your connection.', true);
    } finally {
        sendButton.disabled = false;
        sendButton.textContent = 'Send';
        input.focus();
        scrollToBottom();
    }
});

document.querySelectorAll('[data-prompt]').forEach(button => {
    button.addEventListener('click', () => {
        input.value = button.dataset.prompt;
        input.focus();
    });
});

document.getElementById('clearVisual').addEventListener('click', () => {
    messages.innerHTML = `
        <div class="chat-row tutor-row">
            <div class="chat-avatar">AI</div>
            <div class="chat-bubble tutor-bubble">
                Screen cleared. Ask another Computer Hardware Fundamentals question.
            </div>
        </div>
    `;
});

scrollToBottom();
</script>
</body>
</html>
