<?php
require_once __DIR__ . '/config/database.php';

try {
    // Add research_group to users when upgrading an existing database.
    $col = $pdo->query("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'users'
          AND COLUMN_NAME = 'research_group'
    ")->fetchColumn();

    if ((int)$col === 0) {
        $pdo->exec("
            ALTER TABLE users
            ADD COLUMN research_group ENUM('experimental','control') NULL AFTER section
        ");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_settings (
            setting_key VARCHAR(80) PRIMARY KEY,
            setting_value VARCHAR(255) NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_questions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            item_number INT NOT NULL UNIQUE,
            part_title VARCHAR(180) NOT NULL,
            question TEXT NOT NULL,
            option_a VARCHAR(255) NOT NULL,
            option_b VARCHAR(255) NOT NULL,
            option_c VARCHAR(255) NOT NULL,
            option_d VARCHAR(255) NOT NULL,
            correct_option ENUM('A','B','C','D') NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_attempts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            assessment_type ENUM('pretest','posttest') NOT NULL,
            score INT NOT NULL DEFAULT 0,
            total_items INT NOT NULL DEFAULT 0,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_research_user_type (user_id, assessment_type),
            CONSTRAINT fk_ra_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS research_answers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            attempt_id BIGINT UNSIGNED NOT NULL,
            question_id INT UNSIGNED NOT NULL,
            selected_option ENUM('A','B','C','D') NOT NULL,
            is_correct TINYINT(1) NOT NULL DEFAULT 0,
            CONSTRAINT fk_rans_attempt FOREIGN KEY (attempt_id) REFERENCES research_attempts(id) ON DELETE CASCADE,
            CONSTRAINT fk_rans_question FOREIGN KEY (question_id) REFERENCES research_questions(id) ON DELETE CASCADE
        )
    ");

    $setting = $pdo->prepare("
        INSERT INTO research_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = setting_value
    ");
    $setting->execute(['pretest_open', '0']);
    $setting->execute(['posttest_open', '0']);

    // Exact achievement-test wording from the thesis appendix.
    // Correct options are a provisional implementation key and must be
    // verified/approved by the research validators before formal data collection.
    $questions = [
        [1, 'Part I. Computer Hardware Identification',
         'Which component is considered the main processing unit of a computer?',
         'RAM','CPU','Monitor','Keyboard','B'],

        [2, 'Part I. Computer Hardware Identification',
         'Which hardware component is primarily used to store data permanently?',
         'RAM','CPU','Storage drive','Monitor','C'],

        [3, 'Part I. Computer Hardware Identification',
         'Which device is used to display information from a computer?',
         'Keyboard','Mouse','Monitor','Scanner','C'],

        [4, 'Part I. Computer Hardware Identification',
         'Which of the following is an input device?',
         'Monitor','Keyboard','Speaker','Projector','B'],

        [5, 'Part I. Computer Hardware Identification',
         'Which component supplies electrical power to the computer?',
         'RAM','CPU','Power Supply Unit','Motherboard','C'],

        [6, 'Part II. Functions of Hardware Components',
         'What is the primary function of RAM?',
         'To permanently store files','To temporarily hold data being used by the computer',
         'To display images','To supply electrical power','B'],

        [7, 'Part II. Functions of Hardware Components',
         'What is the main function of the motherboard?',
         'To print documents','To connect and allow communication among computer components',
         'To store all files permanently','To display information','B'],

        [8, 'Part II. Functions of Hardware Components',
         'What is the primary function of a GPU?',
         'Process graphics and visual information','Store documents permanently',
         'Supply electrical power','Connect a keyboard','A'],

        [9, 'Part II. Functions of Hardware Components',
         'What is the main purpose of a cooling fan?',
         'To store data','To prevent components from overheating',
         'To process information','To display images','B'],

        [10, 'Part II. Functions of Hardware Components',
         'What is the primary purpose of an SSD or HDD?',
         'Temporary processing','Permanent data storage','Power distribution','Graphic processing','B'],

        [11, 'Part III. Application and Troubleshooting',
         'A computer becomes slow when several programs are running at the same time. Which component may need additional capacity?',
         'RAM','Monitor','Keyboard','Speaker','A'],

        [12, 'Part III. Application and Troubleshooting',
         'A computer suddenly shuts down because its components are not receiving sufficient power. Which component should be checked?',
         'GPU','PSU','Keyboard','Monitor','B'],

        [13, 'Part III. Application and Troubleshooting',
         "A student wants to improve the computer's performance when running graphics-intensive applications. Which component may need to be upgraded?",
         'GPU','Keyboard','Mouse','Speaker','A'],

        [14, 'Part III. Application and Troubleshooting',
         'A student needs to save files even after the computer is turned off. Which component should be used?',
         'RAM','CPU','SSD/HDD','Cache','C'],

        [15, 'Part III. Application and Troubleshooting',
         'Why is it important to know the function of a hardware component before replacing it?',
         'To ensure the correct component is selected','To increase the monitor size',
         'To change the keyboard layout','To make the computer heavier','A'],
    ];

    $check = $pdo->prepare("SELECT id FROM research_questions WHERE item_number = ? LIMIT 1");
    $insert = $pdo->prepare("
        INSERT INTO research_questions
        (item_number, part_title, question, option_a, option_b, option_c, option_d, correct_option)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $added = 0;
    foreach ($questions as $q) {
        $check->execute([$q[0]]);
        if (!$check->fetch()) {
            $insert->execute($q);
            $added++;
        }
    }

    // Existing demo student can continue using learning modules.
    $pdo->exec("
        UPDATE users
        SET research_group = 'experimental'
        WHERE role = 'student'
          AND username = 'student'
          AND research_group IS NULL
    ");

    $message = "Phase 7 research module is ready. Research questions added: {$added}.";
} catch (Throwable $e) {
    $message = 'Update failed: ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phase 7 Update | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <section class="content-card mx-auto mt-5" style="max-width:860px;">
        <span class="eyebrow">Phase 7</span>
        <h1 class="mt-2">Formal Research Assessment</h1>

        <div class="alert alert-info mt-4">
            <?= htmlspecialchars($message) ?>
        </div>

        <div class="alert alert-warning">
            <strong>Before formal data collection:</strong>
            verify the achievement-test answer key with the ICT validators/adviser.
            The thesis appendix contains the questions but does not print an answer key.
        </div>

        <p class="text-secondary">
            After this update, log out and log back in once so the new research-group field
            is loaded into the session.
        </p>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="admin/research.php">Research Admin</a>
            <a class="btn btn-outline-primary" href="student/research.php">Student Assessment</a>
            <a class="btn btn-outline-secondary" href="admin/students.php">Assign Groups</a>
        </div>
    </section>
</main>
</body>
</html>
