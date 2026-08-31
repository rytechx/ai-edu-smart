<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/auth.php';
requireRole('student');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/ai.php';

$user = $_SESSION['user'];
$message = trim($_POST['message'] ?? '');

function respondJson(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(['ok' => false, 'error' => 'POST request required.'], 405);
}

if ($message === '') {
    respondJson(['ok' => false, 'error' => 'Please enter a question.'], 422);
}

if (mb_strlen($message) > 1000) {
    respondJson(['ok' => false, 'error' => 'Please keep your question under 1,000 characters.'], 422);
}

/*
|--------------------------------------------------------------------------
| Topic restriction
|--------------------------------------------------------------------------
*/
$topicWords = [
    'computer','hardware','cpu','processor','motherboard','mainboard','ram',
    'memory','rom','ssd','hdd','storage','gpu','graphics','video card',
    'power supply','psu','keyboard','mouse','monitor','printer','scanner',
    'speaker','input','output','peripheral','fan','cooling','heatsink',
    'bios','uefi','sata','nvme','m.2','pcie','pci','usb','port','cable',
    'troubleshoot','troubleshooting','boot','component','device','computer part',
    'upgrade','install','drive','cache'
];

$lower = mb_strtolower($message);
$isHardwareTopic = false;

foreach ($topicWords as $word) {
    if (mb_strpos($lower, $word) !== false) {
        $isHardwareTopic = true;
        break;
    }
}

/* Allow short follow-ups if the student has recent tutor context. */
if (!$isHardwareTopic) {
    $recentCheck = $pdo->prepare("
        SELECT COUNT(*)
        FROM ai_chat_logs
        WHERE user_id = ?
          AND created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $recentCheck->execute([$user['id']]);
    $hasRecentChat = (int)$recentCheck->fetchColumn() > 0;

    $followupPhrases = [
        'why','how','explain','example','simpler','simple','more',
        'difference','what about','can you','quiz me','again'
    ];

    foreach ($followupPhrases as $phrase) {
        if (mb_strpos($lower, $phrase) !== false && $hasRecentChat) {
            $isHardwareTopic = true;
            break;
        }
    }
}

if (!$isHardwareTopic) {
    $reply = "I'm your Computer Hardware Fundamentals tutor. Please ask me about computer components, their functions, classification, storage, memory, power, peripherals, or basic troubleshooting.";

    $stmt = $pdo->prepare("
        INSERT INTO ai_chat_logs (user_id, user_message, ai_response)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $message, $reply]);

    respondJson([
        'ok' => true,
        'mode' => 'restricted',
        'provider' => 'local',
        'reply' => $reply
    ]);
}

/*
|--------------------------------------------------------------------------
| Approved course knowledge
|--------------------------------------------------------------------------
*/
$knowledge = [];

$lessons = $pdo->query("
    SELECT title, summary, content
    FROM lessons
    WHERE is_published = 1
    ORDER BY sort_order ASC, id ASC
    LIMIT 12
")->fetchAll();

foreach ($lessons as $lesson) {
    $plain = trim(strip_tags((string)$lesson['content']));
    $knowledge[] =
        "LESSON: " . $lesson['title'] . "\n" .
        "SUMMARY: " . ($lesson['summary'] ?: '') . "\n" .
        "CONTENT: " . mb_substr($plain, 0, 1800);
}

$hardware = $pdo->query("
    SELECT name, category, function_text
    FROM hardware_components
    ORDER BY name ASC
    LIMIT 30
")->fetchAll();

foreach ($hardware as $item) {
    $knowledge[] =
        "HARDWARE: " . $item['name'] .
        " | CATEGORY: " . ($item['category'] ?: 'Uncategorized') .
        " | FUNCTION: " . ($item['function_text'] ?: '');
}

$knowledgeText = implode("\n\n", $knowledge);

/*
|--------------------------------------------------------------------------
| Recent conversation
|--------------------------------------------------------------------------
*/
$historyStmt = $pdo->prepare("
    SELECT user_message, ai_response
    FROM ai_chat_logs
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 4
");
$historyStmt->execute([$user['id']]);
$historyRows = array_reverse($historyStmt->fetchAll());

$historyText = '';
foreach ($historyRows as $row) {
    $historyText .= "Student: " . $row['user_message'] . "\n";
    $historyText .= "Tutor: " . $row['ai_response'] . "\n\n";
}

/*
|--------------------------------------------------------------------------
| Local Demo Mode
|--------------------------------------------------------------------------
*/
function localDemoAnswer(string $message, array $hardware): string {
    $q = mb_strtolower($message);

    if (mb_strpos($q, 'difference') !== false &&
        mb_strpos($q, 'ram') !== false &&
        (mb_strpos($q, 'ssd') !== false || mb_strpos($q, 'hdd') !== false)) {
        return "RAM is temporary working memory used while programs are running, while SSD/HDD is long-term storage that keeps files even after shutdown. RAM is for active work; storage is for keeping data.";
    }

    $patterns = [
        'cpu' => 'The CPU executes instructions and performs calculations. It is the main processing unit of the computer.',
        'processor' => 'The processor or CPU executes program instructions, performs calculations, and coordinates processing tasks.',
        'motherboard' => 'The motherboard is the main circuit board. It connects major computer components and allows them to communicate.',
        'ram' => 'RAM is temporary working memory. It holds data and instructions currently needed by running programs and the CPU.',
        'ssd' => 'An SSD is a non-volatile storage device that keeps files after shutdown. It uses flash memory and is generally faster than a traditional HDD.',
        'hdd' => 'An HDD stores files on magnetic disks and keeps data even when the computer is turned off.',
        'gpu' => 'The GPU processes graphics and visual information. It is especially important for graphics-intensive software and games.',
        'psu' => 'The PSU converts incoming electrical power and supplies usable power to the computer’s internal components.',
        'power supply' => 'The Power Supply Unit converts incoming electrical power and supplies the voltages needed by computer components.',
        'keyboard' => 'A keyboard is an input device used to enter text, numbers, and commands into a computer.',
        'monitor' => 'A monitor is an output device that displays visual information from the computer.',
        'cooling' => 'Cooling components such as fans and heatsinks help remove heat and prevent hardware from overheating.',
        'fan' => 'A cooling fan moves air through the computer to help keep components within safe operating temperatures.',
        'nvme' => 'NVMe is a protocol designed for high-speed solid-state storage, commonly used by M.2 SSDs over PCIe.',
        'm.2' => 'M.2 is a compact physical form factor used by devices such as SSDs. An M.2 SSD may use SATA or NVMe/PCIe depending on the drive and slot.'
    ];

    foreach ($patterns as $needle => $answer) {
        if (mb_strpos($q, $needle) !== false) {
            return $answer;
        }
    }

    foreach ($hardware as $item) {
        if (mb_strpos($q, mb_strtolower($item['name'])) !== false && !empty($item['function_text'])) {
            return $item['name'] . ': ' . $item['function_text'];
        }
    }

    return "I can help with that hardware topic, but Local Demo Mode has limited explanations. Add a Gemini API key in config/ai.php to enable full AI tutoring.";
}

if (trim((string)GEMINI_API_KEY) === '') {
    $reply = localDemoAnswer($message, $hardware);

    $stmt = $pdo->prepare("
        INSERT INTO ai_chat_logs (user_id, user_message, ai_response)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $message, $reply]);

    respondJson([
        'ok' => true,
        'mode' => 'demo',
        'provider' => 'local',
        'reply' => $reply
    ]);
}

/*
|--------------------------------------------------------------------------
| Gemini Interactions API
|--------------------------------------------------------------------------
*/
$systemInstruction = <<<TEXT
You are the AI Hardware Tutor inside a Grade 10 Computer Hardware Fundamentals educational website.

STRICT SCOPE:
- Answer only Computer Hardware Fundamentals topics.
- Allowed topics include computer components, component functions, classification,
  input/output devices, storage, memory, processors, motherboards, GPUs, PSUs,
  cooling, ports, basic installation concepts, and basic troubleshooting.
- If the question is unrelated, politely say you can only help with Computer Hardware Fundamentals.

TEACHING STYLE:
- Use clear Grade 10 language.
- Give a short direct answer first.
- Explain the reason in 2 to 5 concise sentences.
- Use a simple example or analogy when useful.
- Do not overwhelm the student.
- Prefer the approved course knowledge below when relevant.
- If the approved material does not support a specific claim, do not invent it.

APPROVED COURSE KNOWLEDGE:
{$knowledgeText}
TEXT;

$interactionInput = <<<TEXT
RECENT TUTOR CONVERSATION:
{$historyText}

CURRENT STUDENT QUESTION:
{$message}
TEXT;

$payload = [
    'model' => GEMINI_MODEL,
    'input' => $interactionInput,
    'system_instruction' => $systemInstruction,
    'generation_config' => [
        'max_output_tokens' => GEMINI_MAX_OUTPUT_TOKENS,
        'thinking_level' => 'minimal'
    ],
    'store' => false
];

$url = 'https://generativelanguage.googleapis.com/v1beta/interactions';

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
    CURLOPT_TIMEOUT => 45,
]);

$raw = curl_exec($ch);
$curlError = curl_error($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false || $curlError) {
    respondJson([
        'ok' => false,
        'error' => 'Gemini connection failed. Check internet access and the PHP cURL extension.'
    ], 502);
}

$data = json_decode($raw, true);

if ($status < 200 || $status >= 300) {
    $apiMessage =
        $data['error']['message']
        ?? $data['error']['status']
        ?? 'Gemini Interactions API request failed.';

    respondJson([
        'ok' => false,
        'error' => $apiMessage
    ], ($status >= 400 && $status < 600) ? $status : 502);
}

/*
|--------------------------------------------------------------------------
| Extract text from Interactions API response
|--------------------------------------------------------------------------
| REST responses return model output inside steps[].content[].
*/
$reply = '';

if (!empty($data['steps']) && is_array($data['steps'])) {
    foreach ($data['steps'] as $step) {
        if (($step['type'] ?? '') !== 'model_output') {
            continue;
        }

        foreach (($step['content'] ?? []) as $content) {
            if (($content['type'] ?? '') === 'text' && isset($content['text'])) {
                $reply .= (string)$content['text'];
            }
        }
    }
}

/* Future-proof fallback in case output_text is exposed in REST. */
if ($reply === '' && isset($data['output_text'])) {
    $reply = (string)$data['output_text'];
}

$reply = trim($reply);

if ($reply === '') {
    $interactionStatus = $data['status'] ?? 'unknown';

    respondJson([
        'ok' => false,
        'error' => 'Gemini returned no readable text. Interaction status: ' . $interactionStatus
    ], 502);
}

$stmt = $pdo->prepare("
    INSERT INTO ai_chat_logs (user_id, user_message, ai_response)
    VALUES (?, ?, ?)
");
$stmt->execute([$user['id'], $message, $reply]);

respondJson([
    'ok' => true,
    'mode' => 'live',
    'provider' => 'gemini',
    'model' => GEMINI_MODEL,
    'interaction_id' => $data['id'] ?? null,
    'reply' => $reply
]);
?>
