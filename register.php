<?php
require "config.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST["password"];
    $passwordError = "";
    // Jelszó validáció
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
        // Ellenőrzés: foglalt-e már az email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$_POST["email"]]);
        if ($check->fetch()) {
            $error = "Ez az email cím már foglalt!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users 
                (fullname, email, password_hash, role, school)
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST["fullname"],
                $_POST["email"],
                password_hash($password, PASSWORD_DEFAULT),
                $_POST["role"],
                $_POST["school"]
            ]);
            header("Location: login.php");
            exit;
        }
    }
}
?>
<link rel="stylesheet" href="style.css">
<div class="container">
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
</div>
