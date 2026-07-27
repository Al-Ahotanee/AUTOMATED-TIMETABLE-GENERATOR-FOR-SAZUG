<?php
require_once __DIR__ . '/SystemCore.php';
$core = new SystemCore();

// Redirect to dashboard if already logged in
if ($core->isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        if ($core->login($username, $password)) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sazug Timetable System</title>
    <!-- Use Bootstrap 5 CDN for minimalist, professional UI -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f7f6;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            background: #ffffff;
            border: none;
        }
        .brand-logo {
            font-weight: 800;
            font-size: 1.5rem;
            color: #2c3e50;
            text-align: center;
            margin-bottom: 2rem;
            letter-spacing: -0.5px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            font-weight: 500;
            padding: 0.6rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" class="bi bi-calendar-grid-3x3 mb-1 me-2 text-primary" viewBox="0 0 16 16">
            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
            <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
        </svg>
        Sazug Timetable
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 px-3 text-sm" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label for="username" class="form-label text-muted small fw-bold">Administrator ID</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="e.g. admin" required autofocus>
        </div>
        <div class="mb-4">
            <label for="password" class="form-label text-muted small fw-bold">Password</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Secure Login</button>
        </div>
    </form>
    <div class="mt-4 text-center text-muted" style="font-size: 0.75rem;">
        &copy; <?= date('Y') ?> Dept of Mathematical Sciences
    </div>
</div>

</body>
</html>
