<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtém os dados do formulário
    $id_cliente = intval($_POST['id_cliente']);
    $tipo_cliente = $_POST['tipo_cliente'];
    $endereco_id = intval($_POST['endereco_id']);
    
    // Dados do endereço
    $cep = $_POST['cep'];
    $logradouro = $_POST['logradouro'];
    $numero = $_POST['numero'];
    $complemento = $_POST['complemento'] ?? null;
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    
    // Dados comuns
    $email = $_POST['email'];
    $telefone = $_POST['telefone'];
    $whatsapp = $_POST['whatsapp'] ?? null;
    $limite_credito = floatval($_POST['limite_credito']);
    $status = $_POST['status'];
    $observacoes = $_POST['observacoes'] ?? null;

    // Inicia transação para garantir consistência
    $conn->begin_transaction();

    try {
        // Atualiza o endereço primeiro
        $sql_endereco = "UPDATE enderecos SET 
                        cep = ?, 
                        logradouro = ?, 
                        numero = ?, 
                        complemento = ?, 
                        bairro = ?, 
                        cidade = ?, 
                        estado = ? 
                        WHERE id_endereco = ?";
        
        $stmt_endereco = $conn->prepare($sql_endereco);
        $stmt_endereco->bind_param('sssssssi', $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $endereco_id);
        $stmt_endereco->execute();
        $stmt_endereco->close();

        // Atualiza os dados específicos do tipo de cliente
        if ($tipo_cliente === 'pf') {
            $nome = $_POST['nome'];
            $cpf = $_POST['cpf'];
            $rg = $_POST['rg'] ?? null;
            $data_nascimento = $_POST['data_nascimento'] ?? null;
            
            $sql_cliente = "UPDATE clientes_pf SET 
                          nome = ?, 
                          cpf = ?, 
                          rg = ?, 
                          data_nascimento = ?, 
                          email = ?, 
                          telefone = ?, 
                          whatsapp = ?, 
                          limite_credito = ?, 
                          status = ?, 
                          observacoes = ? 
                          WHERE id_cliente = ?";
            
            $stmt_cliente = $conn->prepare($sql_cliente);
            $stmt_cliente->bind_param('sssssssdssi', $nome, $cpf, $rg, $data_nascimento, $email, $telefone, $whatsapp, $limite_credito, $status, $observacoes, $id_cliente);
        } else {
            $razao_social = $_POST['razao_social'];
            $nome_fantasia = $_POST['nome_fantasia'] ?? null;
            $cnpj = $_POST['cnpj'];
            $ie = $_POST['ie'] ?? null;
            
            $sql_cliente = "UPDATE clientes_pj SET 
                          razao_social = ?, 
                          nome_fantasia = ?, 
                          cnpj = ?, 
                          ie = ?, 
                          email = ?, 
                          telefone = ?, 
                          whatsapp = ?, 
                          limite_credito = ?, 
                          status = ?, 
                          observacoes = ? 
                          WHERE id_cliente = ?";
            
            $stmt_cliente = $conn->prepare($sql_cliente);
            $stmt_cliente->bind_param('sssssssdssi', $razao_social, $nome_fantasia, $cnpj, $ie, $email, $telefone, $whatsapp, $limite_credito, $status, $observacoes, $id_cliente);
        }

        // Executa a atualização do cliente
        $stmt_cliente->execute();
        $stmt_cliente->close();

        // Confirma a transação
        $conn->commit();

        $_SESSION['mensagem'] = 'Cliente atualizado com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
        header('Location: cadastro_cliente.php');
        exit();
    } catch (Exception $e) {
        // Em caso de erro, desfaz a transação
        $conn->rollback();
        
        $_SESSION['mensagem'] = 'Erro ao atualizar cliente: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: editar_cadastro_cliente.php?id=' . $id_cliente . '&tipo=' . $tipo_cliente);
        exit();
    }
} else {
    // Se não for POST, redireciona
    $_SESSION['mensagem'] = 'Método de requisição inválido';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: cadastro_cliente.php');
    exit();
}