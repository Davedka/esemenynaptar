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

            // Remember me
            if (isset($_POST["remember"])) {
                $token = bin2hex(random_bytes(32));
                $expires = date("Y-m-d H:i:s", strtotime("+30 days"));

                $pdo->prepare("UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?")
                    ->execute([$token, $expires, $user["id"]]);

                setcookie("remember_token", $token, strtotime("+30 days"), "/", "", false, true);
            }

            header("Location: dashboard.php");
            exit;
        }
    } else {
        $error = "Hibás felhasználónév vagy jelszó!";
    }
}

// Remember me cookie ellenőrzés
if (!isset($_SESSION["user_id"]) && isset($_COOKIE["remember_token"])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND remember_expires > NOW()");
    $stmt->execute([$_COOKIE["remember_token"]]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["fullname"] = $user["fullname"];
        header("Location: dashboard.php");
        exit;
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
        width: 240px;
        height: 240px;
        border-radius: 50%;
        overflow: hidden;
        display: inline-block;
        box-shadow: 0 0 12px #00e5cc, 0 0 24px #00e5cc44;
        display: flex;
        justify-content: center;
        align-items: center;">
        <img src="favicon.png" style="width:100%; height:100%; object-fit:cover; display:block;"/>
    </div>
    <h2>Bejelentkezés</h2>
    <p class="subtitle">MSZC Gépészeti – Eseménynaptár</p>
    <?php if (isset($error)): ?>
      <div class="msg-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <input name="fullname" placeholder="Teljes név" required autocomplete="name">
      <input name="password" type="password" placeholder="Jelszó" required autocomplete="current-password">

      <!-- Remember me -->
      <label style="display:flex;align-items:center;gap:10px;margin:10px 2px;cursor:pointer;font-size:14px;color:rgba(255,255,255,.55);">
        <input type="checkbox" name="remember" style="
            width:16px;
            height:16px;
            margin:0;
            accent-color:var(--cyan);
            cursor:pointer;
        ">
        Emlékezz rám 30 napig
      </label>

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
