let valorAtual = '';

function digitar(numero) {
    valorAtual += numero.replace(',', '.');
    atualizarDisplay();
}


function usarM() {
let valor = parseFloat(valorAtual);
if (!isNaN(valor)) {
valorAtual = (valor * 1000000).toString();
atualizarDisplay();
}
}

function usarK() {
let valor = parseFloat(valorAtual);
if (!isNaN(valor)) {
valorAtual = (valor * 1000).toString();
atualizarDisplay();
}
}

function extrato() {
window.location.replace("extrato.php");
}

function adicionar2M() {
const formData = new FormData();
formData.append('adicionar_2m_usuario_atual', 'true');
formData.append('ajax', 'true');

fetch('banco.php', {
method: 'POST',
body: formData
})
.then(response => response.json())
.then(data => {
document.getElementById('saldoAtual').textContent = 'R$ ' + data.saldoAtual;
exibirNotificacao(data.mensagem, data.status, true);
limparValoresInseridos();
verificarSaldoOculto();
if (data.status === 'success') {
verificarSaldo(data.saldoAtual); // Verificar saldo após atualização
}
})
.catch(error => console.error('Erro ao adicionar 2M ao usuário atual:', error));
}


function apagar() {
valorAtual = valorAtual.slice(0, -1);
atualizarDisplay();
}

function voltarDashboard() {
window.location.href = 'dashboard.php'; // Altere para a URL correta do dashboard
}

function atualizarDisplay() {
let inputValor = document.getElementById('valorTransacao');
inputValor.value = formatarMoeda(valorAtual);
verificarSaldoOculto();
}


function formatarMoeda(valor) {
if (!valor) return 'R$ 0';

let numero = parseFloat(valor.replace(',', '.'));
if (isNaN(numero)) {
return 'R$ 0';
}

return 'R$ ' + numero.toLocaleString('pt-BR', {
minimumFractionDigits: 0,
maximumFractionDigits: 3
});
}

function mostrarOpcoesBanco() {
    const destinatario = document.getElementById('destinatario').value;
    const opcoesBanco = document.getElementById('opcoesBanco');
    const pagarOption = document.querySelector('input[name="tipoOperacao"][value="pagar"]');

    if (destinatario == 0) {
        opcoesBanco.style.display = 'block';
        pagarOption.checked = true; // Define "PAGAR" como a opção padrão
    } else {
        opcoesBanco.style.display = 'none';
    }
}


document.addEventListener('DOMContentLoaded', function() {
const form = document.getElementById('transacaoForm');
form.addEventListener('submit', function(event) {
event.preventDefault();
const formData = new FormData(form);
formData.append('ajax', 'true');
fetch('banco.php', {
method: 'POST',
body: formData
})
.then(response => response.json())
.then(data => {
document.getElementById('saldoAtual').textContent = 'R$ ' + data.saldoAtual;
exibirNotificacao(data.mensagem, data.status, false);
limparValoresInseridos();
verificarSaldoOculto();
verificarSaldo(data.saldoAtual); // Verificar saldo após atualização
})
.catch(error => console.error('Erro ao realizar transação:', error));
});
// Iniciar long polling
verificarNotificacoes();
});

function exibirNotificacao(mensagem, status, tocarSom = true) {
const notificacao = document.getElementById('notificacao');
notificacao.textContent = mensagem;
notificacao.style.display = 'block';

if (status === 'error') {
notificacao.style.backgroundColor = 'red';
} else if (status === 'warning') {
notificacao.style.backgroundColor = 'orange';
} else if (status === 'success') {
notificacao.style.backgroundColor = 'green';
} else if (status === 'alert') {
notificacao.style.backgroundColor = 'red';
} else {
notificacao.style.backgroundColor = 'green';
}

if (tocarSom) {
tocarSomNotificacao(status, mensagem);
}

setTimeout(() => {
notificacao.style.display = 'none';
}, 10000);
}

function verificarNotificacoes() {
fetch('check_notifications.php')
.then(response => response.json())
.then(data => {
if (data.notifications.length > 0) {
	data.notifications.forEach(notificacao => {
		if (notificacao.type === 'rico') {
setTimeout(() => {
exibirNotificacao(notificacao.message, notificacao.type, true);
}, 90000);
} else {
exibirNotificacao(notificacao.message, notificacao.type, true);
}

	});
	document.getElementById('saldoAtual').textContent = 'R$ ' + data.balance;
	verificarSaldoOculto();
	verificarSaldo(data.balance); // Verificar saldo após atualização
}
setTimeout(verificarNotificacoes, 1000); // Verificar novamente após 1 segundo
})
.catch(error => console.error('Erro ao verificar notificações:', error));
}

function tocarSomNotificacao(status, mensagem) {
let audio;
if (mensagem.includes('para você')) {
audio = new Audio('sounds/transaction.mp3');
} else if (status === 'error') {
audio = new Audio('sounds/error.mp3');
} else if (status === 'warning') {
audio = new Audio('sounds/exit.mp3');
} else if (status === 'join') {
audio = new Audio('sounds/join.mp3');
} else if (status === 'rico') {
audio = new Audio('sounds/rico.mp3');
} else if (status === 'alert') {
audio = new Audio('sounds/alert.mp3');
} else {
audio = new Audio('sounds/notification.mp3');
}

audio.play();
}

function limparValoresInseridos() {
valorAtual = '';
atualizarDisplay();
}

document.addEventListener('DOMContentLoaded', function() {
    var saldo = document.getElementById('saldoAtual');
    var icon = document.getElementById('toggleVisibility');

    // Verificar estado salvo no localStorage
    var saldoToggleState = localStorage.getItem('saldoToggle');
    if (saldoToggleState === 'hidden') {
        saldo.setAttribute('data-valor-real', saldo.textContent);
        saldo.textContent = saldo.textContent.replace(/\d/g, '*');
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else if (saldoToggleState === 'visible') {
        saldo.textContent = saldo.getAttribute('data-valor-real') || saldo.textContent;
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var saldo = document.getElementById('saldoAtual');
    var icon = document.getElementById('toggleVisibility');

    // Verificar estado salvo no localStorage
    var saldoToggleState = localStorage.getItem('saldoToggle');
    if (saldoToggleState === 'hidden') {
        saldo.setAttribute('data-valor-real', saldo.textContent);
        saldo.textContent = saldo.textContent.replace(/\d/g, '*');
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        // Restaurar o valor real do saldo caso esteja salvo
        var valorReal = saldo.getAttribute('data-valor-real');
        if (valorReal) {
            saldo.textContent = valorReal;
        }
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    var saldo = document.getElementById('saldoAtual');
    var icon = document.getElementById('toggleVisibility');

    // Verificar estado salvo no localStorage
    var saldoToggleState = localStorage.getItem('saldoToggle');
    var valorReal = localStorage.getItem('saldoReal');

    if (saldoToggleState === 'hidden' && valorReal) {
        saldo.textContent = valorReal.replace(/\d/g, '*');
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else if (valorReal) {
        saldo.textContent = valorReal;
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

function toggleSaldo() {
    var saldo = document.getElementById('saldoAtual');
    var icon = document.getElementById('toggleVisibility');

    if (icon.classList.contains('fa-eye')) {
        localStorage.setItem('saldoReal', saldo.textContent);
        saldo.textContent = saldo.textContent.replace(/\d/g, '*');
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
        // Salvar estado do toggle no localStorage
        localStorage.setItem('saldoToggle', 'hidden');
    } else {
        saldo.textContent = localStorage.getItem('saldoReal');
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
        // Salvar estado do toggle no localStorage
        localStorage.setItem('saldoToggle', 'visible');
    }
}


function verificarSaldoOculto() {
    var saldo = document.getElementById('saldoAtual');
    var icon = document.getElementById('toggleVisibility');
    if (icon.classList.contains('fa-eye-slash')) {
        saldo.setAttribute('data-valor-real', saldo.textContent);
        saldo.textContent = saldo.textContent.replace(/\d/g, '*');
    }
    // Atualizar o valor no localStorage
    localStorage.setItem('saldoReal', saldo.getAttribute('data-valor-real') || saldo.textContent);
}

function verificarSaldo(saldo) {
    let valorSaldo = parseFloat(saldo.replace(/[^0-9,-]+/g, '').replace(',', '.'));
    fetch('notificar_saldo.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ saldo: valorSaldo })
    })
    .then(response => response.json())
    .catch(error => console.error('Erro ao verificar saldo:', error));

    // Atualizar o valor no localStorage
    localStorage.setItem('saldoReal', 'R$ ' + valorSaldo.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
}


function verificarJogadores() {
    fetch('verificar_jogadores.php')
        .then(response => response.json())
        .then(data => {
            if (data.shouldRefresh) {
                setTimeout(() => {
                    window.location.reload();
                }, 7000); // Ajuste o tempo de recarregamento conforme necessário
            } else {
                setTimeout(verificarJogadores, 1000); // Verifique novamente após 1 segundos
            }
        })
        .catch(error => console.error('Erro ao verificar jogadores:', error));
}

verificarJogadores();



function verificarReset() {
    fetch('verificar_reset.php')
    .then(response => response.json())
    .then(data => {
        if (data.gameReset === 1) {
            // Atualizar o valor no localStorage para 15M
            const valorSaldo = 15000000;
            localStorage.setItem('saldoReal', 'R$ ' + valorSaldo.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }));

            // Redirecionar para dashboard
            window.location.href = 'dashboard.php';
        } else {
            setTimeout(verificarReset, 1000);
        }
    })
    .catch(error => console.error('Erro ao verificar reset do jogo:', error));
}

// Chamar a função inicialmente
verificarReset();

