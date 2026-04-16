<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, password, full_name FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['username'] = $username;
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid password. Please try again.';
            }
        } else {
            $error = 'Admin account not found.';
        }
        $conn->close();
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockMaster — Admin Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0a0f1e;
            --surface: #111827;
            --border: #1f2d45;
            --accent: #f97316;
            --accent2: #fb923c;
            --text: #f1f5f9;
            --muted: #64748b;
            --success: #22c55e;
            --danger: #ef4444;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .bg-pattern {
            position: fixed; inset: 0; z-index: 0;
            background:
                radial-gradient(ellipse 80% 60% at 20% 10%, rgba(249,115,22,0.12) 0%, transparent 60%),
                radial-gradient(ellipse 60% 50% at 80% 90%, rgba(249,115,22,0.08) 0%, transparent 60%);
        }

        .grid-lines {
            position: fixed; inset: 0; z-index: 0;
            background-image:
                linear-gradient(rgba(249,115,22,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(249,115,22,0.04) 1px, transparent 1px);
            background-size: 60px 60px;
        }

        .login-wrap {
            position: relative; z-index: 1;
            width: 100%; max-width: 440px;
            padding: 20px;
            animation: slideUp 0.6s ease both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28px;
            margin-bottom: 16px;
            box-shadow: 0 0 40px rgba(249,115,22,0.4);
        }

        .brand h1 {
            font-family: 'Syne', sans-serif;
            font-size: 2rem; font-weight: 800;
            letter-spacing: -0.5px;
        }

        .brand h1 span { color: var(--accent); }

        .brand p { color: var(--muted); font-size: 0.875rem; margin-top: 6px; }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 36px;
            backdrop-filter: blur(10px);
        }

        .form-group { margin-bottom: 20px; }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap span {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            font-size: 18px; pointer-events: none;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 14px 13px 44px;
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(249,115,22,0.15);
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(249,115,22,0.4);
        }

        .btn-login:active { transform: translateY(0); }

        .error-msg {
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 8px;
            padding: 12px 16px;
            color: #fca5a5;
            font-size: 0.875rem;
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 8px;
        }

        .hint {
            text-align: center;
            margin-top: 24px;
            padding: 14px;
            background: rgba(249,115,22,0.06);
            border: 1px dashed rgba(249,115,22,0.2);
            border-radius: 10px;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .hint strong { color: var(--accent); }
    </style>
</head>
<body>
    <div class="bg-pattern"></div>
    <div class="grid-lines"></div>

    <div class="login-wrap">
        <div class="brand">
            <div class="brand-icon">🏪</div>
            <h1>Stock<span>Master</span></h1>
            <p>General Store Inventory Management</p>
        </div>

        <div class="card">
            <?php if ($error): ?>
                <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <div class="input-wrap">
                        <span>👤</span>
                        <input type="text" name="username" placeholder="Enter username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrap">
                        <span>🔒</span>
                        <input type="password" name="password" placeholder="Enter password" required>
                    </div>
                </div>
                <button type="submit" class="btn-login">LOGIN TO DASHBOARD →</button>
            </form>
        </div>

        <div class="hint">
            Default credentials — Username: <strong>admin</strong> &nbsp;|&nbsp; Password: <strong>admin123</strong>
        </div>
    </div>
</body>
</html>
