<?php
ob_start();
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE fullname = ?");
    $stmt->execute([$_POST["fullname"]]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST["password"], $user["password_hash"])) {
        if (!$user["verified"]) {
            $error = "Erősítsd meg az email címed a regisztrációkor kapott kóddal!";
        } else {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["fullname"] = $user["fullname"];
            header("Location: dashboard.php");
            exit;
        }
    } else {
        $error = "Hibás felhasználónév vagy jelszó!";
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head><?php include "head.php"; ?></head>
<body>

<div class="top-line"></div>
<div class="orb-br"></div>

<div class="container">
  <div class="container-box">

    <div class="verify-icon" style="
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        display: inline-block;
        box-shadow: 0 0 12px #00e5cc, 0 0 24px #00e5cc44;
        ">
            <img src="favicon.png" style="width:100%; height:100%; object-fit:cover; display:block;"
      />
</div>
    <h2>Bejelentkezés</h2>
    <p class="subtitle">MSZC Gépészeti – Eseménynaptár</p>

    <?php if (isset($error)): ?>
      <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input name="fullname"  placeholder="Teljes név" required autocomplete="name">
      <input name="password" type="password" placeholder="Jelszó" required autocomplete="current-password">
      <button>Belépés →</button>
    </form>

    <p style="text-align:center;margin-top:18px;font-size:14px;color:rgba(255,255,255,.4);">
      Nincs fiókod? <a href="register.php">Regisztráció</a>
    </p>
    <p style="text-align:center;margin-top:8px;font-size:13px;">
      <a href="forgot_password.php" style="color:rgba(255,255,255,.3);">Elfelejtett jelszó?</a>
    </p>

  </div>
</div>

</body>
</html>
