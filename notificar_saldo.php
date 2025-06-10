<?php
include 'db_connection.php';

session_start();
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $saldo = $data['saldo'];
    $limite = 30000000;

    // Verifique se o usuário já foi notificado
    $sql = "SELECT balance, notificado FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $balance = $row['balance'];
        $notificado = $row['notificado'];
    }
    $stmt->close();

    if ($saldo >= $limite && !$notificado) {
        // Obter o nickname do usuário atual
        $sqlNickname = "SELECT nickname FROM users WHERE id = ?";
        $stmtNickname = $conn->prepare($sqlNickname);
        $stmtNickname->bind_param("i", $userId);
        $stmtNickname->execute();
        $resultNickname = $stmtNickname->get_result();
        $nickname = '';
        if ($row = $resultNickname->fetch_assoc()) {
            $nickname = $row['nickname'];
        }
        $stmtNickname->close();

        // Enviar notificação para todos os usuários
        $sqlUsers = "SELECT id FROM users";
        $resultUsers = $conn->query($sqlUsers);
        $users = [];
        while ($row = $resultUsers->fetch_assoc()) {
            $users[] = $row;
        }

        foreach ($users as $user) {
			enviarNotificacao("Um jogador atingiu R$ 30.000.000.", $user['id'], 'rico', $conn);
        }

        // Marcar como notificado
        $sqlUpdateNotificado = "UPDATE users SET notificado = TRUE WHERE id = ?";
        $stmtUpdateNotificado = $conn->prepare($sqlUpdateNotificado);
        $stmtUpdateNotificado->bind_param("i", $userId);
        $stmtUpdateNotificado->execute();
        $stmtUpdateNotificado->close();

        echo json_encode(['success' => true]);
    } elseif ($saldo < $limite && $notificado) {
        // Reset notificado quando o saldo cai abaixo do limite
        $sqlUpdateNotificado = "UPDATE users SET notificado = FALSE WHERE id = ?";
        $stmtUpdateNotificado = $conn->prepare($sqlUpdateNotificado);
        $stmtUpdateNotificado->bind_param("i", $userId);
        $stmtUpdateNotificado->execute();
        $stmtUpdateNotificado->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma ação necessária.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}

function enviarNotificacao($mensagem, $userId, $tipo, $conn) {
    $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Erro na preparação da consulta INSERT INTO notifications: ' . $conn->error);
    }
    $stmt->bind_param("iss", $userId, $mensagem, $tipo);
    $stmt->execute();
    $stmt->close();
}
?>
