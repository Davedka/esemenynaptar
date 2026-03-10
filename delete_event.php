<?php
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$id = $_GET["id"] ?? null;

if (!$id) {
    header("Location: dashboard.php");
    exit;
}

// Csak a saját eseményét törölheti
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION["user_id"]]);
$event = $stmt->fetch();

if (!$event) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo->prepare("UPDATE events SET is_deleted = TRUE WHERE id = ? AND user_id = ?")
        ->execute([$id, $_SESSION["user_id"]]);
    header("Location: dashboard.php");
    exit;
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Esemény törlése</h2>
    <p>Biztosan törlöd ezt az eseményt: <strong><?= htmlspecialchars($event["title"]) ?></strong>?</p>
    <form method="post">
        <button style="background:red;color:white;width:100%">Igen, törlöm</button>
    </form>
    <a href="dashboard.php">
        <button style="width:100%;margin-top:10px">Mégsem</button>
    </a>
</div>
