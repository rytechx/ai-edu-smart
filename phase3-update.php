<?php
require_once __DIR__ . '/config/database.php';

try {
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

    $samples = [
        ['CPU', 'Processing', 'Executes instructions and performs calculations for the computer.', 'assets/images/hardware/cpu.svg'],
        ['RAM', 'Memory', 'Temporarily stores active data and instructions used by running programs.', 'assets/images/hardware/ram.svg'],
        ['SSD', 'Storage', 'Stores files and programs permanently using flash memory.', 'assets/images/hardware/ssd.svg'],
        ['GPU', 'Processing', 'Processes graphics and visual information.', 'assets/images/hardware/gpu.svg'],
        ['Power Supply Unit', 'Power', 'Converts and supplies electrical power to computer components.', 'assets/images/hardware/psu.svg'],
        ['Motherboard', 'Mainboard', 'Connects major computer components and allows them to communicate.', 'assets/images/hardware/motherboard.svg'],
    ];

    $check = $pdo->prepare("SELECT id FROM hardware_components WHERE name = ? LIMIT 1");
    $insert = $pdo->prepare("
        INSERT INTO hardware_components (name, category, function_text, image_path)
        VALUES (?, ?, ?, ?)
    ");

    $added = 0;
    foreach ($samples as $sample) {
        $check->execute([$sample[0]]);
        if (!$check->fetch()) {
            $insert->execute($sample);
            $added++;
        }
    }

    $message = "Phase 3 database update completed. Sample hardware added: {$added}.";
} catch (Throwable $e) {
    $message = "Update failed: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phase 3 Update | AI Edu Smart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<main class="container py-5">
    <section class="content-card mx-auto mt-5" style="max-width:760px;">
        <span class="eyebrow">Phase 3</span>
        <h1 class="mt-2">Hardware Identification Update</h1>
        <div class="alert alert-info mt-4"><?= htmlspecialchars($message) ?></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="student/hardware.php">Test Student Activity</a>
            <a class="btn btn-outline-primary" href="admin/hardware.php">Manage Hardware</a>
            <a class="btn btn-outline-secondary" href="index.php">Home</a>
        </div>
    </section>
</main>
</body>
</html>
