<?php
$servername = "localhost";
$username = "root";  // Substitua "your_username" pelo seu nome de usuário do banco de dados
$password = "";// Substitua "your_password" pela sua senha do banco de dados
$dbname = "banco_imobiliario";  // Nome do banco de dados que você está usando

// Criando a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Checando a conexão
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
