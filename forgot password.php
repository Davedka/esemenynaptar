<?php
ob_start();
require "config.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Store token in DB (create this table if not exists):
        // CREATE TABLE password_resets (email VARCHAR(255), token VARCHAR(64), expires_at DATETIME);
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$email, $token, $expires]);

        $reset_link = "https://" . $_SERVER["HTTP_HOST"] . "/reset_password.php?token=" . $token;

        // Send email
        $subject = "Jelszó visszaállítás";
        $body = "Kattints az alábbi linkre a jelszavad visszaállításához (1 óráig érvényes):\n\n$reset_link";
        $headers = "From: noreply@" . $_SERVER["HTTP_HOST"];

        mail($email, $subject, $body, $headers);

        $message = "Ha az email cím regisztrált, küldtünk egy visszaállító linket!";
    } else {
        // Same message to avoid user enumeration
        $message = "Ha az email cím regisztrált, küldtünk egy visszaállító linket!";
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Elfelejtett jelszó</h2>
    <?php if ($message): ?>
        <p style="color:green"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Email cím" required>
        <button>Visszaállító link küldése</button>
    </form>
    <p style="text-align:center; margin-top:15px;">
        <a href="login.php">← Vissza a bejelentkezéshez</a>
    </p>
</div>
