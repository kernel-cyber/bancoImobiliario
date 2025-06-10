<?php
session_start();
include 'db_connection.php'; // Supondo que este arquivo tenha a conexão com o banco de dados

// Verifique se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redireciona para login se não estiver logado
    exit;
}

// Obter o ID do usuário atual
$userId = $_SESSION['user_id'];

// Consultar o nickname, o status de abandono e o ícone do usuário atual
$sql = "SELECT nickname, abandonou, icone FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $nickname = $row['nickname'];
    $abandonou = $row['abandonou'];
    $icone = $row['icone'];
} else {
    $nickname = "Usuário";
    $abandonou = 0; // Defina um valor padrão se o usuário não for encontrado
    $icone = null;
}
$stmt->close();

// Verificar se o usuário abandonou a partida
if ($abandonou == 1) {
    echo "<script>alert('Você não pode entrar! Aguarde uma nova partida.');</script>";
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit;
}

// Verificar se o jogador já está na tabela 'jogando'
$sqlCheck = "SELECT COUNT(*) as count FROM jogando WHERE userId = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $userId);
$stmtCheck->execute();
$resultCheck = $stmtCheck->get_result();
$rowCheck = $resultCheck->fetch_assoc();

// Atualizar o status do jogo para 0 (não resetado)
$sqlUpdateGameStatus = "UPDATE game_status SET reset_status = 0 WHERE id = 1";
$conn->query($sqlUpdateGameStatus);

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

function enviarNotificacaoParaTodos($mensagem, $tipo) {
    global $conn;
    $sql = "SELECT id FROM users";
    $result = $conn->query($sql);
    if ($result === false) {
        die('Erro na consulta SELECT id FROM users: ' . $conn->error);
    }
    while ($row = $result->fetch_assoc()) {
        if ($row['id'] != $_SESSION['user_id']) { // Certifique-se de que não está enviando para o usuário atual novamente
            enviarNotificacao($mensagem, $row['id'], $tipo);
        }
    }
}

// Definir os ícones possíveis
$icons = [
    'fa-cash-register',
    'fa-sack-dollar',
    'fa-briefcase',
    'fa-database',
    'fa-dollar-sign',
    'fa-piggy-bank'
];

// Verificar se o ícone já está definido
if (is_null($icone)) {
    // Obter ícones já em uso
    $sqlUsedIcons = "SELECT icone FROM users WHERE icone IS NOT NULL";
    $resultUsedIcons = $conn->query($sqlUsedIcons);
    $usedIcons = [];
    while ($rowUsedIcon = $resultUsedIcons->fetch_assoc()) {
        $usedIcons[] = $rowUsedIcon['icone'];
    }

    // Filtrar ícones disponíveis
    $availableIcons = array_diff($icons, $usedIcons);

    // Sortear um ícone aleatório dos disponíveis
    if (!empty($availableIcons)) {
        $randomIcon = $availableIcons[array_rand($availableIcons)];

        // Atualizar a tabela `users` com o ícone sorteado
        $sqlUpdateIcon = "UPDATE users SET icone = ? WHERE id = ?";
        $stmtUpdateIcon = $conn->prepare($sqlUpdateIcon);
        $stmtUpdateIcon->bind_param("si", $randomIcon, $userId);
        $stmtUpdateIcon->execute();
        $stmtUpdateIcon->close();

        // Atualizar a variável $icone com o novo ícone sorteado
        $icone = $randomIcon;
    } else {
        echo "<script>alert('Não há ícones disponíveis.');</script>";
        echo "<script>window.location.href = 'dashboard.php';</script>";
        exit;
    }
}

if ($rowCheck['count'] == 0) {
    // Escolher um objetivo aleatório não atribuído
    $sqlObjetivo = "SELECT * FROM objetivos WHERE assigned = FALSE ORDER BY RAND() LIMIT 1";
    $stmtObjetivo = $conn->prepare($sqlObjetivo);
    $stmtObjetivo->execute();
    $resultObjetivo = $stmtObjetivo->get_result();
    $objetivo = $resultObjetivo->fetch_assoc();
    $stmtObjetivo->close();

    if ($objetivo) {
        // Marcar o objetivo como atribuído
        $sqlUpdate = "UPDATE objetivos SET assigned = TRUE WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("i", $objetivo['id']);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        // Armazenar o ID do objetivo atribuído ao usuário
        $sqlSaveObjective = "INSERT INTO jogando (userId, nickname, objetivoId) VALUES (?, ?, ?)";
        $stmtSaveObjective = $conn->prepare($sqlSaveObjective);
        $stmtSaveObjective->bind_param("isi", $userId, $nickname, $objetivo['id']);
        if ($stmtSaveObjective->execute()) {
            enviarNotificacaoParaTodos("$nickname entrou na partida.", 'join');
        } else {
            $mensagem = "Erro ao iniciar o jogo: " . $conn->error;
        }
        $stmtSaveObjective->close();
    }
} else {
    // O jogador já está na partida, redirecionar para outra página
    echo "<script>window.location.href = 'banco.php';</script>";
}

$stmtCheck->close();
$conn->close();

// Redirecionar para a página apropriada
header('Location: banco.php');
exit;
?>
