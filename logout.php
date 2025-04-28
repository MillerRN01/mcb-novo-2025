<?php
require_once 'conexao_pdo.php'; // Inclua sua conexão com o banco de dados
session_start();
session_destroy();
header('Location: index.php');
exit;
?>
