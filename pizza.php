<?php
session_start();
include 'db_connection.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    // Redirecionar para a página de login se o usuário não estiver logado
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Consulta SQL para obter as transações do usuário atual
$sql = "SELECT t.message FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.user_id = $user_id
        AND (t.message LIKE '%recebeu%' OR t.message LIKE '%pagou%' OR t.message LIKE '%PIX%')
        ORDER BY t.created_at ASC";
$result = $conn->query($sql);

$transactions = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row['message'];
    }
} else {
    echo "Nenhuma transação encontrada para este usuário.";
}

$conn->close();

// Inicializar contadores para cada categoria
$recebeu_banco_count = 0;
$recebeu_pix_count = 0;
$realizou_pix_count = 0;
$pagou_banco_count = 0;

// Contar o número de transações em cada categoria
foreach ($transactions as $transaction) {
    if (strpos($transaction, 'do banco') !== false) {
        $recebeu_banco_count++;
    } elseif (strpos($transaction, 'recebeu') !== false) {
        $recebeu_pix_count++;
    } elseif (strpos($transaction, 'realizou') !== false) {
        $realizou_pix_count++;
    } elseif (strpos($transaction, 'pagou') !== false) {
        $pagou_banco_count++;
    }
}

// Calcular o total de transações em cada categoria
$total_recebeu_banco = $recebeu_banco_count;
$total_recebeu_pix = $recebeu_pix_count;
$total_realizou_pix = $realizou_pix_count;
$total_pagou_banco = $pagou_banco_count;

// Calcular o total geral de transações
$total_geral = $total_recebeu_banco + $total_recebeu_pix + $total_realizou_pix + $total_pagou_banco;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico de Pizza - Transações do Usuário</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <canvas id="transactionsChart"></canvas>
    </div>
    <script>
        const labels = ['Recebeu do Banco', 'Recebeu via PIX', 'Realizou PIX', 'Pagou ao Banco'];
        const data = [<?php echo $total_recebeu_banco; ?>, <?php echo $total_recebeu_pix; ?>, <?php echo $total_realizou_pix; ?>, <?php echo $total_pagou_banco; ?>];
        const backgroundColors = ['rgba(255, 99, 132, 0.7)', 'rgba(54, 162, 235, 0.7)', 'rgba(255, 206, 86, 0.7)', 'rgba(75, 192, 192, 0.7)'];

        // Criar o gráfico de pizza
        const ctx = document.getElementById('transactionsChart').getContext('2d');
        const transactionsChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: 'Transações do Usuário Atual (Total: <?php echo $total_geral; ?>)'
                }
            }
        });
    </script>
</body>
</html>
