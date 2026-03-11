<?php
require "config.php";

$token = $_GET["token"] ?? "";

// Token ellenőrzés
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    die("<div class='container'><p style='color:red'>Érvénytelen vagy lejárt link!</p><a href='login.php'>Vissza</a></div>");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST["password"];
    $passwordError = "";

    if (strlen($password) < 9) {
        $passwordError = "A jelszónak legalább 9 karakter hosszúnak kell lennie!";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $passwordError = "A jelszónak tartalmaznia kell legalább egy számot!";
    } elseif (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) {
        $passwordError = "A jelszónak tartalmaznia kell legalább egy speciális karaktert!";
    }

    if ($passwordError) {
        $error = $passwordError;
    } else {
        $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?")
            ->execute([password_hash($password, PASSWORD_DEFAULT), $user["id"]]);

        header("Location: login.php");
        exit;
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Új jelszó beállítása</h2>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <form method="post">
        <input type="password" name="password" placeholder="Új jelszó" required>
        <small style="color:gray">Min. 9 karakter, tartalmazzon számot és speciális karaktert</small>
        <button>Jelszó módosítása</button>
    </form>
</div>
