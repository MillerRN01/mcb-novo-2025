<?php
require_once 'conexao_pdo.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verifica se há mensagens na sessão
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $tipo_mensagem = isset($_SESSION['tipo_mensagem']) ? $_SESSION['tipo_mensagem'] : 'danger';
    // Limpa as mensagens da sessão após exibi-las
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo_mensagem']);
}
?>