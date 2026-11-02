<?php
session_start();
include 'config/db.php';

$message = "";

// Handle Registration
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $username = trim($username);
    $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user'; // Default role for new signups

    // FIX: prepared statement (was string-interpolated -> SQL injection)
    $chk = $conn->prepare("SELECT id FROM users WHERE username=?");
    $chk->bind_param("s", $username);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $message = "Username already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hash, $role);
        if ($stmt->execute()) {
            $message = "Registration successful! You can now login.";
        } else {
            $message = "Registration failed. Please try again.";
        }
    }
    $chk->close();
}

// Handle Login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // FIX: fetch by username only, then verify the hash
    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Accepts hashed passwords; also accepts old plaintext rows and upgrades them
    $ok = false;
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $ok = true;
        } elseif (hash_equals($user['password'], $password)) {
            $ok = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $up->bind_param("si", $newHash, $user['id']);
            $up->execute();
        }
    }

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect based on role
        if ($user['role'] == 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: user/dashboard.php");
        }
        exit();
    } else {
        $message = "Invalid username or password!";
    }
}

// Which tab to show first (register tab only if registration failed)
$active = (isset($_POST['register']) && strpos($message, 'successful') === false) ? 'register' : 'login';
$isSuccess = strpos($message, 'successful') !== false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | Feedback Hub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth">
    <aside class="auth-aside">
        <a class="brand" href="index.php"><span class="brand-mark"></span>Feedback Hub</a>
        <div>
            <h1>Hear what your people think.</h1>
            <p>Create feedback forms, collect honest answers and review every response in one place.</p>
            <ul class="auth-points">
                <li>Build a form with your own questions in minutes</li>
                <li>Each person answers a form once</li>
                <li>Read responses grouped by respondent</li>
            </ul>
        </div>
        <span style="color:#9fb0e0;font-size:.85rem;">&copy; <?= date('Y') ?> Feedback Hub</span>
    </aside>

    <main class="auth-main">
        <div class="auth-box">
            <h2>Welcome</h2>
            <p class="muted" style="margin:6px 0 0;">Log in or create an account to continue.</p>

            <div class="tabs" role="tablist">
                <button type="button" class="tab <?= $active=='login' ? 'on' : '' ?>" data-tab="login">Log in</button>
                <button type="button" class="tab <?= $active=='register' ? 'on' : '' ?>" data-tab="register">Create account</button>
            </div>

            <?php if ($message): ?>
                <div class="alert <?= $isSuccess ? 'success' : 'error' ?>" style="margin-top:14px;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" class="pane <?= $active=='login' ? 'on' : '' ?>" id="pane-login">
                <label for="l-user">Username</label>
                <input type="text" id="l-user" name="username" required autocomplete="username">
                <label for="l-pass">Password</label>
                <input type="password" id="l-pass" name="password" required autocomplete="current-password">
                <button class="btn btn-block" type="submit" name="login">Log in</button>
            </form>

            <form method="POST" class="pane <?= $active=='register' ? 'on' : '' ?>" id="pane-register">
                <label for="r-user">Choose a username</label>
                <input type="text" id="r-user" name="username" required autocomplete="username">
                <label for="r-pass">Choose a password</label>
                <input type="password" id="r-pass" name="password" required autocomplete="new-password">
                <button class="btn btn-block" type="submit" name="register">Create account</button>
            </form>
        </div>
    </main>
</div>
<script>
document.querySelectorAll('.tab').forEach(function (t) {
    t.addEventListener('click', function () {
        document.querySelectorAll('.tab').forEach(function (x) { x.classList.toggle('on', x === t); });
        document.querySelectorAll('.pane').forEach(function (p) { p.classList.toggle('on', p.id === 'pane-' + t.dataset.tab); });
    });
});
</script>
</body>
</html>
