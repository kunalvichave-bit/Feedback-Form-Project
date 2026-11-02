<?php
session_start();
include '../config/db.php';

// FIX: no login/admin check existed - anyone with the URL could read all answers
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$form_id = (int)($_GET['form_id'] ?? 0); // FIX: cast -> no SQL injection

// 1. Summary Query (Total responses count)
$summary_query = $conn->query("SELECT COUNT(*) as total FROM responses WHERE form_id = $form_id");
$summary = $summary_query->fetch_assoc();

$form = $conn->query("SELECT title FROM forms WHERE id = $form_id")->fetch_assoc();
if (!$form) { die("Form not found."); }

// 2. Detailed responses, grouped by respondent
$sql = "SELECT r.id AS response_id, u.username, q.question_text, a.answer_text, r.submitted_at
        FROM responses r
        JOIN users u ON r.user_id = u.id
        JOIN answers a ON r.id = a.response_id
        JOIN questions q ON a.question_id = q.id
        WHERE r.form_id = $form_id
        ORDER BY r.id DESC, q.id ASC";
$results = $conn->query($sql);
$grouped = [];
while ($row = $results->fetch_assoc()) {
    $rid = $row['response_id'];
    if (!isset($grouped[$rid])) {
        $grouped[$rid] = ['user' => $row['username'], 'date' => $row['submitted_at'], 'answers' => []];
    }
    $grouped[$rid]['answers'][] = ['q' => $row['question_text'], 'a' => $row['answer_text']];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Responses | Feedback Hub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="topbar"><div class="topbar-inner">
    <a class="brand" href="dashboard.php"><span class="brand-mark"></span>Feedback Hub</a>
    <nav class="topnav">
        <a href="dashboard.php" class="active">Forms</a>
        <a href="create_form.php" class="">New form</a>
    </nav>
    <div class="userbox">
        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['username'], 0, 1))) ?></span>
        <span class="uname"><?= htmlspecialchars($_SESSION['username']) ?></span>
        <a class="btn btn-ghost btn-sm" href="../logout.php">Log out</a>
    </div>
</div></header>
<main class="page narrow">
    <a class="back" href="dashboard.php">&larr; Back to forms</a>
    <div class="page-head">
        <div><h1><?= htmlspecialchars($form['title']) ?></h1><p>Responses from your users</p></div>
        <span class="badge ok" style="font-size:.95rem;padding:6px 14px;"><?= (int)$summary['total'] ?> submission<?= $summary['total'] == 1 ? '' : 's' ?></span>
    </div>

    <?php if (empty($grouped)): ?>
        <div class="card empty">
            <h3>No responses yet</h3>
            <p>Answers will show up here once users fill in this form.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($grouped as $resp): ?>
        <section class="resp">
            <div class="resp-head">
                <strong><?= htmlspecialchars($resp['user']) ?></strong>
                <span class="muted"><?= date('d M Y, H:i', strtotime($resp['date'])) ?></span>
            </div>
            <div class="resp-body">
                <?php foreach ($resp['answers'] as $qa): ?>
                    <div class="qa">
                        <div class="q"><?= htmlspecialchars($qa['q']) ?></div>
                        <div class="a"><?= htmlspecialchars($qa['a']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</main>
</body>
</html>
