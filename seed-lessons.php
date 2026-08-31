<?php
require_once __DIR__ . '/config/database.php';

$lessons = [
    [
        'Computer Hardware Basics',
        'Learn the difference between hardware and software and identify major computer components.',
        '<h2>What is Computer Hardware?</h2>
         <p>Computer hardware refers to the physical parts of a computer system that you can see and touch.</p>
         <h3>Common hardware components</h3>
         <ul>
            <li>Motherboard</li>
            <li>Central Processing Unit (CPU)</li>
            <li>Random Access Memory (RAM)</li>
            <li>Storage devices such as SSD and HDD</li>
            <li>Power Supply Unit (PSU)</li>
            <li>Input and output devices</li>
         </ul>
         <p>These components work together to receive input, process data, store information, and produce output.</p>',
        1
    ],
    [
        'Motherboard and CPU',
        'Understand the role of the motherboard and the CPU in a computer system.',
        '<h2>The Motherboard</h2>
         <p>The motherboard is the main circuit board of the computer. It connects major hardware components and allows them to communicate.</p>
         <h2>The CPU</h2>
         <p>The Central Processing Unit executes instructions and performs calculations required by programs.</p>
         <h3>Remember</h3>
         <ul>
            <li>The motherboard connects components.</li>
            <li>The CPU processes instructions.</li>
         </ul>',
        2
    ],
    [
        'RAM and Storage',
        'Compare temporary memory with permanent storage devices.',
        '<h2>RAM</h2>
         <p>RAM temporarily holds data and instructions that the computer is actively using.</p>
         <h2>SSD and HDD</h2>
         <p>Storage devices keep files and programs even after the computer is turned off.</p>
         <h3>Quick comparison</h3>
         <ul>
            <li>RAM: temporary and fast working memory.</li>
            <li>SSD/HDD: long-term storage.</li>
         </ul>',
        3
    ]
];

$check = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE title = ?");
$insert = $pdo->prepare("
    INSERT INTO lessons (title, slug, summary, content, sort_order, is_published)
    VALUES (?, ?, ?, ?, ?, 1)
");

$added = 0;
foreach ($lessons as [$title, $summary, $content, $order]) {
    $check->execute([$title]);
    if ((int)$check->fetchColumn() === 0) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-')) . '-' . time() . '-' . $order;
        $insert->execute([$title, $slug, $summary, $content, $order]);
        $added++;
    }
}

echo "<h2>Sample lessons ready.</h2>";
echo "<p>Added: {$added}</p>";
echo '<p><a href="student/lessons.php">Open Student Lessons</a></p>';
echo '<p><a href="admin/lessons.php">Manage Lessons</a></p>';
?>
