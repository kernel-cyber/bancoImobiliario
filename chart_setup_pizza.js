function loadPieChart(transactionsData) {
    const ctx = document.getElementById('transactionsPieChart').getContext('2d');

    // Agrupar transações em categorias
    const categories = {
        'Recebido do banco': 0,
        'Recebido de outros jogadores': 0,
        'Pago ao banco': 0,
        'Pago a outros jogadores': 0
    };

    transactionsData.forEach(transaction => {
        const { message, nickname } = transaction;

        // Substituir "para você" pelo nickname do destinatário de forma dinâmica
        let updatedMessage = message.replace("para você", `para ${nickname}`);

        // Extrair valor monetário da mensagem
        const valueMatch = updatedMessage.match(/R\$ ([\d,\.]+)/);
        if (valueMatch) {
            let value = parseFloat(valueMatch[1].replace(/\./g, '').replace(',', '.'));

            if (updatedMessage.includes('recebeu')) {
                if (updatedMessage.includes('banco')) {
                    categories['Recebido do banco'] += value;
                } else {
                    categories['Recebido de outros jogadores'] += value;
                }
            } else if (updatedMessage.includes('pagou') || updatedMessage.includes('realizou um PIX')) {
                if (updatedMessage.includes('banco')) {
                    categories['Pago ao banco'] += value;
                } else {
                    categories['Pago a outros jogadores'] += value;
                }
            }
        }
    });

    // Dados para o gráfico de pizza
    const labels = Object.keys(categories);
    const data = Object.values(categories);

    // Cores para o gráfico
    const colors = labels.map(() => getRandomColor());

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
                            let value = context.raw.toFixed(2);
                            return `${message}: R$ ${value}`;
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

