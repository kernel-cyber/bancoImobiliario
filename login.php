<?php
session_start();
include 'db_connection.php';

$nickname = $_POST['nickname'];
$password = $_POST['password'];

if (!empty($nickname) && !empty($password)) {
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE nickname = ?");
    $stmt->bind_param("s", $nickname);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: dashboard.php");
            exit;
        } else {
            echo "<script>alert('Usuário ou senha incorretos.'); location.href='index.html';</script>";
        }
    } else {
        echo "<script>alert('Usuário ou senha incorretos.'); location.href='index.html';</script>";
    }
} else {
    echo "<script>alert('Por favor, preencha todos os campos.'); location.href='index.html';</script>";
}
?>
