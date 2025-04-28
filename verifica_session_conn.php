<?php
require_once 'conexao_pdo.php';
// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está autenticado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: index.php');
    exit();
}

// Atribui as variáveis de sessão a variáveis locais
$usuario = $_SESSION['usuario'];
$foto = isset($_SESSION['foto']) ? $_SESSION['foto'] : '';
$email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$dante = isset($_SESSION['dante']) ? $_SESSION['dante'] : '';
?>