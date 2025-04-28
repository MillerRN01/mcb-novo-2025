<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = $_POST['nome'];
    $cpf = $_POST['cpf'];
    $rg = $_POST['rg'];
    $data_nascimento = $_POST['data_nascimento'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $whatsapp = $_POST['whatsapp'];
    $observacoes = $_POST['observacoes'];
    
    // Dados do endereço
    $cep = $_POST['cep'];
    $logradouro = $_POST['endereco'];
    $numero = $_POST['numero'];
    $bairro = $_POST['bairro'];
    $estado = $_POST['estado'];
    $cidade = $_POST['cidade'];

    // Verifica se o CPF já está cadastrado
    $sqlCheck = "SELECT COUNT(*) FROM clientes_pf WHERE cpf = ?";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("s", $cpf);
    $stmtCheck->execute();
    $stmtCheck->bind_result($count);
    $stmtCheck->fetch();
    $stmtCheck->close();

    // Verifica se os campos obrigatórios estão preenchidos


    if ($count > 0) {
        $_SESSION['mensagem'] = 'CPF já cadastrado. Por favor, verifique os dados.';
        $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
        header('Location: cadastro_cliente.php'); // Redireciona para a página de cadastro
        exit();
    } else {
        // Insere os dados na tabela de endereços
        $sql_endereco = "INSERT INTO enderecos (cep, logradouro, numero, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_endereco = $conn->prepare($sql_endereco);
        $stmt_endereco->bind_param("ssssss", $cep, $logradouro, $numero, $bairro, $cidade, $estado);
        
        if ($stmt_endereco->execute()) {
            $id_endereco = $stmt_endereco->insert_id; // Obtém o ID do endereço inserido
            $stmt_endereco->close();

            // Insere os dados no banco de dados
            $sql = "INSERT INTO clientes_pf (nome, cpf, rg, data_nascimento, email, telefone, whatsapp, observacoes, endereco_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            $stmt->bind_param("sssssssss",  $nome, $cpf, $rg, $data_nascimento, $email, $telefone, $whatsapp, $observacoes, $id_endereco);

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = 'Cliente cadastrado com sucesso!';
                $_SESSION['tipo_mensagem'] = 'success'; // Tipo de mensagem para sucesso
                header('Location: cadastro_cliente.php'); // Redireciona para a página de clientes
                exit();
            } else {
                $_SESSION['mensagem'] = 'Erro ao cadastrar cliente: ' . htmlspecialchars($stmt->error);
                $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
                header('Location: cadastrar_cliente_pf.php'); // Redireciona para a página de cadastro
                exit();
            }

            // Fecha a declaração
        } else {
            $_SESSION['mensagem'] = 'Erro ao cadastrar endereço: ' . htmlspecialchars($stmt_endereco->error);
            $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
            header('Location: cadastrar_cliente_pf.php'); // Redireciona para a página de cadastro
            exit();
        }
    }
}