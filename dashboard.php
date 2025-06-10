<?php
session_start();
include 'db_connection.php'; // Supondo que este arquivo tenha a conexão com o banco de dados

// Verifique se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redireciona para login se não estiver logado
    exit;
}

// Obter o ID do usuário atual
$userId = $_SESSION['user_id'];

// Consultar o nickname do usuário atual
$sql = "SELECT nickname FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $nickname = $row['nickname'];
} else {
    $nickname = "Usuário";
}
$stmt->close();

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="dashboard.css">
    <script src="https://kit.fontawesome.com/4a99debbd4.js" crossorigin="anonymous"></script>
    <title>Banco Imobiliário - Dashboard</title>
</head>
<body>
    <header>
        <img src="imagens/logo.png" alt="Logo Banco Imobiliário" class="logo">
       <div class="saldo"><font color="green"><i class="fa-solid fa-money-check-dollar icon-gradient2"></i></font> R$<span id="saldo">0</span> <i id="toggleVisibility" class="fa-solid fa-eye" onclick="toggleSaldo();"></i><span><font color="gold"> <i class="fa-solid <?= $icone; ?> icon-gradient"></i></font> <?= $nickname; ?></span></div>
    </header>
    <div class="buttons">
        <button class="btn objetivo" onclick="objetivos()"><i class="fa-solid fa-bullseye"></i> <b>Objetivos</b></button>
        <button class="btn jogar" onclick="jogar()"><i class="fa-solid fa-dice"></i> <b>Jogar</b></button>
       
<button class="btn leilao" onclick="leilao()"><i class="fa-solid fa-gavel"></i></i> <b>Leilão</b></button>
		<button class="btn encerrar" onclick="encerrar()"><i class="fa-solid fa-person-walking-arrow-right"></i> <b>Abandonar Partida</b></button>
		
		<?php

// Verificar se o user_id na sessão é igual a 13
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == 13) {
    echo '<button class="btn resetar" onclick="reset()"><i class="fa-solid fa-power-off"></i> <b>Resetar Partida</b></button>';
}
?>
    </div>
	
</div>

    <script src="dashboard.js"></script>
</body>
</html>
