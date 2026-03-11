<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Token generálás
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
            ->execute([$token, $expires, $user["id"]]);

        // Link kiírása (email küldés helyett, mivel nincs SMTP beállítva)
        $resetLink = "http://" . $_SERVER["HTTP_HOST"] . "/reset_password.php?token=" . $token;
        $success = $resetLink;
    } else {
        $error = "Nem található ilyen email cím!";
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Elfelejtett jelszó</h2>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <?php if(isset($success)): ?>
        <p style="color:green">Jelszó visszaállító link:</p>
        <a href="<?= $success ?>"><?= $success ?></a>
    <?php endif; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Email cím" required>
        <button>Link küldése</button>
    </form>
    <p style="text-align:center;margin-top:15px;">
        <a href="login.php">Vissza a bejelentkezéshez</a>
    </p>
</div>
