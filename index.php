<?php
session_start();

if (!empty($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'student';
    header('Location: ' . ($role === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Edu Smart | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <main class="container py-5">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-lg-10">
                <div class="login-shell">
                    <section class="brand-panel">
                        <div class="brand-mark">AI</div>
                        <span class="eyebrow">Computer Hardware Fundamentals</span>
                        <h1>AI-Powered Educational Smart Application</h1>
                        <p>
                            Learn computer hardware through interactive lessons, identification activities,
                            quizzes, immediate feedback, and AI-assisted explanations.
                        </p>
                        <div class="feature-strip">
                            <span>Interactive Lessons</span>
                            <span>Hardware ID</span>
                            <span>AI Tutor</span>
                        </div>
                    </section>

                    <section class="login-panel">
                        <div class="mb-4">
                            <span class="eyebrow">Welcome</span>
                            <h2 class="mt-2">Sign in to continue</h2>
                            <p class="text-secondary mb-0">Use your student or administrator account.</p>
                        </div>

                        <?php if ($error === 'invalid'): ?>
                            <div class="alert alert-danger">Invalid username or password.</div>
                        <?php endif; ?>

                        <form action="login.php" method="post" class="vstack gap-3">
                            <div>
                                <label class="form-label">Username</label>
                                <input class="form-control form-control-lg" type="text" name="username" required autocomplete="username">
                            </div>
                            <div>
                                <label class="form-label">Password</label>
                                <input class="form-control form-control-lg" type="password" name="password" required autocomplete="current-password">
                            </div>
                            <button class="btn btn-primary btn-lg w-100" type="submit">Sign In</button>
                        </form>

                        <div class="demo-box mt-4">
                            <strong>Demo accounts</strong>
                            <div>Admin: <code>admin</code> / <code>admin123</code></div>
                            <div>Student: <code>student</code> / <code>student123</code></div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
