<?php
session_start();
include '../config/db.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$form_id = (int)($_GET['form_id'] ?? 0);   // FIX: cast -> no SQL injection
$user_id = (int)$_SESSION['user_id'];

// Fetch form details (FIX: handle a form that does not exist)
$form_query = $conn->query("SELECT * FROM forms WHERE id = $form_id");
$form = $form_query->fetch_assoc();
if (!$form) {
    header("Location: dashboard.php");
    exit();
}

// Check if user already submitted this form
$check_submit = $conn->query("SELECT id FROM responses WHERE form_id = $form_id AND user_id = $user_id");
if ($check_submit->num_rows > 0) {
    $_SESSION['flash'] = 'You have already submitted feedback for this form.';
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();
    try {
        // 1. Create a master response record
        $stmt = $conn->prepare("INSERT INTO responses (form_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $form_id, $user_id);
        $stmt->execute();
        $response_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert individual answers (only for questions that belong to THIS form)
        $stmt = $conn->prepare(
            "INSERT INTO answers (response_id, question_id, answer_text)
             SELECT ?, id, ? FROM questions WHERE id = ? AND form_id = ?"
        );
        foreach (($_POST['answers'] ?? []) as $question_id => $answer) {
            $question_id = (int)$question_id;
            $answer = trim($answer);
            $stmt->bind_param("isii", $response_id, $answer, $question_id, $form_id);
            $stmt->execute();
        }
        $stmt->close();
        $conn->commit();
    } catch (Throwable $ex) {
        $conn->rollback();
        echo "<script>alert('Could not save feedback. Please try again.'); window.location='dashboard.php';</script>";
        exit();
    }

    $_SESSION['flash'] = 'Thank you! Your feedback was submitted.';
    header("Location: dashboard.php");
    exit(); // FIX: stop here instead of re-rendering the form
}

// Fetch questions
$questions = $conn->query("SELECT * FROM questions WHERE form_id = $form_id ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Give feedback | Feedback Hub</title>
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
<main class="page narrow">
    <a class="back" href="dashboard.php">&larr; Back to forms</a>
    <div class="page-head">
        <div>
            <h1><?= htmlspecialchars($form['title']) ?></h1>
            <?php if (!empty($form['description'])): ?><p><?= htmlspecialchars($form['description']) ?></p><?php endif; ?>
        </div>
    </div>

    <?php if ($questions->num_rows == 0): ?>
        <div class="card empty"><h3>No questions yet</h3><p>This form has no questions. Please check back later.</p></div>
    <?php else: ?>
    <form method="POST" action="">
        <?php $n = 1; while ($q = $questions->fetch_assoc()): ?>
        <div class="question">
            <label for="q<?= $q['id'] ?>"><span class="q-num"><?= $n++ ?></span><?= htmlspecialchars($q['question_text']) ?></label>
            <textarea id="q<?= $q['id'] ?>" name="answers[<?= $q['id'] ?>]" rows="3" required placeholder="Type your answer"></textarea>
        </div>
        <?php endwhile; ?>
        <div class="btn-row">
            <button type="submit" class="btn">Submit feedback</button>
            <a class="btn btn-outline" href="dashboard.php">Cancel</a>
        </div>
    </form>
    <?php endif; ?>
</main>
</body>
</html>
