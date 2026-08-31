<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$rows = $pdo->query("
    SELECT
        u.student_id,
        u.full_name,
        u.section,
        u.research_group,
        pre.score AS pre_score,
        pre.total_items AS pre_total,
        pre.submitted_at AS pre_submitted,
        post.score AS post_score,
        post.total_items AS post_total,
        post.submitted_at AS post_submitted
    FROM users u
    LEFT JOIN research_attempts pre
      ON pre.user_id = u.id AND pre.assessment_type = 'pretest'
    LEFT JOIN research_attempts post
      ON post.user_id = u.id AND post.assessment_type = 'posttest'
    WHERE u.role = 'student' AND u.is_active = 1
    ORDER BY u.research_group, u.full_name
")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="research-achievement-results.csv"');

$out = fopen('php://output', 'w');

fputcsv($out, [
    'Student Code',
    'Student Name',
    'Section',
    'Research Group',
    'Pretest Score',
    'Pretest Total',
    'Pretest Date',
    'Posttest Score',
    'Posttest Total',
    'Posttest Date',
    'Raw Gain'
]);

foreach ($rows as $row) {
    $gain = '';
    if ($row['pre_score'] !== null && $row['post_score'] !== null) {
        $gain = (int)$row['post_score'] - (int)$row['pre_score'];
    }

    fputcsv($out, [
        $row['student_id'],
        $row['full_name'],
        $row['section'],
        $row['research_group'],
        $row['pre_score'],
        $row['pre_total'],
        $row['pre_submitted'],
        $row['post_score'],
        $row['post_total'],
        $row['post_submitted'],
        $gain
    ]);
}

fclose($out);
exit;
?>
