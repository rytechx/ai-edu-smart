<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['user'])) {
        header('Location: ../index.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();

    if (($_SESSION['user']['role'] ?? '') !== $role) {
        header('Location: ../index.php');
        exit;
    }
}


function requireExperimental(): void {
    requireRole('student');

    if (($_SESSION['user']['research_group'] ?? null) !== 'experimental') {
        header('Location: research.php?access=learning-restricted');
        exit;
    }
}
?>
