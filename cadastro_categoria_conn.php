<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Adicionar nova categoria
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $categoria = trim($_POST['categoria']);

    if (!empty($categoria)) {
        // Sanitiza a entrada
        $categoria = htmlspecialchars($categoria);

        $stmt = $conn->prepare("INSERT INTO categorias_produto (nome) VALUES (?)");
        if ($stmt) {
            $stmt->bind_param("s", $categoria);

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = 'Nova categoria adicionada com sucesso!';
                $_SESSION['tipo_mensagem'] = 'success'; // Tipo de mensagem para sucesso
                $stmt->close(); // Fecha o statement antes de redirecionar
                header('Location: cadastro_produto.php'); // Redireciona para a página de produtos
                exit();
            } else {
                $_SESSION['mensagem'] = 'Erro ao adicionar categoria: ' . htmlspecialchars($stmt->error);
                $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
                $stmt->close(); // Fecha o statement antes de redirecionar
                header('Location: cadastro_categoria.php'); // Redireciona para a página de cadastro de categoria
                exit();
            }
        } else {
            $_SESSION['mensagem'] = 'Erro ao preparar a consulta: ' . htmlspecialchars($conn->error);
            $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
            header('Location: cadastro_categoria.php'); // Redireciona para a página de cadastro de categoria
            exit();
        }
    } else {
        $_SESSION['mensagem'] = 'Por favor, insira um nome válido para a categoria';
        $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
        header('Location: cadastro_categoria.php'); // Redireciona para a página de cadastro de categoria
        exit();
    }
}
?>