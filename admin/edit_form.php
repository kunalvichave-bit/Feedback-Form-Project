<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$form_id = (int)($_GET['form_id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM forms WHERE id=?");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
if (!$form) { die("Form not found."); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description'] ?? '');

    $stmt = $conn->prepare("UPDATE forms SET title=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $title, $description, $form_id);
    $stmt->execute();
    $stmt->close();

    // Update existing questions (keyed by question id; empty text = delete)
    foreach (($_POST['existing'] ?? []) as $qid => $text) {
        $qid = (int)$qid;
        $text = trim($text);
        if ($text === "") {
            $s = $conn->prepare("DELETE FROM questions WHERE id=? AND form_id=?");
            $s->bind_param("ii", $qid, $form_id);
        } else {
            $s = $conn->prepare("UPDATE questions SET question_text=? WHERE id=? AND form_id=?");
            $s->bind_param("sii", $text, $qid, $form_id);
        }
        $s->execute();
        $s->close();
    }

    // Add new questions
    foreach (($_POST['questions'] ?? []) as $text) {
        if (trim($text) != "") {
            $s = $conn->prepare("INSERT INTO questions (form_id, question_text) VALUES (?, ?)");
            $s->bind_param("is", $form_id, $text);
            $s->execute();
            $s->close();
        }
    }
    $_SESSION['flash'] = 'Form updated.';
    header("Location: dashboard.php");
    exit();
}

$qs = $conn->query("SELECT * FROM questions WHERE form_id = $form_id ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit form | Feedback Hub</title>
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
    <div class="page-head"><div><h1>Edit form</h1><p>Change the details or questions. Clear a question to delete it.</p></div></div>

    <form method="POST" class="card">
        <label for="title" style="margin-top:0;">Form title</label>
        <input type="text" id="title" name="title" value="<?= htmlspecialchars($form['title']) ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description"><?= htmlspecialchars($form['description']) ?></textarea>

        <h3 style="margin-top:26px;">Questions</h3>
        <div class="q-list" style="margin-top:10px;">
            <?php while ($q = $qs->fetch_assoc()): ?>
                <input type="text" name="existing[<?= $q['id'] ?>]" value="<?= htmlspecialchars($q['question_text']) ?>">
            <?php endwhile; ?>
            <div id="new-questions"></div>
        </div>
        <button type="button" class="btn btn-outline btn-sm" onclick="addQuestion()">+ Add question</button>

        <div class="btn-row">
            <button type="submit" class="btn">Save changes</button>
            <a class="btn btn-outline" href="dashboard.php">Cancel</a>
        </div>
    </form>
</main>
<script>
    function addQuestion() {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'questions[]';
        input.placeholder = 'New question';
        document.getElementById('new-questions').appendChild(input);
        input.focus();
    }
</script>
</body>
</html>
