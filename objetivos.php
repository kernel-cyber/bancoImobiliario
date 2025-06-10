<?php
session_start();
include 'db_connection.php';

// Verificar se o usuário está logado e obter o ID do usuário
if (!isset($_SESSION['user_id'])) {
    header('Location: index.html'); // Redireciona para login se não estiver logado
    exit;
}

$user_id = $_SESSION['user_id'];

// Selecionar todos os objetivos
$sql = "SELECT * FROM objetivos";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Erro na preparação da consulta SELECT * FROM objetivos: ' . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
$objetivos = [];
while ($row = $result->fetch_assoc()) {
    $objetivos[] = $row;
}

// Selecionar o objetivo atribuído ao usuário (se houver)
$sql = "SELECT objetivoId FROM jogando WHERE userId = ?";
$stmtAssigned = $conn->prepare($sql);
if ($stmtAssigned === false) {
    die('Erro na preparação da consulta SELECT objetivoId FROM jogando: ' . $conn->error);
}
$stmtAssigned->bind_param("i", $user_id);
$stmtAssigned->execute();
$resultAssigned = $stmtAssigned->get_result();
$assignedObjectiveId = $resultAssigned->fetch_assoc()['objetivoId'] ?? null;
$stmtAssigned->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4a99debbd4.js" crossorigin="anonymous"></script>
    <title>Gerenciamento de Objetivos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 90%;
            margin: 20px auto;
            box-sizing: border-box;
            overflow-x: auto;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f4f4f4;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .button, .back-button {
            padding: 10px 20px;
            border: none;
            background-color: #28a745;
            color: white;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .button:hover, .back-button:hover {
            background-color: #218838;
        }
        .inactive {
            background-color: #f44336;
        }
        .inactive:hover {
            background-color: #d32f2f;
        }
        @media (max-width: 600px) {
            table {
                font-size: 12px;
            }
        }
		 /* Estilos CSS omitidos por brevidade */
        .assigned {
            background-color: #28a745; /* Verde */
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fa-solid fa-bullseye" style="color:red;"></i> Gerenciamento de Objetivos</h2>
        </header>
        <main>
            <?php if (empty($objetivos)): ?>
                <p>Nenhum objetivo encontrado.</p>
            <?php else: ?>
                <table id="objetivosTable">
                    <thead>
                        <tr>
                            <th>Nome do Objetivo</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                       <?php foreach ($objetivos as $objetivo): ?>
    <?php 
        $isAssigned = ($objetivo['id'] == $assignedObjectiveId); 
        $class = $isAssigned ? 'assigned' : '';
    ?>
    <tr>
        <td class="<?= $class ?>"><b><?= $objetivo['nome_objetivo'] ?></b></td>
        <td class="<?= $class ?>"><?= $objetivo['descricao_objetivo'] ?></td>
    </tr>
<?php endforeach; ?>


                    </tbody>
                </table>
            <?php endif; ?>
        </main>
        <footer>
            <a href="dashboard.php" class="back-button"><i class="fa-solid fa-rotate-left"></i> Voltar</a>
        </footer>
    </div>
</body>
</html>
