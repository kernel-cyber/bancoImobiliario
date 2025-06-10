<?php
header('Content-Type: application/json');
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

$userId = $conn->real_escape_string($_POST['user_id']);
$sql = "SELECT id, message, type, created_at
        FROM notifications
        WHERE user_id = '$userId' AND `read` = 0
        ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);
$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
    $sql = "UPDATE notifications SET `read` = 1 WHERE id = '{$row['id']}'";
    $conn->query($sql);
}
echo json_encode(['notifications' => $notifications]);
?>