<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta os dados do formulário
    $nome = trim($_POST['nome']);
    $cpf_cnpj = trim($_POST['cpf_cnpj']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $cep = trim($_POST['cep']);
    $logradouro = trim($_POST['endereco']);
    $numero = trim($_POST['numero']);
    $bairro = trim($_POST['bairro']);
    $estado = trim($_POST['estado']);
    $cidade = trim($_POST['cidade']);

    // Verificando se todos os campos obrigatórios estão preenchidos
    if (empty($nome) || empty($email) || empty($cpf_cnpj) || empty($cep)) {
        $_SESSION['mensagem'] = "Todos os campos são obrigatórios!";
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: cadastro_fornecedor_novo.php');
        exit();
    }

    // Insere os dados na tabela de endereços
    $sql_endereco = "INSERT INTO enderecos (cep, logradouro, numero, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_endereco = $conn->prepare($sql_endereco);
    
    if ($stmt_endereco === false) {
        die('Erro na preparação da consulta de endereço: ' . htmlspecialchars($conn->error));
    }

    $stmt_endereco->bind_param("ssssss", $cep, $logradouro, $numero, $bairro, $cidade, $estado);
    
    if ($stmt_endereco->execute()) {
        $id_endereco = $stmt_endereco->insert_id; // Obtém o ID do endereço inserido
    } else {
        $_SESSION['mensagem'] = "Erro ao cadastrar endereço: " . htmlspecialchars($stmt_endereco->error);
        $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
        header('Location: cadastro_fornecedor_novo.php'); // Redireciona para a página de clientes
        exit();
    }
    
    $stmt_endereco->close();

    try {
        $sql_fornecedor = "INSERT INTO fornecedor (cpf_cnpj, nome, email, telefone, endereco_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_fornecedor = $conn->prepare($sql_fornecedor);
        
        if ($stmt_fornecedor === false) {
            die('Erro na preparação da consulta de fornecedor: ' . htmlspecialchars($conn->error));
        }

        // Corrigido: use $id_endereco em vez de $endereco_id
        $stmt_fornecedor->bind_param("ssssi", $cpf_cnpj, $nome, $email, $telefone, $id_endereco);
        
        if ($stmt_fornecedor->execute()) {
            // Armazena a mensagem de sucesso na sessão
            $_SESSION['mensagem'] = 'Fornecedor e endereço cadastrados com sucesso!';
            $_SESSION['tipo_mensagem'] = 'success'; // Tipo de mensagem para sucesso
            header('Location: cadastro_fornecedores.php'); // Redireciona para a página de clientes
            exit();
        } else {
            $_SESSION['mensagem'] = "Erro ao cadastrar fornecedor: " . htmlspecialchars($stmt_fornecedor->error);
            $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
            header('Location: cadastro_fornecedor_novo.php'); // Redireciona para a página de clientes
            exit();
        }

    } catch (mysqli_sql_exception $e) {
        // Captura o erro e armazena a mensagem de erro
        $_SESSION['mensagem'] = "Erro ao cadastrar fornecedor: " . htmlspecialchars ($e->getMessage());
        $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
        header('Location: cadastro_fornecedor_novo.php'); // Redireciona para a página de clientes
        exit();
    }
}

$conn->close();
?>