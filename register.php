<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST["verify_code"])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND verify_token = ?");
        $stmt->execute([$_POST["email"], $_POST["verify_code"]]);
        $user = $stmt->fetch();

        if ($user) {
            $pdo->prepare("UPDATE users SET verified = TRUE, verify_token = NULL WHERE id = ?")
                ->execute([$user["id"]]);
            header("Location: login.php");
            exit;
        } else {
            $verifyError = "Hibás kód!";
            $showVerify = true;
            $verifyEmail = $_POST["email"];
        }

    } else {
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
            $check = $pdo->prepare("SELECT id FROM users WHERE fullname = ? OR email = ?");
            $check->execute([$_POST["fullname"], $_POST["email"]]);

            if ($check->fetch()) {
                $error = "Ez a teljes név vagy email már foglalt!";
            } else {
                $code = strval(random_int(100000, 999999));

                $stmt = $pdo->prepare("INSERT INTO users 
                    (fullname, email, password_hash, role, school, verified, verify_token)
                    VALUES (?, ?, ?, ?, ?, FALSE, ?)");
                $stmt->execute([
                    $_POST["fullname"],
                    $_POST["email"],
                    password_hash($password, PASSWORD_DEFAULT),
                    $_POST["role"],
                    $_POST["school"],
                    $code
                ]);

                $apiKey = getenv("BREVO_API_KEY");
                $data = [
                    "sender" => ["name" => "MSZC Eseménynaptár", "email" => "tamasdavidg@gmail.com"],
                    "to" => [["email" => $_POST["email"], "name" => $_POST["fullname"]]],
                    "subject" => "Email megerősítés",
                    "htmlContent" => "
                        <h3>Regisztráció megerősítése</h3>
                        <p>A megerősítő kódod:</p>
                        <h1 style='letter-spacing:5px'>$code</h1>
                        <p>Írd be ezt a kódot a regisztrációs oldalon.</p>
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
                    $showVerify = true;
                    $verifyEmail = $_POST["email"];
                } else {
                    $error = "Email küldési hiba: " . $response;
                }
            }
        }
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">

<?php if (isset($showVerify)): ?>
    <h2>Email megerősítés</h2>
    <p>Elküldtük a 6 jegyű kódot a(z) <strong><?= htmlspecialchars($verifyEmail) ?></strong> címre.</p>
    <?php if(isset($verifyError)) echo "<p style='color:red'>$verifyError</p>"; ?>
    <form method="post">
        <input type="hidden" name="email" value="<?= htmlspecialchars($verifyEmail) ?>">
        <input name="verify_code" placeholder="6 jegyű kód" maxlength="6" required>
        <button>Megerősítés</button>
    </form>

<?php else: ?>
    <h2>Regisztráció</h2>
    <?php if(isset($error)) echo "<p style='color:red'>$error</p>"; ?>
    <form method="post">
        <input name="fullname" placeholder="Teljes név" required>
        <input name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Jelszó" required>
        <small style="color:gray">Min. 9 karakter, tartalmazzon számot és speciális karaktert (!@#$% stb.)</small>
        <select name="role">
            <option value="student">Diák</option>
            <option value="teacher">Tanár</option>
        </select>
        <select name="school">
            <option value="MSZC">MSZC</option>
            <option value="SZIC">SZIC</option>
            <option value="KSZC">KSZC</option>
        </select>
        <button>Regisztráció</button>
    </form>
    <p style="text-align:center;margin-top:15px;">
        Van már fiókod? <a href="login.php">Bejelentkezés</a>
    </p>

<?php endif; ?>
</div>
