<?php
session_start();
include 'db_connection.php';

// Verificar se o jogo foi resetado
$sql = "SELECT reset_status FROM game_status WHERE id = 1";
$result = $conn->query($sql);
$gameReset = $result->fetch_assoc()['reset_status'];

// Responder com o status do reset do jogo
echo json_encode(['gameReset' => (int)$gameReset]);

$conn->close();
?>
