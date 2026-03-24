<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?")
            ->execute([$token, $expires, $user["id"]]);

        $resetLink = "http://" . $_SERVER["HTTP_HOST"] . "/reset_password.php?token=" . $token;

        $apiKey = getenv("BREVO_API_KEY"); // ← Brevo API kulcs

        $data = [
            "sender" => ["name" => "MSZC Eseménynaptár", "email" => "tamasdavidg@gmail.com"],
            "to" => [["email" => $user["email"], "name" => $user["fullname"]]],
            "subject" => "Jelszó visszaállítás",
            "htmlContent" => "
                <h3>Jelszó visszaállítás</h3>
                <p>Kattints az alábbi linkre a jelszavad visszaállításához:</p>
                <a href='$resetLink'>$resetLink</a>
                <p>A link 1 óráig érvényes.</p>
                <p>Ha nem te kérted, hagyd figyelmen kívül ezt az emailt.</p>
            "
        ];

        $ch = curl_init("https://api.brevo.com/v3/smtp/email");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "api-key: $apiKey"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 201) {
            $success = "Az emailt elküldtük a(z) " . $user["email"] . " címre!";
        } else {
            $error = "Email küldési hiba: " . $response;
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
