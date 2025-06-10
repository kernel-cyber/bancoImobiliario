<?php
session_start();
include 'db_connection.php';

$nickname = $_POST['new_nickname'];
$password = $_POST['new_password'];

if (!empty($nickname) && !empty($password)) {
    // Converter a primeira letra do nickname para maiúscula
    $nickname = ucfirst(strtolower($nickname));
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (nickname, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $nickname, $hashed_password);
    $stmt->execute();
    if ($stmt->affected_rows === 1) {
        echo "<script>alert('Usuário criado com sucesso!'); location.href='index.html';</script>";
    } else {
        echo "<script>alert('Usuário já está em uso!'); location.href='index.html';</script>";
    }
} else {
    echo "<script>alert('Por favor, preencha todos os campos.'); location.href='index.html';</script>";
}
?>
