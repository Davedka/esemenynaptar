<?php
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Először töröljük az eseményeit
    $pdo->prepare("DELETE FROM events WHERE user_id = ?")
        ->execute([$_SESSION["user_id"]]);

    // Majd töröljük a felhasználót
    $pdo->prepare("DELETE FROM users WHERE id = ?")
        ->execute([$_SESSION["user_id"]]);

    // Kijelentkeztetés
    session_destroy();
    header("Location: login.php");
    exit;
}
?>

<head>
<?php include "head.php"; ?>
</head>

<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Profil törlése</h2>
    <p style="color:red">Biztosan törlöd a fiókodat? Ez a művelet nem visszavonható!</p>
    <form method="post">
        <button style="background:red;color:white;width:100%">Igen, törlöm a fiókom</button>
    </form>
    <a href="dashboard.php">
        <button style="width:100%;margin-top:10px">Mégsem</button>
    </a>
</div>
