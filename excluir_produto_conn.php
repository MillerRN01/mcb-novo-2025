<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produto = intval($_POST['id_produto']);
    
    // Verifica se o produto existe
    $sql_verifica = "SELECT id_produto FROM produtos WHERE id_produto = ?";
    $stmt_verifica = $conn->prepare($sql_verifica);
    $stmt_verifica->bind_param("i", $id_produto);
    $stmt_verifica->execute();
    $resultado = $stmt_verifica->get_result();
    
    if ($resultado->num_rows > 0) {
        // Produto existe, pode excluir
        $sql_delete = "DELETE FROM produtos WHERE id_produto = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id_produto);
        
        if ($stmt_delete->execute()) {
            $_SESSION['mensagem'] = "Produto excluído com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
        } else {
            $_SESSION['mensagem'] = "Erro ao excluir produto: " . $stmt_delete->error;
            $_SESSION['tipo_mensagem'] = "danger";
        }
    } else {
        $_SESSION['mensagem'] = "Produto não encontrado!";
        $_SESSION['tipo_mensagem'] = "danger";
    }
    
    header("Location: cadastro_produto.php");
    exit;
} else {
    header("Location: cadastro_produto.php");
    exit;
}