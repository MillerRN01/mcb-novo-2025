<?php
session_start(); // Inicia a sessão para usar mensagens de feedback
require_once 'conexao.php'; // Conexão com o banco de dados

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtém o ID do cliente a ser deletado
    $id_cliente = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;
    $tipo_cliente = isset($_POST['tipo_cliente']) ? $_POST['tipo_cliente'] : '';

    // Verifica se o ID do cliente é válido
    if ($id_cliente > 0 && in_array($tipo_cliente, ['pf', 'pj'])) {
        // Define a tabela com base no tipo de cliente
        $tabela = $tipo_cliente === 'pf' ? 'clientes_pf' : 'clientes_pj';

        // Prepara a consulta SQL para deletar o cliente
        $sql = "DELETE FROM $tabela WHERE id_cliente = ?";

        // Prepara a declaração
        $stmt = $conn->prepare($sql);

        // Verifica se a preparação da consulta foi bem-sucedida
        if ($stmt === false) {
            $_SESSION['mensagem'] = 'Erro na preparação da consulta: ' . htmlspecialchars($conn->error);
            $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
            header('Location: cadastro_cliente.php'); // Redireciona para a página de cadastro
            exit();
        }

        // Vincula o parâmetro e executa a consulta
        $stmt->bind_param('i', $id_cliente);
        if ($stmt->execute()) {
            $_SESSION['mensagem'] = 'Cliente deletado com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success'; // Tipo de mensagem para sucesso
        } else {
            $_SESSION['mensagem'] = 'Erro ao deletar cliente: ' . htmlspecialchars($stmt->error);
            $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
        }

        // Fecha a declaração
        $stmt->close();
    } else {
        $_SESSION['mensagem'] = 'ID do cliente inválido ou tipo de cliente não especificado.';
        $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
    }

    // Redireciona para a página de cadastro
    header('Location: cadastro_cliente.php');
    exit();
}

// Fecha a conexão
$conn->close();
?>