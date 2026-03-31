<?php
session_start();
$host = "aws-1-eu-west-1.pooler.supabase.com";
$port = "6543";
$dbname = "postgres";
$user = "postgres.aywstgoezgowqqkrjoqq";
$password = "7kMbzylV6hfkHzmr";
try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Adatbázis hiba: " . $e->getMessage());
}

// Remember me ellenőrzés
if (!isset($_SESSION["user_id"]) && isset($_COOKIE["remember_token"])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND remember_expires > NOW()");
    $stmt->execute([$_COOKIE["remember_token"]]);
    $rememberUser = $stmt->fetch();

    if ($rememberUser) {
        $_SESSION["user_id"] = $rememberUser["id"];
        $_SESSION["fullname"] = $rememberUser["fullname"];
    }
}
