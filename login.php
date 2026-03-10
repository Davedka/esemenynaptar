<?php
ob_start();
require "config.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$_POST["email"]]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($_POST["password"], $user["password_hash"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["fullname"] = $user["fullname"];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Hibás adatok!";
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Bejelentkezés</h2>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <form method="post">
        <input name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Jelszó" required>
        <button>Belépés</button>
    </form>
    <p style="text-align:center;margin-top:15px;">
        Nincs fiókod? <a href="register.php">Regisztráció</a>
    </p>
    <p style="text-align:center;">
        <a href="forgot_password.php">Elfelejtett jelszó?</a>
    </p>
</div>

