<?php
session_start();
include 'db_connection.php';

// Supondo que o ID do usuário esteja armazenado na sessão
$userId = $_SESSION['user_id'];

$sql = "SELECT balance FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo number_format($row['balance'], 0, ',', '.'); // Formato brasileiro de moeda
} else {
    echo "0";
}
$conn->close();
?>
