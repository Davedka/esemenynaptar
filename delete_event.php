<?php
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$id = $_GET["id"];

$stmt = $pdo->prepare("UPDATE events SET is_deleted = TRUE WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION["user_id"]]);

header("Location: dashboard.php");