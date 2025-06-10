<?php
include 'db_connection.php';

session_start();
$current_user_id = $_SESSION['user_id']; // Supondo que o user_id esteja na sessão

// Consulta SQL para todas as transações
$sql_all = "SELECT t.user_id, t.message, t.created_at, u.nickname FROM transactions t
            JOIN users u ON t.user_id = u.id
            ORDER BY t.created_at ASC";
$result_all = $conn->query($sql_all);

$all_transactions = [];

if ($result_all->num_rows > 0) {
    while ($row = $result_all->fetch_assoc()) {
        $all_transactions[] = $row;
    }
} else {
    echo "Nenhuma transação encontrada.";
}

// Consulta SQL para transações do usuário atual
$sql_user = "SELECT t.user_id, t.message, t.created_at, u.nickname FROM transactions t
             JOIN users u ON t.user_id = u.id
             WHERE t.user_id = ?
             ORDER BY t.created_at ASC";

$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result_user = $stmt->get_result();

$user_transactions = [];

if ($result_user->num_rows > 0) {
    while ($row = $result_user->fetch_assoc()) {
        $user_transactions[] = $row;
    }
} 

$conn->close();

$allTransactionsData = json_encode($all_transactions);
$userTransactionsData = json_encode($user_transactions);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráfico de Transações</title>
    <script src="https://kit.fontawesome.com/4a99debbd4.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
</head>
<style>
    button {
        display: block;
        width: 100%;
        padding: 10px;
        border: none;
        background-color: #28a745;
        color: white;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        text-align: center;
        margin-top: 10px;
    }
    button:hover {
        background-color: #218838;
    }
	.btn-back {
    background-image: linear-gradient(145deg, #66cc66, #009933); /* Gradiente ajustado */
    color: white; /* Cor do texto para garantir contraste */
    
}
</style>
<body>
    <div class="container">
        <canvas id="transactionsLineChart"></canvas>
    </div>
    <div class="container">
        <canvas id="transactionsPieChart"></canvas>
    </div>
    <script>
        const allTransactionsData = <?php echo $allTransactionsData; ?>;
        const userTransactionsData = <?php echo $userTransactionsData; ?>;
        
        document.addEventListener('DOMContentLoaded', function () {
            loadLineChart(allTransactionsData);
            loadPieChart(userTransactionsData);
        });

        function loadLineChart(transactionsData) {
            const ctx = document.getElementById('transactionsLineChart').getContext('2d');
            const initialBalance = 15000000; // Saldo inicial para cada usuário
            const usersData = {};

            // Inicializar saldos e transações
            transactionsData.forEach(transaction => {
                const { user_id, nickname } = transaction;
                if (!usersData[user_id]) {
                    usersData[user_id] = {
                        label: nickname,
                        data: [{ x: 0, y: initialBalance, v: 0, message: '' }],
                        borderColor: getRandomColor(),
                        fill: false,
                        tension: 0,
                        cubicInterpolationMode: 'default',
                        currentBalance: initialBalance
                    };
                }
            });

            // Processar as transações e ajustar os saldos
            transactionsData.forEach((transaction, index) => {
                const { user_id, nickname, message } = transaction;
                let user = usersData[user_id];
                let currentBalance = user.currentBalance;

                // Substituir "para você" pelo nickname do destinatário de forma dinâmica
                let updatedMessage = message.replace("para você", `para ${nickname}`);

                // Extrair valor monetário da mensagem
                const valueMatch = updatedMessage.match(/R\$ ([\d,\.]+)/);
                if (valueMatch) {
                    let value = parseFloat(valueMatch[1].replace(/\./g, '').replace(',', '.'));

                    // Determinar se a transação é um pagamento ou recebimento
                    if (updatedMessage.includes('recebeu') || updatedMessage.includes('recebeu um PIX')) {
                        currentBalance += value; // Aumenta saldo para recebimentos
                    } else if (updatedMessage.includes('pagou') || updatedMessage.includes('realizou um PIX')) {
                        currentBalance -= value; // Diminui saldo para pagamentos
                    }

                    // Atualizar saldo atual do usuário
                    user.currentBalance = currentBalance;

                    // Adicionar ponto de dados com o novo saldo
                    user.data.push({
                        x: index + 1,
                        y: currentBalance,
                        v: value,
                        message: updatedMessage
                    });
                }
            });

            const datasets = Object.values(usersData).map(user => ({
                ...user,
                data: user.data.map(point => ({
                    x: point.x,
                    y: point.y,
                    v: point.v,
                    message: point.message,
                    nickname: user.label
                }))
            }));

            // Criar o gráfico
            const myChart = new Chart(ctx, {
                type: 'line',
                data: { datasets },
                options: {
                    scales: {
                        x: {
                            type: 'linear',
                            title: {
                                display: true,
                                text: 'Fluxo de Movimentações'
                            },
                            ticks: {
                                callback: function(value) {
                                    return value;
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Saldo (R$)'
                            },
                            ticks: {
                                stepSize: 10000000,
                                callback: function(value) {
                                    return `R$ ${value.toLocaleString('pt-BR')}`;
                                }
                            }
                        }
                    },
                    elements: {
                        line: {
                            tension: 0 // Para linhas retas
                        },
                        point: {
                            radius: 8 // Tamanho dos pontos
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let message = context.raw.message;
                                    return `${message}`;
                                }
                            }
                        },
                        datalabels: {
                            display: true,
                            align: 'top',
                            formatter: (value, context) => {
                                return `R$ ${context.raw.v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                            }
                        }
                    },
                }
            });
        }

        function loadPieChart(transactionsData) {
            const ctx = document.getElementById('transactionsPieChart').getContext('2d');

            // Agrupar transações em categorias
            const categories = {
                'Recebido do banco': 0,
                'Recebido de jogadores': 0,
                'Pago ao banco': 0,
                'Pago a jogadores': 0
            };

            transactionsData.forEach(transaction => {
                const { message, nickname } = transaction;

                // Substituir "para você" pelo nickname do destinatário de forma dinâmica
                let updatedMessage = message.replace("para você", `para ${nickname}`);

                // Extrair valor monetário da mensagem
                const valueMatch = updatedMessage.match(/R\$ ([\d,\.]+)/);
                if (valueMatch) {
                    let value = parseFloat(valueMatch[1].replace(/\./g, '').replace(',', '.'));

                    if (updatedMessage.includes('recebeu um PIX')) {
                        categories['Recebido de jogadores'] += value;
                    } else if (updatedMessage.includes('recebeu')) {
                        if (updatedMessage.includes('banco')) {
                            categories['Recebido do banco'] += value;
                        } else {
                            categories['Recebido de jogadores'] += value;
                        }
                    } else if (updatedMessage.includes('realizou um PIX')) {
                        categories['Pago a jogadores'] += value;
                    } else if (updatedMessage.includes('pagou')) {
                        if (updatedMessage.includes('banco')) {
                            categories['Pago ao banco'] += value;
                        } else {
                            categories['Pago a jogadores'] += value;
                        }
                    }
                }

            });

            // Cores fixas para cada categoria
            const categoryColors = {
                'Recebido do banco': '#4CAF50', // Verde
                'Recebido de jogadores': '#2196F3', // Azul
                'Pago ao banco': '#F44336', // Vermelho
                'Pago a jogadores': '#FF9800' // Laranja
            };

            // Dados para o gráfico de pizza
            const labels = Object.keys(categories);
            const data = Object.values(categories);

            // Cores para o gráfico baseadas nas categorias
            const colors = labels.map(label => categoryColors[label]);

            // Criar o gráfico de pizza
            const myChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderColor: colors.map(color => darkenColor(color, 0.2)),
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let message = context.label;
                                    let value = context.raw.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL', minimumFractionDigits: 0, maximumFractionDigits: 0 });
                                    return `${message}: ${value}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function getRandomColor() {
            const letters = '0123456789ABCDEF';
            let color = '#';
            for (let i = 0; i < 6; i++) {
                color += letters[Math.floor(Math.random() * 16)];
            }
            return color;
        }

        function darkenColor(color, percent) {
            const num = parseInt(color.slice(1), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) + amt;
            const G = (num >> 8 & 0x00FF) + amt;
            const B = (num & 0x0000FF) + amt;
            return `#${(0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 + (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 + (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1)}`;
        }

        function voltar() {
            window.location.href = 'extrato.php';
        }
    </script><br>
    <button onclick="voltar()" class="btn-back"><i class="fa-solid fa-circle-arrow-left"></i> Voltar</button>
</body>
</html>
