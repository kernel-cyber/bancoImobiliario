<?php
require_once 'db_connection.php';
$sql = "UPDATE poupanca
        SET valor_atual = valor_atual * 1.10,
            ultima_atualizacao = NOW()
        WHERE status = 'ativa'
          AND TIMESTAMPDIFF(MINUTE, ultima_atualizacao, NOW()) >= 2";
$conn->query($sql);
echo "Juros atualizados.";
?>