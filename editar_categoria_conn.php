<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $id_categoria = $_POST['edit_id'];
    $categoria = trim($_POST['categoria']);

    if (!empty($categoria)) {
        $stmt = $conn->prepare("UPDATE categorias_produto SET nome = ? WHERE id_categoria = ?");
        $stmt->bind_param("si", $categoria, $id_categoria);

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = 'Categoria editada com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success';
        } else {
            $_SESSION['mensagem'] = 'Erro ao editar categoria: ' . htmlspecialchars($stmt->error);
            $_SESSION['tipo_mensagem'] = 'danger';
        }
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = 'Por favor, insira um nome válido para a categoria';
        $_SESSION['tipo_mensagem'] = 'danger';
    }

    header('Location: cadastro_categoria.php'); // Redireciona para a página de cadastro de categoria
    exit();
}
?>