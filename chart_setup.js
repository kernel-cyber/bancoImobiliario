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
            if (updatedMessage.includes('recebeu')) {
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
                            return `R$ ${value.toLocaleString()}`;
                        }
                    }
                }
            },
            elements: {
                line: {
                    tension: 0 // Para linhas retas
                },
                point: {
                    radius: 1 // Tamanho dos pontos
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
                        return `R$ ${context.raw.v.toFixed(2)}`;
                    }
                }
            },
        }
    });
}
