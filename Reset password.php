<?php
ob_start();
require "config.php";

$error = "";
$message = "";
$token = $_GET["token"] ?? "";

// Validate token
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "Érvénytelen vagy lejárt link. Kérj új visszaállító emailt.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $reset) {
    $password = $_POST["password"];
    $confirm  = $_POST["confirm_password"];

    if (strlen($password) < 6) {
        $error = "A jelszónak legalább 6 karakter hosszúnak kell lennie!";
    } elseif ($password !== $confirm) {
        $error = "A két jelszó nem egyezik!";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?")
            ->execute([$hash, $reset["email"]]);

        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")
            ->execute([$reset["email"]]);

        $message = "Jelszavad sikeresen megváltozott! <a href='login.php'>Bejelentkezés</a>";
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Új jelszó beállítása</h2>
    <?php if ($error): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($message): ?>
        <p style="color:green"><?= $message ?></p>
    <?php elseif ($reset): ?>
    <form method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="password" name="password" placeholder="Új jelszó" required>
        <input type="password" name="confirm_password" placeholder="Jelszó megerősítése" required>
        <button>Jelszó mentése</button>
    </form>
    <?php endif; ?>
    <p style="text-align:center; margin-top:15px;">
        <a href="login.php">← Vissza a bejelentkezéshez</a>
    </p>
</div>
