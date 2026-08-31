<?php
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO(
            'mysql:host=localhost;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $pdo->exec("CREATE DATABASE IF NOT EXISTS ai_edu_smart CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE ai_edu_smart");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(50) NULL,
                full_name VARCHAR(150) NOT NULL,
                username VARCHAR(80) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('student','admin') NOT NULL DEFAULT 'student',
                section VARCHAR(100) NULL,
                research_group ENUM('experimental','control') NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS lessons (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                slug VARCHAR(190) NOT NULL UNIQUE,
                summary TEXT NULL,
                content LONGTEXT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS hardware_components (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                category VARCHAR(100) NULL,
                function_text TEXT NULL,
                image_path VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS quiz_questions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                lesson_id INT UNSIGNED NULL,
                question TEXT NOT NULL,
                option_a VARCHAR(255) NOT NULL,
                option_b VARCHAR(255) NOT NULL,
                option_c VARCHAR(255) NOT NULL,
                option_d VARCHAR(255) NOT NULL,
                correct_option ENUM('A','B','C','D') NOT NULL,
                explanation TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_quiz_lesson FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS quiz_attempts (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                score INT NOT NULL DEFAULT 0,
                total_items INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_attempt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

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

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS hardware_attempts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                component_id INT UNSIGNED NOT NULL,
                selected_component_id INT UNSIGNED NULL,
                is_correct TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_hw_attempt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_hw_attempt_component FOREIGN KEY (component_id) REFERENCES hardware_components(id) ON DELETE CASCADE
            )
        ");

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

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS engagement_questions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                item_number INT NOT NULL UNIQUE,
                dimension ENUM('behavioral','cognitive','emotional') NOT NULL,
                statement TEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS engagement_attempts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                assessment_type ENUM('pre','post') NOT NULL,
                behavioral_mean DECIMAL(4,2) NOT NULL,
                cognitive_mean DECIMAL(4,2) NOT NULL,
                emotional_mean DECIMAL(4,2) NOT NULL,
                overall_mean DECIMAL(4,2) NOT NULL,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_engagement_user_type (user_id, assessment_type),
                CONSTRAINT fk_ea_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS engagement_answers (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                attempt_id BIGINT UNSIGNED NOT NULL,
                question_id INT UNSIGNED NOT NULL,
                response_value TINYINT UNSIGNED NOT NULL,
                CONSTRAINT fk_eans_attempt FOREIGN KEY (attempt_id) REFERENCES engagement_attempts(id) ON DELETE CASCADE,
                CONSTRAINT fk_eans_question FOREIGN KEY (question_id) REFERENCES engagement_questions(id) ON DELETE CASCADE,
                CONSTRAINT chk_response_value CHECK (response_value BETWEEN 1 AND 4)
            )
        ");

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

        $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");

        $check->execute(['admin']);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([
                'System Administrator',
                'admin',
                password_hash('admin123', PASSWORD_DEFAULT)
            ]);
        }

        $check->execute(['student']);
        if (!$check->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, role, section) VALUES (?, ?, ?, 'student', ?)");
            $stmt->execute([
                'Demo Student',
                'student',
                password_hash('student123', PASSWORD_DEFAULT),
                'Grade 10 - Demo'
            ]);
        }

        $message = 'Setup complete! The database and demo accounts are ready.';
    } catch (Throwable $e) {
        $error = 'Setup failed: ' . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <div class="content-card mx-auto mt-5" style="max-width: 720px;">
        <span class="eyebrow">Localhost Setup</span>
        <h1 class="mt-2">AI Edu Smart</h1>
        <p class="text-secondary">
            Make sure Apache and MySQL are running in XAMPP, then click the button once.
        </p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <a class="btn btn-primary" href="index.php">Open Login</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <button class="btn btn-primary btn-lg" type="submit">Initialize Local Database</button>
            </form>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
