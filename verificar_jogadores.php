<?php
session_start();
include 'db_connection.php';

// Verificar se o número de jogadores mudou
$sql = "SELECT COUNT(*) as count FROM jogando";
$result = $conn->query($sql);
$currentCount = $result->fetch_assoc()['count'];

$response = ['shouldRefresh' => false];

// Verifique se a contagem mudou
if (isset($_SESSION['player_count'])) {
    if ($_SESSION['player_count'] != $currentCount) {
        $response['shouldRefresh'] = true;
    }
} 

// Atualize a contagem na sessão
$_SESSION['player_count'] = $currentCount;

echo json_encode($response);

$conn->close();
?>
