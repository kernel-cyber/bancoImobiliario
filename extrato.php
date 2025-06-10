<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.html'); // Redireciona para login se não estiver logado
    exit;
}

// Consultar as 30 últimas notificações
$sql = "SELECT DISTINCT created_at, message FROM transactions ORDER BY created_at DESC LIMIT 30";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Erro na preparação da consulta SELECT * FROM transactions: ' . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$transactions = [];
while ($row = $result->fetch_assoc()) {
    if (strpos($row['message'], 'para você') === false) {
        $transactions[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <script src="https://kit.fontawesome.com/4a99debbd4.js" crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="extrato.css">
    <title>Extrato de Transações</title>
</head>
<body>
    <div class="container">
        <h2><i class="fa-solid fa-file-invoice-dollar"></i> Extrato de Transações</h2>
        <?php if (empty($transactions)): ?>
    <p>Nenhuma transação encontrada.</p>
<?php else: ?>
    <div class="search-container">
        <i class="fa-solid fa-magnifying-glass"></i> <input type="text" id="searchInput" class="search-input" placeholder="Pesquisar transações..." onkeyup="filterTable()">
    </div>
    <table id="transactionsTable">
        <thead>
            <tr>
                <th><center><i class="fa-regular fa-clock"></i> Horário</center></th>
                <th><center><i class="fa-solid fa-money-bill-transfer"></i> Transação</center></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <?php
                // Pular a exibição da mensagem se contiver "recebeu um PIX"
                if (strpos($transaction['message'], 'recebeu um PIX') !== false) {
                    continue;
                }

                $messageClass = '';
                $transactionType = '';

                if (strpos($transaction['message'], 'pagou') !== false) {
                    $messageClass = 'red';
                    $transactionType = '<i class="fa-solid fa-hand-holding-dollar"></i>';
                } elseif (strpos($transaction['message'], 'recebeu') !== false) {
                    $messageClass = 'green';
                    $transactionType = '<i class="fa-solid fa-hand-holding-medical"></i>';
                } elseif (strpos($transaction['message'], 'PIX') !== false) {
                    $messageClass = 'blue';
                    $transactionType = '<i class="fa-solid fa-money-bill-transfer"></i>';
                }
                ?>
                <tr>
                    <td class="nowrap"><b>[<?= date('d/m H:i', strtotime($transaction['created_at'])) ?>]</b></td>
                    <td class="nowrap <?= $messageClass ?>">[<?= $transactionType ?>] <?= $transaction['message'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

        <div class="button-container">
            <button class="btn-back" onclick="voltarDashboard()"><i class="fa-solid fa-circle-arrow-left"></i> Voltar</button>
            <button class="btn-est" onclick="chart()"><i class="fa-solid fa-chart-line"></i> Estatísticas</button>
        </div>
    </div>
    <script>
        function voltarDashboard() {
            window.location.href = 'banco.php'; // Altere para a URL correta do dashboard
        }

        function chart() {
            window.location.href = 'chart.php'; // Altere para a URL correta do dashboard
        }

        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('transactionsTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header row
                const td = tr[i].getElementsByTagName('td')[1];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html>
