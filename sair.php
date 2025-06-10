<?php
session_start();
include 'db_connection.php'; // Supondo que este arquivo tenha a conexão com o banco de dados

// Verifique se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html'); // Redireciona para login se não estiver logado
    exit;
}

// Obter o ID do usuário atual
$userId = $_SESSION['user_id'];

// Verificar se há partidas em andamento
$sqlCheckPartidas = "SELECT COUNT(*) as count FROM jogando";
$resultCheckPartidas = $conn->query($sqlCheckPartidas);
$rowCheckPartidas = $resultCheckPartidas->fetch_assoc();

if ($rowCheckPartidas['count'] < 1) {
    echo "<script>
        alert('Não há partidas em andamento.');
        setTimeout(function() {
            window.location.href = 'dashboard.php';
        }, 1000); // Redireciona após 1 segundo
    </script>";
    exit;
}

// Verificar se o usuário já abandonou a partida
$sqlCheckAbandonou = "SELECT abandonou FROM users WHERE id = ?";
$stmtCheckAbandonou = $conn->prepare($sqlCheckAbandonou);
$stmtCheckAbandonou->bind_param("i", $userId);
$stmtCheckAbandonou->execute();
$resultCheckAbandonou = $stmtCheckAbandonou->get_result();
if ($rowCheckAbandonou = $resultCheckAbandonou->fetch_assoc()) {
    if ($rowCheckAbandonou['abandonou'] == 1) {
        echo "<script>
            alert('Você já abandonou a partida!');
            setTimeout(function() {
                window.location.href = 'dashboard.php';
            }, 1000); // Redireciona após 1 segundo
        </script>";
        exit;
    }
}
$stmtCheckAbandonou->close();

// Função para enviar notificações
function enviarNotificacao($mensagem, $userId, $tipo) {
    global $conn;
    $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Erro na preparação da consulta INSERT INTO notifications: ' . $conn->error);
    }
    $stmt->bind_param("iss", $userId, $mensagem, $tipo);
    $stmt->execute();
}

// Função para enviar notificações para todos os usuários
function enviarNotificacaoParaTodos($mensagem, $tipo) {
    global $conn;
    $sql = "SELECT id FROM users";
    $result = $conn->query($sql);
    if ($result === false) {
        die('Erro na consulta SELECT id FROM users: ' . $conn->error);
    }
    while ($row = $result->fetch_assoc()) {
        enviarNotificacao($mensagem, $row['id'], $tipo);
    }
}

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

$mensagem = "$nickname abandonou a partida.";
enviarNotificacaoParaTodos($mensagem, 'warning');

// Remover o jogador da tabela jogando
$sqlDelete = "DELETE FROM jogando WHERE userId = ?";
$stmtDelete = $conn->prepare($sqlDelete);
$stmtDelete->bind_param("i", $userId);
if ($stmtDelete->execute()) {
    // Remoção bem-sucedida
} else {
    $mensagem = "Erro ao parar de jogar: " . $conn->error;
}
$stmtDelete->close();

// Atualizar os saldos dos usuários para 15 milhões
$sqlUpdateSaldo = "UPDATE users SET balance = 0 WHERE id = ?";
$stmtUpdateSaldo = $conn->prepare($sqlUpdateSaldo);
$stmtUpdateSaldo->bind_param("i", $userId);
if ($stmtUpdateSaldo->execute()) {
    $mensagemSaldo = "Sucesso.";
} else {
    $mensagemSaldo = "Erro ao atualizar saldo: " . $conn->error;
}
$stmtUpdateSaldo->close();

// Limpar as notificações do banco de dados
$sqlLimparNotificacoes = "DELETE FROM notifications WHERE user_id = ?";
$stmtLimparNotificacoes = $conn->prepare($sqlLimparNotificacoes);
$stmtLimparNotificacoes->bind_param("i", $userId);
if ($stmtLimparNotificacoes->execute()) {
    $mensagemNotificacoes = "Notificações do banco de dados limpas.";
} else {
    $mensagemNotificacoes = "Erro ao limpar as notificações do banco de dados: " . $conn->error;
}
$stmtLimparNotificacoes->close();

// Atualizar o campo 'abandonou' para 1
$sqlUpdateAbandonou = "UPDATE users SET abandonou = 1 WHERE id = ?";
$stmtUpdateAbandonou = $conn->prepare($sqlUpdateAbandonou);
$stmtUpdateAbandonou->bind_param("i", $userId);
if ($stmtUpdateAbandonou->execute()) {
    $mensagemAbandonou = "Status de abandono atualizado.";
} else {
    $mensagemAbandonou = "Erro ao atualizar status de abandono: " . $conn->error;
}
$stmtUpdateAbandonou->close();

$conn->close();

// Redirecionar de volta para o dashboard ou outra página de sua escolha com um atraso
echo "<script>
    alert('Você abandonou a partida!');
    setTimeout(function() {
        window.location.href = 'dashboard.php';
    }, 1000); // Redireciona após 1 segundo
</script>";
exit;
?>
