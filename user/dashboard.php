<?php
session_start();
include '../config/db.php';

// Security check: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
$uid = (int)$_SESSION['user_id'];
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$forms = $conn->query("SELECT f.*,
    (SELECT COUNT(*) FROM questions q WHERE q.form_id = f.id) AS q_count,
    (SELECT COUNT(*) FROM responses r WHERE r.form_id = f.id AND r.user_id = $uid) AS done
    FROM forms f ORDER BY f.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>My forms | Feedback Hub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="topbar"><div class="topbar-inner">
    <a class="brand" href="dashboard.php"><span class="brand-mark"></span>Feedback Hub</a>
    <nav class="topnav"><a href="dashboard.php" class="active">My forms</a></nav>
    <div class="userbox">
        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['username'], 0, 1))) ?></span>
        <span class="uname"><?= htmlspecialchars($_SESSION['username']) ?></span>
        <a class="btn btn-ghost btn-sm" href="../logout.php">Log out</a>
    </div>
</div></header>
<main class="page">
    <div class="page-head">
        <div>
            <h1>Feedback forms</h1>
            <p>Pick a form and share your thoughts. Each form can be submitted once.</p>
        </div>
    </div>

    <?php if ($flash): ?><div class="alert success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

    <?php if ($forms->num_rows == 0): ?>
        <div class="card empty">
            <h3>No forms available right now</h3>
            <p>New feedback forms will appear here when they are published.</p>
        </div>
    <?php else: ?>
    <div class="grid">
        <?php while ($row = $forms->fetch_assoc()): ?>
        <article class="form-card">
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <p><?= htmlspecialchars($row['description'] ?: 'No description provided.') ?></p>
            <div class="meta">
                <span class="muted"><?= (int)$row['q_count'] ?> question<?= $row['q_count'] == 1 ? '' : 's' ?></span>
                <?php if ($row['done'] > 0): ?>
                    <span class="badge ok">Submitted</span>
                <?php else: ?>
                    <a class="btn btn-sm" href="submit_feedback.php?form_id=<?= $row['id'] ?>">Give feedback</a>
                <?php endif; ?>
            </div>
        </article>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</main>
</body>
</html>
