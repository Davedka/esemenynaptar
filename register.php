<?php
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $stmt = $pdo->prepare("INSERT INTO users 
        (fullname, username, email, password_hash, role, school)
        VALUES (?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $_POST["fullname"],
        $_POST["username"],
        $_POST["email"],
        password_hash($_POST["password"], PASSWORD_DEFAULT),
        $_POST["role"],
        $_POST["school"]
    ]);

    header("Location: login.php");
}
?>

<link rel="stylesheet" href="style.css">

<div class="container">
    <h2>Regisztráció</h2>

    <form method="post">
        <input name="fullname" placeholder="Teljes név" required>
        <input name="username" placeholder="Felhasználónév" required>
        <input name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Jelszó" required>

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