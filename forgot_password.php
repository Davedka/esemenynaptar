<?php
require "config.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require "vendor/autoload.php";

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

        $resetLink = "http://" . $_SERVER["HTTP_HOST"] . "/reset_password.php?token=" . $token;

        // Email küldés PHPMailer-rel
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = "smtp.gmail.com";      // SMTP szerver
            $mail->SMTPAuth   = true;
            $mail->Username   = "te_emailed@gmail.com"; // ← cseréld ki
            $mail->Password   = "app_jelszo";           // ← cseréld ki (lásd lent)
            $mail->SMTPSecure = "tls";
            $mail->Port       = 587;

            $mail->setFrom("te_emailed@gmail.com", "MSZC Eseménynaptár");
            $mail->addAddress($user["email"], $user["fullname"]);

            $mail->isHTML(true);
            $mail->Subject = "Jelszó visszaállítás";
            $mail->Body    = "
                <h3>Jelszó visszaállítás</h3>
                <p>Kattints az alábbi linkre a jelszavad visszaállításához:</p>
                <a href='$resetLink'>$resetLink</a>
                <p>A link 1 óráig érvényes.</p>
                <p>Ha nem te kérted, hagyd figyelmen kívül ezt az emailt.</p>
            ";

            $mail->send();
            $success = "Az emailt elküldtük a(z) $email címre!";

        } catch (Exception $e) {
            $error = "Email küldési hiba: " . $mail->ErrorInfo;
        }

    } else {
        $error = "Nem található ilyen email cím!";
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
    <h2>Elfelejtett jelszó</h2>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <?php if(isset($success)) echo "<p style='color:green'>$success</p>"; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Email cím" required>
        <button>Link küldése</button>
    </form>
    <p style="text-align:center;margin-top:15px;">
        <a href="login.php">Vissza a bejelentkezéshez</a>
    </p>
</div>
