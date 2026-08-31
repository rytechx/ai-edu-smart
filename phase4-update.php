<?php
require_once __DIR__ . '/config/database.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quiz_attempt_answers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attempt_id INT UNSIGNED NOT NULL,
            question_id INT UNSIGNED NOT NULL,
            selected_option ENUM('A','B','C','D') NULL,
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_qaa_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
            CONSTRAINT fk_qaa_question FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
        )
    ");

    $questions = [
        [
            'Which component is considered the main processing unit of a computer?',
            'RAM','CPU','Monitor','Keyboard','B',
            'The CPU is the main processing unit that executes instructions and performs calculations.'
        ],
        [
            'Which hardware component is primarily used to store data permanently?',
            'RAM','CPU','Storage drive','Monitor','C',
            'Storage drives such as SSDs and HDDs keep data even when the computer is powered off.'
        ],
        [
            'Which device is used to display information from a computer?',
            'Keyboard','Mouse','Monitor','Scanner','C',
            'A monitor is an output device used to display visual information from the computer.'
        ],
        [
            'Which of the following is an input device?',
            'Monitor','Keyboard','Speaker','Projector','B',
            'A keyboard is an input device used to enter text and commands into a computer.'
        ],
        [
            'Which component supplies electrical power to the computer?',
            'RAM','CPU','Power Supply Unit','Motherboard','C',
            'The Power Supply Unit converts incoming electricity and supplies usable power to computer components.'
        ],
        [
            'What is the primary function of RAM?',
            'To permanently store files',
            'To temporarily hold data being used by the computer',
            'To display images',
            'To supply electrical power',
            'B',
            'RAM temporarily stores active data and instructions that programs and the CPU are using.'
        ],
        [
            'What is the main function of the motherboard?',
            'To print documents',
            'To connect and allow communication among computer components',
            'To store all files permanently',
            'To display information',
            'B',
            'The motherboard connects major components and provides communication pathways between them.'
        ],
        [
            'What is the primary function of a GPU?',
            'Process graphics and visual information',
            'Store documents permanently',
            'Supply electrical power',
            'Connect a keyboard',
            'A',
            'The GPU specializes in processing graphics and visual information.'
        ],
        [
            'What is the main purpose of a cooling fan?',
            'To store data',
            'To prevent components from overheating',
            'To process information',
            'To display images',
            'B',
            'Cooling fans move air through the system to help keep components within safe temperatures.'
        ],
        [
            'What is the primary purpose of an SSD or HDD?',
            'Temporary processing',
            'Permanent data storage',
            'Power distribution',
            'Graphic processing',
            'B',
            'SSDs and HDDs provide long-term storage for files, programs, and operating-system data.'
        ],
        [
            'A computer becomes slow when several programs are running at the same time. Which component may need additional capacity?',
            'RAM','Monitor','Keyboard','Speaker','A',
            'More RAM can help when many applications are running because RAM holds active program data.'
        ],
        [
            'A computer suddenly shuts down because its components are not receiving sufficient power. Which component should be checked?',
            'GPU','PSU','Keyboard','Monitor','B',
            'The PSU should be checked because it is responsible for supplying electrical power to the system.'
        ],
        [
            "A student wants to improve the computer's performance when running graphics-intensive applications. Which component may need to be upgraded?",
            'GPU','Keyboard','Mouse','Speaker','A',
            'Graphics-intensive applications depend heavily on the GPU.'
        ],
        [
            'A student needs to save files even after the computer is turned off. Which component should be used?',
            'RAM','CPU','SSD/HDD','Cache','C',
            'SSD/HDD storage is non-volatile, so stored files remain available after shutdown.'
        ],
        [
            'Why is it important to know the function of a hardware component before replacing it?',
            'To ensure the correct component is selected',
            'To increase the monitor size',
            'To change the keyboard layout',
            'To make the computer heavier',
            'A',
            'Understanding a component’s function helps ensure the correct replacement part is chosen.'
        ]
    ];

    $check = $pdo->prepare("SELECT id FROM quiz_questions WHERE question = ? LIMIT 1");
    $insert = $pdo->prepare("
        INSERT INTO quiz_questions
        (question, option_a, option_b, option_c, option_d, correct_option, explanation)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $added = 0;
    foreach ($questions as $q) {
        $check->execute([$q[0]]);
        if (!$check->fetch()) {
            $insert->execute($q);
            $added++;
        }
    }

    $message = "Phase 4 database update completed. Sample quiz questions added: {$added}.";
} catch (Throwable $e) {
    $message = "Update failed: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phase 4 Update | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <section class="content-card mx-auto mt-5" style="max-width:780px;">
        <span class="eyebrow">Phase 4</span>
        <h1 class="mt-2">Quiz & Practice Update</h1>
        <div class="alert alert-info mt-4"><?= htmlspecialchars($message) ?></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="student/quiz.php">Test Student Quiz</a>
            <a class="btn btn-outline-primary" href="admin/quiz.php">Manage Quiz</a>
            <a class="btn btn-outline-secondary" href="student/results.php">View Results</a>
        </div>
    </section>
</main>
</body>
</html>
