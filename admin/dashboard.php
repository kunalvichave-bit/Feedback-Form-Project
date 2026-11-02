<?php
session_start();
include '../config/db.php';

// Security check: Ensure only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Handle Delete Form
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM forms WHERE id=?"); // cascades to questions/responses
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: dashboard.php");
    exit(); // FIX: was missing, script kept running
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$totalForms = $conn->query("SELECT COUNT(*) c FROM forms")->fetch_assoc()['c'];
$totalResponses = $conn->query("SELECT COUNT(*) c FROM responses")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) c FROM users WHERE role='user'")->fetch_assoc()['c'];

$forms = $conn->query("SELECT f.*,
    (SELECT COUNT(*) FROM questions q WHERE q.form_id = f.id) AS q_count,
    (SELECT COUNT(*) FROM responses r WHERE r.form_id = f.id) AS resp_count
    FROM forms f ORDER BY f.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin dashboard | Feedback Hub</title>
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
<main class="page">
    <div class="page-head">
        <div>
            <h1>Feedback forms</h1>
            <p>Create forms, edit questions and review responses.</p>
        </div>
        <a class="btn" href="create_form.php">+ New form</a>
    </div>

    <?php if ($flash): ?><div class="alert success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>

    <div class="stats">
        <div class="stat"><div class="num"><?= (int)$totalForms ?></div><div class="lbl">Forms created</div></div>
        <div class="stat"><div class="num"><?= (int)$totalResponses ?></div><div class="lbl">Responses received</div></div>
        <div class="stat"><div class="num"><?= (int)$totalUsers ?></div><div class="lbl">Registered users</div></div>
    </div>

    <div class="card" style="padding:0;">
        <?php if ($forms->num_rows == 0): ?>
            <div class="empty">
                <h3>No forms yet</h3>
                <p>Create your first feedback form to start collecting responses.</p>
                <a class="btn" href="create_form.php">Create a form</a>
            </div>
        <?php else: ?>
        <div class="table-wrap">
        <table>
            <thead><tr>
                <th>Form</th><th>Questions</th><th>Responses</th><th>Created</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php while ($row = $forms->fetch_assoc()): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($row['title']) ?></strong>
                    <div class="muted"><?= htmlspecialchars($row['description']) ?></div>
                </td>
                <td><?= (int)$row['q_count'] ?></td>
                <td><span class="badge <?= $row['resp_count'] > 0 ? 'ok' : '' ?>"><?= (int)$row['resp_count'] ?></span></td>
                <td class="muted"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td>
                    <div class="actions">
                        <a class="btn btn-outline btn-sm" href="view_responses.php?form_id=<?= $row['id'] ?>">Responses</a>
                        <a class="btn btn-outline btn-sm" href="edit_form.php?form_id=<?= $row['id'] ?>">Edit</a>
                        <a class="btn btn-danger btn-sm" href="dashboard.php?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this form and all its responses?');">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
