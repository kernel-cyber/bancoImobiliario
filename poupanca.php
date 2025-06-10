<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Verificar se o usuário está na tabela 'jogando'
$sql = "SELECT 1 FROM jogando WHERE userId = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "<script>alert('Você não está em uma partida.'); location.href='dashboard.php';</script>";
    exit;
}
$stmt->close();

// Obter saldo e informações do usuário
$sql = "SELECT balance, nickname, icone FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$saldoAtual = $user['balance'];
$nickname = $user['nickname'];
$icone = $user['icone'] ?: 'fa-user';
$stmt->close();

// Verificar se o usuário abandonou
$sql = "SELECT abandonou FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->fetch_assoc()['abandonou'] == 1) {
    echo "<script>alert('Você abandonou a partida.'); location.href='dashboard.php';</script>";
    exit;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poupança - Banco Imobiliário</title>
    <script src="https://kit.fontawesome.com/4a99debbd4.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="estilo.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 30px auto;
            padding: 15px;
        }
        .card {
            background: #1e1e1e;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }
        .card-header {
            background: linear-gradient(90deg, #28a745, #1e7e34);
            color: #fff;
            font-weight: 600;
            text-align: center;
            padding: 15px;
            border-bottom: none;
        }
        .card-body {
            padding: 20px;
        }
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1.2em;
        }
        .user-info .nickname i, .user-info .saldo i {
            margin-right: 8px;
        }
        .form-control {
            background-color: #2a2a2a;
            color: #fff;
            border: 1px solid #28a745;
            border-radius: 8px;
            text-align: right;
        }
        .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 8px rgba(40, 167, 69, 0.3);
        }
        .btn-value {
            background: #2a2a2a;
            color: #28a745;
            border: 1px solid #28a745;
            border-radius: 8px;
            margin: 5px;
            padding: 8px 12px;
            font-size: 0.9em;
            transition: all 0.2s;
        }
        .btn-value:hover {
            background: #28a745;
            color: #fff;
        }
        .btn-primary {
            background: linear-gradient(90deg, #28a745, #1e7e34);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #1e7e34, #17692b);
        }
        .btn-danger {
            background: linear-gradient(90deg, #dc3545, #c82333);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .btn-danger:hover {
            background: linear-gradient(90deg, #c82333, #bd2130);
        }
        .btn-secondary {
            background: linear-gradient(90deg, #6c757d, #5a6268);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .info-regras, .forecast {
            background: #2a2a2a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .forecast {
            border-left-color: #ffc107;
        }
        .info-regras p, .forecast p {
            margin: 5px 0;
            font-size: 0.9em;
            color: #ccc;
        }
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
        }
        .modal-content {
            background: #1e1e1e;
            color: #fff;
            border-radius: 12px;
        }
        .modal-header {
            border-bottom: 1px solid #28a745;
        }
        .modal-footer {
            border-top: 1px solid #28a745;
        }
        .value-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        @media (max-width: 576px) {
            .container {
                padding: 10px;
            }
            .btn-value {
                padding: 6px 10px;
                font-size: 0.8em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            <span class="nickname">
                <i class="fa-solid <?= htmlspecialchars($icone) ?> icon-gradient"></i> <?= htmlspecialchars($nickname) ?>
            </span>
            <span class="saldo">
                <i class="fa-solid fa-money-check-dollar icon-gradient"></i>
                <span id="saldoAtual">R$ <?= number_format($saldoAtual, 0, ',', '.') ?></span>
                <i id="toggleVisibility" class="fa-solid fa-eye icon-gradient" onclick="toggleSaldo()"></i>
            </span>
        </div>

        <div class="info-regras">
            <h5><i class="fa-solid fa-info-circle"></i> Regras da Poupança</h5>
            <p>- Aplicação: Entre R$5.000.000,00 e R$10.000.000,00.</p>
            <p>- Rendimento: 10% a cada 2 minutos após a aplicação.</p>
            <p>- Saque após 10 minutos: Inclui rendimentos sem penalidade.</p>
            <p>- Saque antes de 10 minutos: Penalidade de 25% sobre o valor aplicado.</p>
        </div>

        <div class="card" id="form-poupanca-card">
            <div class="card-header">
                <i class="fa-solid fa-piggy-bank"></i> Criar Poupança
            </div>
            <div class="card-body">
                <div id="alert-message" class="alert" role="alert" style="display: none;"></div>
                <form id="form-poupanca">
                    <div class="mb-3">
                        <label for="valor" class="form-label">Valor a Aplicar (R$)</label>
                        <input type="number" class="form-control" id="valor" value="5000000" min="5000000" max="10000000" step="100000" required readonly>
                        <div class="value-buttons mt-2">
                            <button type="button" class="btn-value" onclick="adjustValue(100000, 'add')">+100K</button>
                            <button type="button" class="btn-value" onclick="adjustValue(1000000, 'add')">+1M</button>
                            <button type="button" class="btn-value" onclick="adjustValue(10000000, 'add')">+10M</button>
                            <button type="button" class="btn-value" onclick="adjustValue(100000, 'subtract')">-100K</button>
                            <button type="button" class="btn-value" onclick="adjustValue(1000000, 'subtract')">-1M</button>
                            <button type="button" class="btn-value" onclick="adjustValue(10000000, 'subtract')">-10M</button>
                        </div>
                        <small class="form-text text-muted">Entre R$5.000.000,00 e R$10.000.000,00</small>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-circle-check"></i> Aplicar</button>
                </form>
            </div>
        </div>

        <div class="card" id="poupanca-info" style="display: none;">
            <div class="card-header">
                <i class="fa-solid fa-piggy-bank"></i> Sua Poupança
            </div>
            <div class="card-body">
                <p><strong>Valor Aplicado:</strong> <span id="valor-aplicado" class="money-format"></span></p>
                <p><strong>Valor Atual:</strong> <span id="valor-atual" class="money-format"></span></p>
                <p><strong>Data de Aplicação:</strong> <span id="data-aplicacao"></span></p>
                <p><strong>Tempo para Saque sem Penalidade:</strong> <span id="tempo-restante"></span></p>
                <div class="forecast">
                    <h5><i class="fa-solid fa-chart-line"></i> Previsão</h5>
                    <p><strong>Valor após 10 minutos:</strong> <span id="valor-projetado"></span></p>
                    <p><strong>Saque agora:</strong> <span id="valor-saque-agora"></span> (<span id="penalidade-info"></span>)</p>
                </div>
                <div class="button-row">
                    <button id="btn-sacar" class="btn btn-danger"><i class="fa-solid fa-money-bill-withdraw"></i> Sacar</button>
                    <button class="btn btn-secondary" onclick="window.location.href='dashboard.php'"><i class="fa-solid fa-circle-arrow-left"></i> Voltar</button>
                </div>
            </div>
        </div>

        <div class="toast-container"></div>

        <!-- Modal de Confirmação de Saque -->
        <div class="modal fade" id="confirmSaqueModal" tabindex="-1" aria-labelledby="confirmSaqueModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmSaqueModalLabel">Confirmar Saque</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><strong>Valor a sacar:</strong> <span id="modal-valor-saque"></span></p>
                        <p><strong>Penalidade:</strong> <span id="modal-penalidade"></span></p>
                        <p><strong>Saldo após saque:</strong> <span id="modal-saldo-final"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" id="confirmar-saque">Confirmar Saque</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let dataAplicacao = null;
        let poupancaId = null;
        let valorAplicado = 0;
        const saldoAtual = <?= $saldoAtual ?>;

        $(document).ready(function() {
            consultarPoupanca();
            setInterval(consultarPoupanca, 30000);
            setInterval(atualizarTempoRestante, 1000);
            setInterval(pollingNotificacoes, 5000);

            $('#form-poupanca').submit(function(e) {
                e.preventDefault();
                const valor = parseFloat($('#valor').val());
                if (valor < 5000000 || valor > 10000000) {
                    showAlert('Valor deve ser entre R$5.000.000,00 e R$10.000.000,00.', 'danger');
                    return;
                }
                if (valor > saldoAtual) {
                    showAlert('Saldo insuficiente para aplicar este valor.', 'danger');
                    return;
                }
                $.ajax({
                    url: 'poupanca_backend.php',
                    method: 'POST',
                    data: { action: 'criar', valor: valor },
                    dataType: 'json',
                    success: function(response) {
                        showAlert(response.success || response.error, response.success ? 'success' : 'danger');
                        if (response.success) {
                            consultarPoupanca();
                            $('#form-poupanca')[0].reset();
                            $('#valor').val(5000000);
                        }
                    },
                    error: function(xhr, status, error) {
                        showAlert('Erro na comunicação com o servidor: ' + error, 'danger');
                        console.error('Erro AJAX:', xhr.responseText);
                    }
                });
            });

            $('#btn-sacar').click(function() {
                if (!poupancaId) {
                    showAlert('Nenhuma poupança ativa encontrada.', 'danger');
                    return;
                }
                $.ajax({
                    url: 'poupanca_backend.php',
                    method: 'POST',
                    data: { action: 'consultar' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            showAlert(response.error, 'danger');
                            return;
                        }
                        const segundosPassados = response.segundos_passados;
                        const valorAtual = response.valor_atual;
                        const valorSaque = segundosPassados >= 600 ? valorAtual : response.valor_aplicado * 0.75;
                        const penalidade = segundosPassados < 600 ? response.valor_aplicado * 0.25 : 0;
                        $('#modal-valor-saque').text(formatMoney(valorSaque));
                        $('#modal-penalidade').text(penalidade > 0 ? `R$ ${formatMoney(penalidade)} (25% do valor aplicado)` : 'Nenhuma');
                        $('#modal-saldo-final').text(formatMoney(saldoAtual + valorSaque));
                        $('#confirmar-saque').data('poupanca-id', poupancaId);
                        new bootstrap.Modal(document.getElementById('confirmSaqueModal')).show();
                    }
                });
            });

            $('#confirmar-saque').click(function() {
                const poupancaId = $(this).data('poupanca-id');
                $.ajax({
                    url: 'poupanca_backend.php',
                    method: 'POST',
                    data: { action: 'sacar', poupanca_id: poupancaId },
                    dataianz: 'json',
                    success: function(response) {
                        showAlert(response.success || response.error, response.success ? 'success' : 'danger');
                        if (response.success) {
                            $('#saldoAtual').text('R$ ' + response.saldo);
                            consultarPoupanca();
                        }
                        $('#confirmSaqueModal').modal('hide');
                    },
                    error: function(xhr, status, error) {
                        showAlert('Erro na comunicação com o servidor: ' + error, 'danger');
                        console.error('Erro AJAX:', xhr.responseText);
                    }
                });
            });

            pollingNotificacoes();
        });

        function adjustValue(amount, operation) {
            const input = $('#valor');
            let valor = parseFloat(input.val()) || 5000000;
            if (operation === 'add') {
                valor += amount;
            } else {
                valor -= amount;
            }
            if (valor < 5000000) valor = 5000000;
            if (valor > 10000000) valor = 10000000;
            input.val(valor.toFixed(2));
        }

        function consultarPoupanca() {
            $.ajax({
                url: 'poupanca_backend.php',
                method: 'POST',
                data: { action: 'consultar' },
                dataType: 'json',
                success: function(response) {
                    if (response.error) {
                        $('#poupanca-info').hide();
                        $('#form-poupanca-card').show();
                        return;
                    }
                    $('#poupanca-info').show();
                    $('#form-poupanca-card').hide();
                    valorAplicado = response.valor_aplicado;
                    poupancaId = response.id;
                    $('#valor-aplicado').text(formatMoney(valorAplicado));
                    $('#valor-atual').text(formatMoney(response.valor_atual));
                    $('#data-aplicacao').text(new Date(response.data_aplicacao).toLocaleString('pt-BR'));
                    dataAplicacao = new Date(response.data_aplicacao);
                    atualizarTempoRestante();
                    atualizarPrevisao();
                },
                error: function(xhr, status, error) {
                    $('#poupanca-info').hide();
                    $('#form-poupanca-card').show();
                    console.error('Erro AJAX:', xhr.responseText);
                }
            });
        }

        function atualizarTempoRestante() {
            if (!dataAplicacao) return;
            const agora = new Date();
            const segundosPassados = Math.floor((agora - dataAplicacao) / 1000);
            const podeSacar = segundosPassados >= 600;
            if (podeSacar) {
                $('#tempo-restante').text('Pode sacar sem penalidade.');
            } else {
                const segundosRestantes = 600 - segundosPassados;
                const min = Math.floor(segundosRestantes / 60);
                const sec = segundosRestantes % 60;
                $('#tempo-restante').text(`${min}m ${sec}s`);
            }
        }

        function atualizarPrevisao() {
            if (!valorAplicado) return;
            const minutosTotais = 5; // 10 minutos / 2 minutos por rendimento
            const valorProjetado = valorAplicado * Math.pow(1.10, minutosTotais);
            const segundosPassados = dataAplicacao ? Math.floor((new Date() - dataAplicacao) / 1000) : 0;
            const valorSaqueAgora = segundosPassados >= 600 ? valorAplicado * Math.pow(1.10, Math.floor(segundosPassados / 120)) : valorAplicado * 0.75;
            const penalidade = segundosPassados < 600 ? valorAplicado * 0.25 : 0;
            $('#valor-projetado').text(formatMoney(valorProjetado));
            $('#valor-saque-agora').text(formatMoney(valorSaqueAgora));
            $('#penalidade-info').text(penalidade > 0 ? `Penalidade de R$ ${formatMoney(penalidade)}` : 'Sem penalidade');
        }

        function pollingNotificacoes() {
            $.ajax({
                url: 'get_notificacoes.php',
                method: 'POST',
                data: { user_id: <?= $userId ?> },
                dataType: 'json',
                success: function(response) {
                    if (response.notifications) {
                        response.notifications.forEach(function(notif) {
                            mostrarNotificacao(notif.message, notif.type);
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Erro polling notificações:', xhr.responseText);
                }
            });
        }

        function mostrarNotificacao(mensagem, tipo) {
            const toast = $(`
                <div class="toast align-items-center text-white bg-${tipo === 'success' ? 'success' : tipo === 'error' ? 'danger' : 'info'}" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">${mensagem}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `);
            $('.toast-container').append(toast);
            toast.toast({ delay: 5000 });
            toast.toast('show');
        }

        function formatMoney(value) {
            return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function showAlert(message, type) {
            const alert = $('#alert-message');
            alert.removeClass('alert-success alert-danger alert-info').addClass(`alert-${type}`).text(message).show();
            setTimeout(() => alert.hide(), 5000);
        }

        function toggleSaldo() {
            const saldo = $('#saldoAtual');
            const toggleIcon = $('#toggleVisibility');
            if (saldo.text() === '****') {
                saldo.text('R$ <?= number_format($saldoAtual, 0, ',', '.') ?>');
                toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
            } else {
                saldo.text('****');
                toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            }
        }
    </script>
</body>
</html>