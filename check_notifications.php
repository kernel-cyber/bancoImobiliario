<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

$userId = $_SESSION['user_id'];

$sql = "SELECT message, type FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Erro na preparação da consulta SELECT message, type: ' . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = ['message' => $row['message'], 'type' => $row['type']];
}

// Obter saldo atualizado do usuário
$sql = "SELECT balance FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Erro na preparação da consulta SELECT balance: ' . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$balance = 0;
if ($row = $result->fetch_assoc()) {
    $balance = number_format($row['balance'], 0, ',', '.');
}

// Limpar notificações após envio para evitar duplicação
$sql = "DELETE FROM notifications WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Erro na preparação da consulta DELETE FROM notifications: ' . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();

echo json_encode(['notifications' => $notifications, 'balance' => $balance]);
?>
