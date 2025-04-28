<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';

// Processa o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $razao_social = $_POST['razao_social'] ?? '';
    $nome_fantasia = $_POST['nome_fantasia'] ?? '';
    $cnpj = $_POST['cnpj'] ?? '';
    $ie = $_POST['ie'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    
    // Dados do endereço
    $cep = $_POST['cep'] ?? '';
    $logradouro = $_POST['endereco'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $cidade = $_POST['cidade'] ?? '';

    // Valida campos obrigatórios
    if (empty($razao_social) || empty($cnpj) || empty($email) || empty($telefone)) {
        $_SESSION['mensagem'] = 'Por favor, preencha todos os campos obrigatórios.';
        $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
        header('Location: cadastrar_cliente_pj.php'); // Redireciona para a página de cadastro PJ
        exit();
    } else {
        // Verifica se o CNPJ já está cadastrado
        $sqlCheck = "SELECT COUNT(*) FROM clientes_pj WHERE cnpj = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param("s", $cnpj);
        $stmtCheck->execute();
        $stmtCheck->bind_result($count);
        $stmtCheck->fetch();
        $stmtCheck->close();

        if ($count > 0) {
            $_SESSION['mensagem'] = 'CNPJ já cadastrado. Por favor, verifique os dados.';
            $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
            header('Location: cadastrar_cliente_pj.php'); // Redireciona para a página de cadastro PJ
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
                $sql = "INSERT INTO clientes_pj (razao_social, nome_fantasia, cnpj, ie, email, telefone, whatsapp, observacoes, endereco_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    die('Erro na preparação da consulta: ' . htmlspecialchars($conn->error));
                }

                $tipo_pessoa = 'pj'; // Define o tipo como pessoa jurídica
                $status = 'ativo';  // Define o status padrão como ativo

                // Adiciona os dados ao bind_param
                $stmt->bind_param(
                    "ssssssssss",
                    $razao_social,
                    $nome_fantasia,
                    $cnpj,
                    $ie,
                    $email,
                    $telefone,
                    $whatsapp,
                    $observacoes,
                    $id_endereco,
                    $status
                );

                if ($stmt->execute()) {
                    $_SESSION['mensagem'] = 'Cliente cadastrado com sucesso!';
                    $_SESSION['tipo_mensagem'] = 'success'; // Tipo de mensagem para sucesso
                    header('Location: cadastro_cliente.php'); // Redireciona para a página de clientes
                    exit();
                } else {
                    $_SESSION['mensagem'] = 'Erro ao cadastrar cliente: ' . htmlspecialchars($stmt->error);
                    $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
                    header('Location: cadastrar_cliente_pj.php'); // Redireciona para a página de cadastro PJ
                    exit();
                }
            } else {
                $_SESSION['mensagem'] = 'Erro ao cadastrar endereço: ' . htmlspecialchars($stmt_endereco->error);
                $_SESSION['tipo_mensagem'] = 'danger'; // Tipo de mensagem para erro
                header('Location: cadastrar_cliente_pj.php'); // Redireciona para a página de cadastro PJ
                exit();
            }
        }
    }
}
?>