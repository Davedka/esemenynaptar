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

<head>
<meta charset="UTF-8">
<title>Eseménynaptár</title>

<link rel="icon" type="image/png" href="/favicon.png">
<link rel="stylesheet" href="/style.css">
</head>






