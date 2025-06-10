<?php
session_start();
require_once 'db_connection.php';

// Função de log para depuração
function logReset($message) {
    $logFile = 'logs/reset.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n\n", FILE_APPEND);
}

if (!isset($_SESSION['user_id'])) {
    logReset("Erro: Usuário não autenticado.");
    header('Location: index.html');
    exit;
}

if ($_SESSION['user_id'] != 13) {
    logReset("Erro: Usuário sem permissão (userId={$_SESSION['user_id']}).");
    echo "<script>alert('Você não tem permissão para resetar o jogo.'); location.href='index.php';</script>";
    exit;
}

$conn->begin_transaction();
try {
    // Inicializar mensagens de status
    $sqlSaldo = $sqlNotificacao = $sqlTransacao = $sqlJogador = $sqlObjetivo = $sqlAbandono = $sqlPoupanca = $sqlObjetivoAtual = $sqlIcone = $sqlLastReward = "";

    // Limpar notificações
    $sql = "DELETE FROM notifications";
    if ($conn->query($sql)) {
        logReset("Notificações limpas.");
        $sqlNotificacao = "Notificações do banco de dados limpas.";
    } else {
        throw new Exception("Erro ao limpar notificações: " . $conn->error);
    }

    // Limpar transações
    $sql = "DELETE FROM transactions";
    if ($conn->query($sql)) {
        logReset("Transações limpas.");
        $sqlTransacao = "Transações do banco de dados limpas.";
    } else {
        throw new Exception("Erro ao limpar transações: " . $conn->error);
    }

    // Limpar jogadores
    $sql = "DELETE FROM jogando";
    if ($conn->query($sql)) {
        logReset("Jogadores removidos da partida.");
        $sqlJogador = "Jogadores removidos da partida.";
    } else {
        throw new Exception("Erro ao limpar jogadores: " . $conn->error);
    }

    // Limpar objetivos ativos
    $sql = "UPDATE objetivos SET status = 0 WHERE status = 1";
    if ($conn->query($sql)) {
        logReset("Objetivos removidos da partida.");
        $sqlObjetivo = "Objetivos removidos da partida.";
    } else {
        throw new Exception("Erro ao limpar objetivos: " . $conn->error);
    }

    // Limpar abandonos
    $sql = "UPDATE users SET abandonou = 0 WHERE abandonou = 1";
    if ($conn->query($sql)) {
        logReset("Abandonos resetados.");
        $sqlAbandono = "Abandonos resetados.";
    } else {
        throw new Exception("Erro ao limpar abandonos: " . $conn->error);
    }

    // Consolidar poupanças ativas e atualizar saldos
    $sql = "SELECT user_id, SUM(valor_atual) as total_valor FROM poupanca WHERE status = 'ativa' GROUP BY user_id";
    $result = $conn->query($sql);
    if ($result === FALSE) {
        throw new Exception("Erro ao consultar poupanças ativas: " . $conn->error);
    }

    $poupancaErrors = [];
    while ($poupanca = $result->fetch_assoc()) {
        $userIdPoupanca = (int)$poupanca['user_id'];
        $valorSaque = (float)$poupanca['total_valor'];
        logReset("Processando poupança para userId=$userIdPoupanca, total_valor=$valorSaque");

        // Verificar entrada 'sacada' existente
        $sql = "SELECT id, valor_atual FROM poupanca WHERE user_id = ? AND status = 'sacada'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userIdPoupanca);
        $stmt->execute();
        $existingSacada = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existingSacada) {
            // Atualizar entrada 'sacada' existente
            $newValorAtual = $existingSacada['valor_atual'] + $valorSaque;
            $existingPoupancaId = $existingSacada['id'];
            logReset("Entrada 'sacada' encontrada para userId=$userIdPoupanca. Atualizando poupancaId=$existingPoupancaId com novo valor=$newValorAtual");
            $sql = "UPDATE poupanca SET valor_atual = ?, valor_aplicado = ?, ultima_atualizacao = NOW() WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ddii", $newValorAtual, $newValorAtual, $existingPoupancaId, $userIdPoupanca);
            if (!$stmt->execute()) {
                $poupancaErrors[] = "Erro ao atualizar poupança sacada (poupancaId=$existingPoupancaId): " . $stmt->error;
            }
            $stmt->close();
        } else {
            // Verificar entrada 'penalizada'
            $sql = "SELECT id, valor_atual FROM poupanca WHERE user_id = ? AND status = 'penalizada'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userIdPoupanca);
            $stmt->execute();
            $existingPenalizada = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existingPenalizada) {
                // Atualizar entrada 'penalizada'
                $newValorAtual = $existingPenalizada['valor_atual'] + $valorSaque;
                $existingPoupancaId = $existingPenalizada['id'];
                logReset("Entrada penalizada encontrada para userId=$userIdPoupanca. Atualizando poupancaId=$existingPoupancaId com novo valor=$newValorAtual");
                $sql = "UPDATE poupanca SET valor_atual = ?, valor_aplicado = ?, ultima_atualizacao = NOW() WHERE id = ? AND user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ddii", $newValorAtual, $newValorAtual, $existingPoupancaId, $userIdPoupanca);
                if (!$stmt->execute()) {
                    $poupancaErrors[] = "Erro ao atualizar poupança penalizada (poupancaId=$existingPoupancaId): " . $stmt->error;
                }
                $stmt->close();
            } else {
                // Criar entrada 'sacada'
                $sql = "INSERT INTO poupanca (user_id, valor_aplicado, valor_atual, data_aplicacao, ultima_atualizacao, status) VALUES (?, ?, ?, NOW(), NOW(), 'sacada')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("idd", $userIdPoupanca, $valorSaque, $valorSaque);
                if (!$stmt->execute()) {
                    $poupancaErrors[] = "Erro ao criar nova poupança sacada (userId=$userIdPoupanca): " . $stmt->error;
                }
                $stmt->close();
            }
        }

        // Marcar poupanças ativas como 'sacada'
        $sql = "UPDATE poupanca SET status = 'sacada', ultima_atualizacao = NOW() WHERE user_id = ? AND status = 'ativa'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userIdPoupanca);
        if (!$stmt->execute()) {
            $poupancaErrors[] = "Erro ao marcar poupanças ativas como sacada (userId=$userIdPoupanca): " . $stmt->error;
        }
        $stmt->close();

        // Adicionar poupança ao saldo em uma única query
        $sql = "UPDATE users SET balance = 15000000.00 + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $valorSaque, $userIdPoupanca);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao atualizar saldo (userId=$userIdPoupanca): " . $stmt->error);
        }
        $stmt->close();

        // Log do saldo final
        $sql = "SELECT balance FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userIdPoupanca);
        $stmt->execute();
        $resultBalance = $stmt->get_result()->fetch_assoc();
        logReset("Saldo final para userId=$userIdPoupanca: " . $resultBalance['balance']);
        $stmt->close();

        // Registrar transação
        $mensagem = "Saque da Poupança (Reset): R$" . number_format($valorSaque, 2, '.', ',');
        $sql = "INSERT INTO transactions (user_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userIdPoupanca, $mensagem);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar transação (userId=$userIdPoupanca): " . $stmt->error);
        }
        $stmt->close();

        // Registrar notificação
        $sql = "INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'poupanca')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $userIdPoupanca, $mensagem);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar notificação (userId=$userIdPoupanca): " . $stmt->error);
        }
        $stmt->close();
    }

    // Atualizar saldos dos usuários sem poupanças ativas
    $sql = "UPDATE users SET balance = 15000000.00 WHERE id NOT IN (SELECT user_id FROM poupanca WHERE status = 'ativa')";
    if (!$conn->query($sql)) {
        throw new Exception("Erro ao atualizar saldos de usuários sem poupanças: " . $conn->error);
    }
    logReset("Saldos dos usuários sem poupanças ativas atualizados para 15 milhões.");
    $sqlSaldo = "Saldos dos usuários atualizados para 15 milhões.";

    if (!empty($poupancaErrors)) {
        $sqlPoupanca = "Erros ao processar poupanças: " . implode("; ", $poupancaErrors);
        logReset($sqlPoupanca);
    } else {
        logReset("Poupanças resetadas com sucesso.");
        $sqlPoupanca = "Poupanças resetadas.";
    }

    // Atualizar status do jogo
    $sql = "UPDATE game_status SET reset_status = 1 WHERE id = 1";
    if (!$conn->query($sql)) {
        throw new Exception("Erro ao atualizar status do jogo: " . $conn->error);
    }

    // Resetar notificações de usuários
    $sql = "UPDATE users SET notificado = 0 WHERE notificado = 1";
    if (!$conn->query($sql)) {
        throw new Exception("Erro ao resetar notificações de usuários: " . $conn->error);
    }

    // Resetar objetivos atribuídos
    $sql = "UPDATE objetivos SET assigned = 0 WHERE assigned = 1";
    if ($conn->query($sql)) {
        logReset("Objetivos atribuídos resetados.");
        $sqlObjetivoAtual = "Objetivos atribuídos resetados.";
    } else {
        throw new Exception("Erro ao resetar objetivos atribuídos: " . $conn->error);
    }

    // Resetar ícones
    $sql = "UPDATE users SET icone = NULL WHERE icone IS NOT NULL";
    if ($conn->query($sql)) {
        logReset("Ícones resetados.");
        $sqlIcone = "Ícones restaurados para o padrão.";
    } else {
        throw new Exception("Erro ao resetar ícones: " . $conn->error);
    }

    // Resetar último tempo de recompensa
    $sql = "UPDATE users SET last_reward_time = NULL WHERE last_reward_time IS NOT NULL";
    if ($conn->query($sql)) {
        logReset("Último tempo de recompensa resetado.");
        $sqlLastReward = "Último tempo de recompensa resetado.";
    } else {
        throw new Exception("Erro ao resetar último tempo de recompensa: " . $conn->error);
    }

    $conn->commit();
    logReset("Reset concluído com sucesso.");
} catch (Exception $e) {
    $conn->rollback();
    logReset("Erro durante o reset: " . $e->getMessage());
    $sqlSaldo = $sqlNotificacao = $sqlTransacao = $sqlJogador = $sqlObjetivo = $sqlAbandono = $sqlPoupanca = $sqlObjetivoAtual = $sqlIcone = $sqlLastReward = "Erro durante o reset: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resetar Jogo - Banco Imobiliário</title>
    <script src="https://kit.fontawesome.com/4a99debbd4.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            padding: 15px;
            max-width: 90vw;
            width: 100%;
            margin: 15px;
        }
        .card-header {
            background: linear-gradient(90deg, #28a745, #1e7e34);
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .card-body {
            padding: 15px;
        }
        h1 {
            color: #28a745;
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
        }
        p {
            color: #ccc;
            font-size: 14px;
            margin-bottom: 8px;
            text-align: left;
        }
        .error {
            color: #dc3545;
        }
        .success {
            color: #28a745;
        }
        .btn-primary {
            background: linear-gradient(90deg, #28a745, #1e7e34);
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #1e7e34, #17692b);
        }
        .fa-rotate-left {
            font-size: 18px;
        }
        .error-list {
            background: #2c2a2a;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
            margin-bottom: 15px;
        }
        @media (max-width: 576px) {
            .container {
                padding: 10px;
                margin: 10px;
            }
            .card-header {
                padding: 12px;
                font-size: 16px;
            }
            h1 {
                font-size: 1.3rem;
            }
            p {
                font-size: 13px;
            }
            .btn-primary {
                font-size: 14px;
                padding: 10px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1><i class="fa-solid fa-rotate-left"></i> Jogo Reseteado</h1>
            </div>
            <div class="card-body">
                <?php if (!empty($poupancaErrors)): ?>
                    <div class="error-list">
                        <p class="error">Erros ao processar poupanças:</p>
                        <ul>
                            <?php foreach ($poupancaErrors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <p class="<?php echo strpos($sqlPoupanca, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlPoupanca); ?>
                </p>
                <p class="<?php echo strpos($sqlSaldo, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlSaldo); ?>
                </p>
                <p class="<?php echo strpos($sqlNotificacao, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlNotificacao); ?>
                </p>
                <p class="<?php echo strpos($sqlTransacao, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlTransacao); ?>
                </p>
                <p class="<?php echo strpos($sqlJogador, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlJogador); ?>
                </p>
                <p class="<?php echo strpos($sqlObjetivo, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlObjetivo); ?>
                </p>
                <p class="<?php echo strpos($sqlAbandono, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlAbandono); ?>
                </p>
                <p class="<?php echo strpos($sqlObjetivoAtual, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlObjetivoAtual); ?>
                </p>
                <p class="<?php echo strpos($sqlIcone, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlIcone); ?>
                </p>
                <p class="<?php echo strpos($sqlLastReward, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($sqlLastReward); ?>
                </p>
                <a href="dashboard.php" class="btn btn-primary"><i class="fa-solid fa-rotate-left"></i> Voltar ao Dashboard</a>
            </div>
        </div>
    </div>
    <script>
        // Limpar localStorage e marcar reset
        localStorage.removeItem('saldoPoupanca');
        localStorage.setItem('resetTimestamp', Date.now());
        // Forçar recarregamento da página destino para evitar cache
        window.location.href = 'dashboard.php?nocache=' + Date.now();
    </script>
</body>
</html>