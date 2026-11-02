<?php
session_start();
include '../config/db.php';

// FIX: page had no access control - anyone could create forms
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');
    
    // 1. Insert Form
    $stmt = $conn->prepare("INSERT INTO forms (title, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $title, $description);
    $stmt->execute();
    $form_id = $conn->insert_id;
    $stmt->close();

    // 2. Insert Questions
    if (!empty($_POST['questions'])) {
        foreach ($_POST['questions'] as $question) {
            if (trim($question) != "") {
                $stmt = $conn->prepare("INSERT INTO questions (form_id, question_text) VALUES (?, ?)");
                $stmt->bind_param("is", $form_id, $question);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    $_SESSION['flash'] = 'Form created successfully.';
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>New form | Feedback Hub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
</head>
<body>
<header class="topbar"><div class="topbar-inner">
    <a class="brand" href="dashboard.php"><span class="brand-mark"></span>Feedback Hub</a>
    <nav class="topnav">
        <a href="dashboard.php" class="">Forms</a>
        <a href="create_form.php" class="active">New form</a>
    </nav>
    <div class="userbox">
        <span class="avatar"><?= htmlspecialchars(strtoupper(substr($_SESSION['username'], 0, 1))) ?></span>
        <span class="uname"><?= htmlspecialchars($_SESSION['username']) ?></span>
        <a class="btn btn-ghost btn-sm" href="../logout.php">Log out</a>
    </div>
</div></header>
<main class="page narrow">
    <a class="back" href="dashboard.php">&larr; Back to forms</a>
    <div class="page-head"><div><h1>Create a feedback form</h1><p>Give it a title and add the questions people will answer.</p></div></div>

    <form method="POST" action="" class="card">
        <label for="title" style="margin-top:0;">Form title</label>
        <input type="text" id="title" name="title" required placeholder="e.g. Course feedback">

        <label for="description">Description <span class="hint">(optional)</span></label>
        <textarea id="description" name="description" placeholder="Tell people what this form is for"></textarea>

        <h3 style="margin-top:26px;">Questions</h3>
        <div id="questions-container" class="q-list" style="margin-top:10px;">
            <input type="text" name="questions[]" placeholder="Question 1" required>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="addQuestion()">+ Add question</button>

        <div class="btn-row">
            <button type="submit" class="btn">Save form</button>
            <a class="btn btn-outline" href="dashboard.php">Cancel</a>
        </div>
    </form>
</main>
<script>
    let qCount = 1;
    function addQuestion() {
        qCount++;
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'questions[]';
        input.placeholder = 'Question ' + qCount;
        document.getElementById('questions-container').appendChild(input);
        input.focus();
    }
</script>
</body>
</html>
