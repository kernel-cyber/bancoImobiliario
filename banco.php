<?php
session_start();
include 'db_connection.php';

$mensagem = "";
$saldoAtual = 0;
$nickname = "";

// Verifique se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redireciona para login se não estiver logado
    exit;
}
$userId = $_SESSION['user_id'];
// Verificar se o usuário está na tabela 'jogando'
$sqlCheckPlaying = "SELECT 1 FROM jogando WHERE userid = ?";
$stmtCheckPlaying = $conn->prepare($sqlCheckPlaying);
$stmtCheckPlaying->bind_param("i", $userId);
$stmtCheckPlaying->execute();
$resultCheckPlaying = $stmtCheckPlaying->get_result();

if ($resultCheckPlaying->num_rows === 0) {
    // Redirecionar para outra página se o usuário não estiver na tabela 'jogando'
    echo "<script>alert('Você não está em uma partida. Por favor, entre em uma partida primeiro.'); location.href='dashboard.php';</script>";
    exit;
}

$stmtCheckPlaying->close();

// Obter saldo e nickname do usuário atual

$sql = "SELECT balance, nickname FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Erro na preparação da consulta SELECT balance, nickname: ' . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $saldoAtual = $row['balance'];
    $nickname = $row['nickname'];
}

// Obter lista de todos os usuários
$users = [];
$sql = "SELECT id, nickname FROM users";
$result = $conn->query($sql);
if ($result === false) {
    die('Erro na consulta SELECT id, nickname FROM users: ' . $conn->error);
}
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}


// Consultar o nickname e o status de abandono do usuário atual
$sql = "SELECT nickname, abandonou FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $nickname = $row['nickname'];
    $abandonou = $row['abandonou'];
} else {
    $nickname = "Usuário";
    $abandonou = 0; // Defina um valor padrão se o usuário não for encontrado
}
$stmt->close();

// Verificar se o usuário abandonou a partida
if ($abandonou == 1) {
    echo "<script>alert('Você não pode entrar, terá que aguardar uma nova partida.');</script>";
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit;
}

// Obter lista de todos os que estão jogando
$jogadores = [];
$sql = "SELECT userId, nickname FROM jogando";
$result = $conn->query($sql);
if ($result === false) {
    die('Erro na consulta SELECT userId, nickname FROM jogando: ' . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $jogadores[] = $row;
}

// Obter lista de objetivos
$objetivos = [];
$sql = "SELECT objetivoId FROM jogando WHERE userId = ?";
$stmtObjetivoId = $conn->prepare($sql);
$stmtObjetivoId->bind_param("i", $userId);
$stmtObjetivoId->execute();
$resultObjetivoId = $stmtObjetivoId->get_result();

if ($resultObjetivoId === false) {
    die('Erro na consulta SELECT objetivoId FROM jogando WHERE userId: ' . $conn->error);
}

$objetivoId = $resultObjetivoId->fetch_assoc()['objetivoId'];

$stmtObjetivoId->close();

if ($objetivoId !== null) {
    $sql = "SELECT nome_objetivo, objetivo1, objetivo2, objetivo3, objetivo4 FROM objetivos WHERE id = ?";
    $stmtObjetivos = $conn->prepare($sql);
    $stmtObjetivos->bind_param("i", $objetivoId);
    $stmtObjetivos->execute();
    $resultObjetivos = $stmtObjetivos->get_result();

    if ($resultObjetivos === false) {
        die('Erro na consulta SELECT objetivo1, objetivo2, objetivo3, objetivo4 FROM objetivos WHERE id: ' . $conn->error);
    }

    while ($row = $resultObjetivos->fetch_assoc()) {
        $objetivos[] = $row;
    }

    $stmtObjetivos->close();
}


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

// Função para obter o saldo atualizado do usuário
function getUpdatedBalance($userId) {
    global $conn;
    $sql = "SELECT balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Erro na preparação da consulta SELECT balance: ' . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['balance'];
    }
    return 0;
}


// Preparar a consulta para obter o ícone do usuário atual
$sqlIcon = "SELECT icone FROM users WHERE id = ?";
$stmtIcon = $conn->prepare($sqlIcon);
$stmtIcon->bind_param("i", $userId);
$stmtIcon->execute();
$resultIcon = $stmtIcon->get_result();

if ($rowIcon = $resultIcon->fetch_assoc()) {
    $icone = $rowIcon['icone'];
} else {
    // Defina um ícone padrão se não houver ícone encontrado
    $icone = 'fa-user';
}

$stmtIcon->close();

// Função para registrar transações no arquivo de log e no banco de dados
function registrarTransacao($userId, $mensagem) {
    global $conn;

    // Registrar no arquivo de log
    $arquivoLog = 'transacoes.log';
    $dataHora = date('d/m/Y H:i:s');
    $mensagemFormatada = "[$dataHora] $mensagem\n";
    file_put_contents($arquivoLog, $mensagemFormatada, FILE_APPEND);

    // Registrar no banco de dados
    $sql = "INSERT INTO transactions (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Erro na preparação da consulta INSERT INTO transactions: ' . $conn->error);
    }
    $stmt->bind_param("is", $userId, $mensagem);
    $stmt->execute();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['adicionar_2m_usuario_atual'])) {
        $valorAdicionar = 2000000; // 2M
        $sql = "SELECT last_reward_time FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            die('Erro na preparação da consulta SELECT last_reward_time: ' . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($lastRewardTime);
        $stmt->fetch();
        $stmt->close();

        $canReceiveReward = true;
        $currentTime = new DateTime();
        if ($lastRewardTime) {
            $lastRewardDateTime = new DateTime($lastRewardTime);
            $interval = $currentTime->getTimestamp() - $lastRewardDateTime->getTimestamp();
            if ($interval < 10) { // 5 segundos
                $canReceiveReward = false;
            }
        }

        if ($canReceiveReward) {
            $sql = "UPDATE users SET balance = balance + ?, last_reward_time = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die('Erro na preparação da consulta UPDATE users SET balance: ' . $conn->error);
            }
            $stmt->bind_param("dsi", $valorAdicionar, $currentTime->format('Y-m-d H:i:s'), $userId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $mensagem = "Você recebeu R$ " . number_format($valorAdicionar, 0, ',', '.') . " do banco.";
                enviarNotificacao($mensagem, $userId, 'success');
                registrarTransacao($userId, "$nickname recebeu R$ " . number_format($valorAdicionar, 0, ',', '.') . " do banco.");

                // Enviar notificação para todos os outros usuários
                $sqlUsers = "SELECT id FROM users WHERE id != ?";
                $stmtUsers = $conn->prepare($sqlUsers);
                $stmtUsers->bind_param("i", $userId);
                $stmtUsers->execute();
                $resultUsers = $stmtUsers->get_result();
                
                while ($rowUsers = $resultUsers->fetch_assoc()) {
                    enviarNotificacao("$nickname recebeu R$ " . number_format($valorAdicionar, 0, ',', '.') . " do banco.", $rowUsers['id'], 'transaction');
                }
                $stmtUsers->close();
            }

            echo json_encode([
                'mensagem' => $mensagem,
                'saldoAtual' => number_format(getUpdatedBalance($userId), 0, ',', '.'),
                'status' => 'success'
            ]);
        } else {
            $mensagem = "Espere 5 segundos antes de receber o prêmio novamente.";
            echo json_encode([
                'mensagem' => $mensagem,
                'saldoAtual' => number_format(getUpdatedBalance($userId), 0, ',', '.'),
                'status' => 'alert'
            ]);
        }
        exit;
    }


    if (isset($_POST['valor']) && isset($_POST['destinatario'])) {
        $valor = intval(str_replace(['R$', '.', ','], ['', '', ''], $_POST['valor']));  // Remove símbolos e pontos, converte a entrada em inteiro
        $destinatarioId = intval($_POST['destinatario']);
        $tipoOperacao = isset($_POST['tipoOperacao']) ? $_POST['tipoOperacao'] : '';

        if ($valor < 10000) {
            $mensagem = "O valor mínimo para transação é de R$10.000.";
            enviarNotificacao($mensagem, $userId, 'error');
        } elseif ($tipoOperacao === 'pagar' && $valor > $saldoAtual) {
            $mensagem = "Saldo insuficiente para realizar esta transação.";
            enviarNotificacao($mensagem, $userId, 'error');
        } else {
            if ($destinatarioId === 0) { // Operações com o banco
    if ($tipoOperacao === 'pagar') {
        $novoSaldo = $saldoAtual - $valor;
        if ($novoSaldo < 0) {
            $mensagem = "Saldo insuficiente para realizar esta transação.";
            enviarNotificacao($mensagem, $userId, 'error');
            echo json_encode([
                'mensagem' => $mensagem,
                'saldoAtual' => number_format(getUpdatedBalance($userId), 0, ',', '.'),
                'status' => 'error'
            ]);
            exit;
        }
        $sql = "UPDATE users SET balance = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $novoSaldo, $userId);
    } elseif ($tipoOperacao === 'receber') {
        $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $valor, $userId);
    } else {
        $mensagem = "Selecione uma operação válida (pagar ou receber).";
        enviarNotificacao($mensagem, $userId, 'error');
        echo json_encode([
            'mensagem' => $mensagem,
            'saldoAtual' => number_format(getUpdatedBalance($userId), 0, ',', '.'),
            'status' => 'error'
        ]);
        exit;
    }

    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $mensagem = ($tipoOperacao === 'pagar') ?
            "Você pagou R$ " . number_format($valor, 0, ',', '.') . " ao banco." :
            "Você recebeu R$ " . number_format($valor, 0, ',', '.') . " do banco.";
        $saldoAtual = getUpdatedBalance($userId);
        enviarNotificacao($mensagem, $userId, 'success');
        registrarTransacao($userId, ($tipoOperacao === 'pagar') ?
            "$nickname pagou R$ " . number_format($valor, 0, ',', '.') . " ao banco." :
            "$nickname recebeu R$ " . number_format($valor, 0, ',', '.') . " do banco.");
		foreach ($users as $user) {
                if ($user['id'] != $userId) {
					if ($tipoOperacao === 'pagar') {
                    enviarNotificacao("$nickname pagou R$ " . number_format($valor, 0, ',', '.') . " ao banco.", $user['id'], 'success');
					} else {
						enviarNotificacao("$nickname recebeu R$ " . number_format($valor, 0, ',', '.') . " do banco.", $user['id'], 'success');
					}
                }
            }
    } else {
        $mensagem = "Erro ao processar a transação.";
        enviarNotificacao($mensagem, $userId, 'error');
    }
}
 else { // Transferência para outro jogador
                $conn->begin_transaction();

                try {
                    // Verificar se o saldo será suficiente para a transferência
                    if ($saldoAtual < $valor) {
                        throw new Exception("Saldo insuficiente para realizar esta transação.");
                    }

                    $sql = "UPDATE users SET balance = balance - ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        throw new Exception('Erro na preparação da consulta UPDATE users SET balance: ' . $conn->error);
                    }
                    $stmt->bind_param("di", $valor, $userId);
                    $stmt->execute();

                    $sql = "UPDATE users SET balance = balance + ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        throw new Exception('Erro na preparação da consulta UPDATE users SET balance: ' . $conn->error);
                    }
                    $stmt->bind_param("di", $valor, $destinatarioId);
                    $stmt->execute();

                    $conn->commit();
                    $mensagem = "Você realizou um PIX de R$ " . number_format($valor, 0, ',', '.') . " para " . getNicknameById($destinatarioId) . ".";
                    $saldoAtual = getUpdatedBalance($userId);
                    enviarNotificacao($mensagem, $userId, 'success'); // Notificação de sucesso para o usuário que realiza a transação
                    registrarTransacao($userId, "$nickname realizou um PIX de R$ " . number_format($valor, 0, ',', '.') . " para " . getNicknameById($destinatarioId) . ".");

                    foreach ($users as $user) {
                        if ($user['id'] != $userId && $user['id'] != $destinatarioId) {
                            enviarNotificacao("$nickname realizou um PIX de R$ " . number_format($valor, 0, ',', '.') . " para " . getNicknameById($destinatarioId) . ".", $user['id'], 'notification');
                        } elseif ($user['id'] == $destinatarioId) {
                            enviarNotificacao("$nickname realizou um PIX de R$ " . number_format($valor, 0, ',', '.') . " para você.", $user['id'], 'transaction');
							registrarTransacao($destinatarioId, getNicknameById($destinatarioId) . " recebeu um PIX de R$ " . number_format($valor, 0, ',', '.') . " de " . $nickname . ".");
                        }
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $mensagem = $e->getMessage();
                    enviarNotificacao($mensagem, $userId, 'error');
                }
            }
        }

        echo json_encode([
            'mensagem' => $mensagem,
            'saldoAtual' => number_format(getUpdatedBalance($userId), 0, ',', '.'),
            'status' => ($tipoOperacao === 'error' || $valor < 10000 || ($valor > $saldoAtual && $tipoOperacao === 'pagar')) ? 'error' : 'success'
        ]);
        exit;
    }
}

function getNicknameById($id) {
    global $conn;
    $sql = "SELECT nickname FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die('Erro na preparação da consulta SELECT nickname FROM users: ' . $conn->error);
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['nickname'];
    }
    return null;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4a99debbd4.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="estilo.css">
	<link rel="stylesheet" href="dashboard.css">
    <title>Banco Imobiliário</title>
</head>
<body>
<body style="background-color:#000000;">
</body>
    <div class="container">	
	<?php
foreach ($objetivos as $objetivo) {
    // Verificar se há algum objetivo definido
    if (!empty($objetivo['objetivo1']) || !empty($objetivo['objetivo2']) || !empty($objetivo['objetivo3']) || !empty($objetivo['objetivo4'])) {
        // Exibir o nome do objetivo
        echo '<b>~</b> [<font color="purple"><tr><td colspan="4" style="text-align: center; font-weight: bold;"> <b>' . $objetivo['nome_objetivo'] . '</b></font> ] <b>~</b></td></tr><br>';

        // Exibir os detalhes do objetivo
        echo '<tr>';

        // Verificar se o objetivo1 contém a palavra-chave "ação" ou "conj."
        if (!empty($objetivo['objetivo1'])) {
            if (stripos($objetivo['objetivo1'], "ação") !== false || stripos($objetivo['objetivo1'], "ações")  !== false) {
                echo '<td><i class="fa-solid fa-money-bill-trend-up"></i> ' . $objetivo['objetivo1'] . ' </td>';
            } elseif (stripos($objetivo['objetivo1'], "conj.") !== false) {
                echo '<td><i class="fa-solid fa-city"></i> ' . $objetivo['objetivo1'] . ' </td>';
            } else {
                echo '<td><i class="fa-solid fa-house-chimney"></i> ' . $objetivo['objetivo1'] . ' </td>';
            }
        }

        // Verificar se o objetivo2 contém a palavra-chave "casas", "hotéis" ou "ações"
        if (!empty($objetivo['objetivo2'])) {
            if (stripos($objetivo['objetivo2'], "casas") !== false) {
                echo '<td><i class="fa-solid fa-house-chimney"></i> ' . $objetivo['objetivo2'] . ' </td>';
            } elseif (stripos($objetivo['objetivo2'], "hotéis") !== false || stripos($objetivo['objetivo2'], "hotel") !== false) {
                echo '<td><i class="fa-solid fa-hotel"></i> ' . $objetivo['objetivo2'] . ' </td>';
            } elseif (stripos($objetivo['objetivo2'], "ação") !== false || stripos($objetivo['objetivo2'], "ações") !== false) {
                echo '<td><i class="fa-solid fa-money-bill-trend-up"></i> ' . $objetivo['objetivo2'] . ' </td>';
            } 
        }

        // Verificar se o objetivo3 contém a palavra-chave "ações"
        if (!empty($objetivo['objetivo3']) && stripos($objetivo['objetivo3'], "ações") !== false) {
            echo '<td><i class="fa-solid fa-money-bill-trend-up"></i> ' . $objetivo['objetivo3'] . ' </td>';
        } elseif (!empty($objetivo['objetivo3']) && stripos($objetivo['objetivo3'], "casas") !== false) {
			echo '<td><i class="fa-solid fa-house-chimney"></i> ' . $objetivo['objetivo3'] . ' </td>';
        } elseif (!empty($objetivo['objetivo3']) && stripos($objetivo['objetivo3'], "casas") !== false) {
			echo '<td><i class="fa-solid fa-hotel"></i> ' . $objetivo['objetivo3'] . ' </td>';
		}

        // Verificar se o objetivo4 está definido e não está vazio
    }
}


?>
<hr class="separator">
<h2>
    <hr class="separator">
    <span class="nickname" id="nickname">
        <i class="fa-solid <?= $icone; ?> icon-gradient"></i> <?= $nickname; ?>
    </span>
    <span id="containerSaldo">
        <i class="fa-solid fa-money-check-dollar icon-gradient2"></i>
        <span id="saldoAtual">R$ <?= number_format($saldoAtual, 0, ',', '.'); ?></span>
        <i id="toggleVisibility" class="fa-solid fa-eye icon-gradient" onclick="toggleSaldo();"></i>
    </span>
</h2>




        <?php if (!empty($mensagem)): ?>
            <p class="alert"><?= $mensagem; ?></p>
        <?php endif; ?>
        <form id="transacaoForm" method="post">
            <input type="text" id="valorTransacao" name="valor" placeholder="R$" readonly>
            <select name="destinatario" id="destinatario" onchange="mostrarOpcoesBanco()">
                <option value="0">Banco</option>
                <?php foreach ($jogadores as $jogador): ?>
                    <?php if ($jogador['userId'] != $userId): ?>
                        <option value="<?= $jogador['userId'] ?>"> <?= $jogador['nickname'] ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <div id="opcoesBanco">
                <label><input type="radio" name="tipoOperacao" value="pagar"> <b><font color="red" size="4"><i class="fa-solid fa-hand-holding-dollar"></i> PAGAR</font></b></label>
                <label><input type="radio" name="tipoOperacao" value="receber"> <b><font color="green" size="4"><i class="fa-solid fa-hand-holding-medical"></i> RECEBER</font></b></label>
            </div><br>
            <div class="teclado">
                <button type="button" class="btn-m" onclick="usarM()">M</button>
                <button type="button" class="btn-arrow" onclick="adicionar2M()"><i class="fa-solid fa-sack-dollar"></i> (2M)</button>
                <button type="button" class="btn-k" onclick="usarK()">K</button>
                <button type="button" onclick="digitar('7')">7</button>
                <button type="button" onclick="digitar('8')">8</button>
                <button type="button" onclick="digitar('9')">9</button>
                <button type="button" onclick="digitar('4')">4</button>
                <button type="button" onclick="digitar('5')">5</button>
                <button type="button" onclick="digitar('6')">6</button>
                <button type="button" onclick="digitar('1')">1</button>
                <button type="button" onclick="digitar('2')">2</button>
                <button type="button" onclick="digitar('3')">3</button>
                <button type="button" class="btn-clear" onclick="apagar()"><i class="fa-solid fa-delete-left"></i></button>
                <button type="button" onclick="digitar('0')">0</button>
                <button type="button" onclick="digitar('.')"><font size="4"><b>.</b></font></button>
                <button type="button" class="btn-back" onclick="voltarDashboard()"><i class="fa-solid fa-circle-arrow-left"></i> Voltar</button>
                <button type="button" class="btn-extrato" onclick="extrato()"><i class="fa-solid fa-file-invoice-dollar"></i> Extrato</button>
                <button type="submit" class="btn-pagar"><i class="fa-solid fa-circle-check"></i> OK</button>
            </div>
        </form>
    </div>
    <div id="notificacao" class="notificacao"></div>
    <script src="scripts.js"></script>
</body>
</html>
