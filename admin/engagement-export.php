<?php
require_once __DIR__ . '/../config/auth.php';
requireRole('admin');
require_once __DIR__ . '/../config/database.php';

$questions = $pdo->query("
    SELECT id, item_number, dimension
    FROM engagement_questions
    WHERE is_active = 1
    ORDER BY item_number ASC
")->fetchAll();

$students = $pdo->query("
    SELECT id, student_id, full_name, section, research_group
    FROM users
    WHERE role = 'student' AND is_active = 1
    ORDER BY research_group, full_name
")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="student-engagement-prepost.csv"');

$out = fopen('php://output', 'w');

$header = [
    'Student Code',
    'Student Name',
    'Section',
    'Research Group'
];

foreach (['Pre','Post'] as $prefix) {
    foreach ($questions as $q) {
        $header[] = $prefix . '_Item_' . $q['item_number'];
    }

    $header[] = $prefix . '_Behavioral_Mean';
    $header[] = $prefix . '_Cognitive_Mean';
    $header[] = $prefix . '_Emotional_Mean';
    $header[] = $prefix . '_Overall_Mean';
    $header[] = $prefix . '_Submitted_At';
}

$header[] = 'Behavioral_Change';
$header[] = 'Cognitive_Change';
$header[] = 'Emotional_Change';
$header[] = 'Overall_Change';

fputcsv($out, $header);

foreach ($students as $student) {
    $row = [
        $student['student_id'],
        $student['full_name'],
        $student['section'],
        $student['research_group']
    ];

    $attemptData = [];

    foreach (['pre','post'] as $type) {
        $stmt = $pdo->prepare("
            SELECT *
            FROM engagement_attempts
            WHERE user_id = ? AND assessment_type = ?
            LIMIT 1
        ");
        $stmt->execute([$student['id'], $type]);
        $attempt = $stmt->fetch();

        $answersByQuestion = [];

        if ($attempt) {
            $ansStmt = $pdo->prepare("
                SELECT question_id, response_value
                FROM engagement_answers
                WHERE attempt_id = ?
            ");
            $ansStmt->execute([$attempt['id']]);

            foreach ($ansStmt->fetchAll() as $answer) {
                $answersByQuestion[$answer['question_id']] = $answer['response_value'];
            }
        }

        foreach ($questions as $q) {
            $row[] = $answersByQuestion[$q['id']] ?? '';
        }

        if ($attempt) {
            $row[] = $attempt['behavioral_mean'];
            $row[] = $attempt['cognitive_mean'];
            $row[] = $attempt['emotional_mean'];
            $row[] = $attempt['overall_mean'];
            $row[] = $attempt['submitted_at'];

            $attemptData[$type] = $attempt;
        } else {
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
            $row[] = '';
        }
    }

    foreach (['behavioral_mean','cognitive_mean','emotional_mean','overall_mean'] as $metric) {
        if (isset($attemptData['pre'], $attemptData['post'])) {
            $row[] = number_format(
                (float)$attemptData['post'][$metric] - (float)$attemptData['pre'][$metric],
                2,
                '.',
                ''
            );
        } else {
            $row[] = '';
        }
    }

    fputcsv($out, $row);
}

fclose($out);
exit;
?>
