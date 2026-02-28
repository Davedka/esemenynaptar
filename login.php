<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST["username"]]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST["password"], $user["password_hash"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        header("Location: dashboard.php");
    } else {
        $error = "Hibás adatok!";
    }
}
?>

<link rel="stylesheet" href="style.css">

<div class="container">
    <h2>Bejelentkezés</h2>

    <?php if(isset($error)) echo "<p>$error</p>"; ?>

    <form method="post">
        <input name="username" placeholder="Felhasználónév" required>
        <input type="password" name="password" placeholder="Jelszó" required>
        <button>Belépés</button>
    </form>

    <p style="text-align:center;margin-top:15px;">
        Nincs fiókod? <a href="register.php">Regisztráció</a>
    </p>
</div>