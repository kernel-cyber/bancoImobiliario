function objetivos() {
    window.location.replace("objetivos.php");
}

function jogar() {
    window.location.replace("jogar.php");
}


function extrato() {
    window.location.replace("extrato.php");
}

function reset() {
    window.location.replace("reset.php");
}

function encerrar() {
    window.location.replace("sair.php");
}

document.addEventListener('DOMContentLoaded', function() {
    atualizarSaldo();
});

function atualizarSaldo() {
    fetch('get_saldo.php')
    .then(response => response.text())
    .then(data => {
        document.getElementById('saldo').textContent = data;
    })
    .catch(error => console.error('Erro ao buscar o saldo:', error));
}

function toggleSaldo() {
    var saldo = document.getElementById('saldo');
    var icon = document.getElementById('toggleVisibility');
    if (icon.classList.contains('fa-eye')) {
        // Salva o saldo atual em um atributo data
        saldo.setAttribute('data-valor-real', saldo.textContent);
        // Substitui o valor por asteriscos
        saldo.textContent = saldo.textContent.replace(/\d/g, '*');
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        // Restaura o valor real
        saldo.textContent = saldo.getAttribute('data-valor-real');
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
