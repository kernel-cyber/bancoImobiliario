<?php
ob_start();
header('Content-Type: application/json');
session_start();
require_once 'db_connection.php';

// Configurar depuração
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Função de log para depuração
function logPoupanca($message) {
    $logFile = 'logs/poupanca.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

if (!isset($_SESSION['user_id'])) {
    logPoupanca("Erro: Usuário não autenticado.");
    echo json_encode(['error' => 'Usuário não autenticado.']);
    ob_end_flush();
    exit;
}

$action = $_POST['action'] ?? '';
$userId = (int)$_SESSION['user_id'];
logPoupanca("Requisição recebida: action=$action, userId=$userId, POST=" . json_encode($_POST));

switch ($action) {
    case 'criar':
        criarPoupanca($conn, $userId, $_POST['valor'] ?? 0);
        break;
    case 'consultar':
        consultarPoupanca($conn, $userId);
        break;
    case 'sacar':
        sacarPoupanca($conn, $userId, $_POST['poupanca_id'] ?? 0);
        break;
    case 'notificar':
        notificarRendimento($conn);
        break;
    default:
        logPoupanca("Erro: Ação inválida ($action).");
        echo json_encode(['error' => 'Ação inválida.']);
}

ob_end_flush();

function criarPoupanca($conn, $userId, $valor) {
    $valor = floatval($valor);
    logPoupanca("Criando poupança: userId=$userId, valor=$valor");

    $sql = "SELECT balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        logPoupanca("Erro ao preparar query de saldo: " . $conn->error);
        echo json_encode(['error' => 'Erro interno no servidor.']);
        return;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        logPoupanca("Erro: Usuário não encontrado (userId=$userId).");
        echo json_encode(['error' => 'Usuário não encontrado.']);
        $stmt->close();
        return;
    }
    $user = $result->fetch_assoc();
    $stmt->close();
    if ($user['balance'] < $valor) {
        logPoupanca("Erro: Saldo insuficiente (balance={$user['balance']}, valor=$valor).");
        echo json_encode(['error' => 'Saldo insuficiente.']);
        return;
    }

    if ($valor < 5000000 || $valor > 10000000) {
        logPoupanca("Erro: Valor fora do intervalo (valor=$valor).");
        echo json_encode(['error' => 'Valor deve ser entre R$5.000.000,00 e R$10.000.000,00.']);
        return;
    }

    $sql = "SELECT COUNT(*) as count FROM poupanca WHERE user_id = ? AND status = 'ativa'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();
    if ($count > 0) {
        logPoupanca("Erro: Usuário já possui poupança ativa (userId=$userId).");
        echo json_encode(['error' => 'Você já possui uma poupança ativa.']);
        return;
    }

    $conn->begin_transaction();
    try {
        $sql = "INSERT INTO poupanca (user_id, valor_aplicado, valor_atual, data_aplicacao, ultima_atualizacao) VALUES (?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idd", $userId, $valor, $valor);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao inserir poupança: " . $stmt->error);
        }
        $stmt->close();

        $sql = "UPDATE users SET balance = balance - ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $valor, $userId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar saldo: " . $stmt->error);
        }
        $stmt->close();

        $mensagem = 'Aplicação na Poupança: R$' . number_format($valor, 0, '.', ',');
        $sql = "INSERT INTO transactions (user_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $mensagem);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar transação: " . $stmt->error);
        }
        $stmt->close();

        $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'poupanca')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $mensagem);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar notificação: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        logPoupanca("Poupança criada com sucesso: userId=$userId, valor=$valor");
        echo json_encode(['success' => 'Poupança criada com sucesso!']);
    } catch (Exception $e) {
        $conn->rollback();
        logPoupanca("Erro ao criar poupança: " . $e->getMessage());
        echo json_encode(['error' => 'Erro ao criar poupança: ' . $e->getMessage()]);
    }
}

function consultarPoupanca($conn, $userId) {
    $sql = "SELECT id, valor_aplicado, valor_atual, data_aplicacao, status,
                   TIMESTAMPDIFF(SECOND, data_aplicacao, NOW()) AS segundos_passados
            FROM poupanca
            WHERE user_id = ? AND status = 'ativa'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        logPoupanca("Erro ao preparar query de consulta: " . $conn->error);
        echo json_encode(['error' => 'Erro interno no servidor.']);
        return;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $poupanca = $result->fetch_assoc();
        $poupanca['pode_sacar'] = $poupanca['segundos_passados'] >= 600;
        logPoupanca("Poupança encontrada: userId=$userId, id={$poupanca['id']}, valor_atual={$poupanca['valor_atual']}");
        echo json_encode($poupanca);
    } else {
        logPoupanca("Nenhuma poupança ativa encontrada: userId=$userId");
        echo json_encode(['error' => 'Nenhuma poupança ativa encontrada.']);
    }
    $stmt->close();
}

function sacarPoupanca($conn, $userId, $poupancaId) {
    $poupancaId = (int)$poupancaId;
    logPoupanca("Sacando poupança: userId=$userId, poupancaId=$poupancaId");

    if ($poupancaId <= 0) {
        logPoupanca("Erro: poupancaId inválido ($poupancaId).");
        echo json_encode(['error' => 'ID da poupança inválido.']);
        return;
    }

    $sql = "SELECT valor_aplicado, valor_atual, data_aplicacao, status
            FROM poupanca
            WHERE id = ? AND user_id = ? AND status = 'ativa'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        logPoupanca("Erro ao preparar query de saque: " . $conn->error);
        echo json_encode(['error' => 'Erro interno no servidor.']);
        return;
    }
    $stmt->bind_param("ii", $poupancaId, $userId);
    if (!$stmt->execute()) {
        logPoupanca("Erro ao executar query de saque: " . $stmt->error);
        echo json_encode(['error' => 'Erro interno no servidor.']);
        $stmt->close();
        return;
    }
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        logPoupanca("Erro: Poupança não encontrada ou não ativa (poupancaId=$poupancaId, userId=$userId).");
        echo json_encode(['error' => 'Poupança não encontrada ou não ativa.']);
        $stmt->close();
        return;
    }

    $poupanca = $result->fetch_assoc();
    $stmt->close();

    $segundos = (int)strtotime('now') - strtotime($poupanca['data_aplicacao']);
    $valorSaque = $segundos >= 600 ? $poupanca['valor_atual'] : $poupanca['valor_aplicado'] * 0.75;
    $penalidade = $segundos < 600 ? $poupanca['valor_aplicado'] * 0.25 : 0;
    $status = $segundos < 600 ? 'penalizada' : 'sacada';
    $mensagem = $segundos < 600 ? 'Saque com Penalidade (25%): R$' . number_format($valorSaque, 0, '.', ',') : 'Saque da Poupança: R$' . number_format($valorSaque, 0, '.', ',');

    $conn->begin_transaction();
    try {
        $sql = "UPDATE poupanca SET status = ?, valor_atual = ?, ultima_atualizacao = NOW() WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar atualização da poupança: " . $conn->error);
        }
        $stmt->bind_param("sdii", $status, $valorSaque, $poupancaId, $userId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar poupança: " . $stmt->error);
        }
        $stmt->close();

        $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar atualização do saldo: " . $conn->error);
        }
        $stmt->bind_param("di", $valorSaque, $userId);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar saldo: " . $stmt->error);
        }
        $stmt->close();

        $sql = "INSERT INTO transactions (user_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar transação: " . $conn->error);
        }
        $stmt->bind_param("is", $userId, $mensagem);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar transação: " . $stmt->error);
        }
        $stmt->close();

        $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'poupanca')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar notificação: " . $conn->error);
        }
        $stmt->bind_param("is", $userId, $mensagem);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar notificação: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();
        logPoupanca("Saque realizado: userId=$userId, poupancaId=$poupancaId, valorSaque=$valorSaque, penalidade=$penalidade");
        echo json_encode([
            'success' => 'Saque realizado com sucesso!',
            'saldo' => getUpdatedBalance($conn, $userId),
            'valor_saque' => $valorSaque,
            'penalidade' => $penalidade
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        logPoupanca("Erro ao sacar poupança: " . $e->getMessage());
        echo json_encode(['error' => 'Erro ao sacar poupança: ' . $e->getMessage()]);
    }
}

function notificarRendimento($conn) {
    logPoupanca("Iniciando notificação de rendimentos");
    $sql = "SELECT user_id, valor_aplicado, valor_atual, data_aplicacao,
                   TIMESTAMPDIFF(SECOND, data_aplicacao, NOW()) AS segundos_passados
            FROM poupanca
            WHERE status = 'ativa'";
    $result = $conn->query($sql);
    if ($result === FALSE) {
        logPoupanca("Erro na consulta: " . $conn->error);
        echo json_encode(['error' => 'Erro na consulta: ' . $conn->error]);
        return;
    }

    while ($poupanca = $result->fetch_assoc()) {
        $userId = (int)$poupanca['user_id'];
        $segundos = (int)$poupanca['segundos_passados'];
        $rendimento = $poupanca['valor_atual'] - $poupanca['valor_aplicado'];

        $sql = "SELECT COUNT(*) as count FROM notifications
                WHERE user_id = ? AND type = 'poupanca_rendimento'
                AND created_at > NOW() - INTERVAL 4 MINUTE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();
        if ($count > 0) {
            logPoupanca("Notificação ignorada: já enviada recentemente (userId=$userId)");
            continue;
        }

        $mensagem = "Sua poupança rendeu R$" . number_format($rendimento, 0, '.', ',') . " até agora.";
        if ($segundos >= 600) {
            $mensagem .= " Você pode sacar sem penalidade!";
        }

        $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'poupanca_rendimento')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userId, $mensagem);
        if (!$stmt->execute()) {
            logPoupanca("Erro ao registrar notificação: " . $stmt->error);
        } else {
            logPoupanca("Notificação enviada: userId=$userId, mensagem=$mensagem");
        }
        $stmt->close();
    }
    echo json_encode(['success' => 'Notificações enviadas.']);
}

function getUpdatedBalance($conn, $userId) {
    $sql = "SELECT balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $balance = number_format($row['balance'], 0, '.', ',');
        $stmt->close();
        return $balance;
    }
    $stmt->close();
    return '0';
}
?>