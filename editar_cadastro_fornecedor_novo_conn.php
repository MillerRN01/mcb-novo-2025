<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Coleta dos dados do formulário
    $id_fornecedor = (int)$_POST['id_fornecedor'];
    $endereco_id = (int)$_POST['endereco_id'];
    $nome = trim($_POST['nome']);
    $cpf_cnpj = trim($_POST['cpf_cnpj']);
    $email = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $cep = trim($_POST['cep']);
    $logradouro = trim($_POST['endereco']);
    $numero = trim($_POST['numero']);
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro']);
    $cidade = trim($_POST['cidade']);
    $estado = trim($_POST['estado']);

    // Validação dos campos obrigatórios
    $camposObrigatorios = [
        'Nome' => $nome,
        'CPF/CNPJ' => $cpf_cnpj,
        'Email' => $email,
        'CEP' => $cep,
        'Endereço' => $logradouro,
        'Número' => $numero,
        'Bairro' => $bairro,
        'Cidade' => $cidade,
        'Estado' => $estado
    ];

    $camposVazios = [];
    foreach ($camposObrigatorios as $campo => $valor) {
        if (empty($valor)) {
            $camposVazios[] = $campo;
        }
    }

    if (!empty($camposVazios)) {
        $_SESSION['mensagem'] = "Os seguintes campos são obrigatórios: " . implode(", ", $camposVazios);
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: editar_cadastro_fornecedor.php?id=' . $id_fornecedor);
        exit();
    }

    try {
        // Inicia transação
        $conn->begin_transaction();

        // 1. Atualiza o endereço
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
        if (!$stmt_endereco) {
            throw new Exception("Erro ao preparar atualização de endereço: " . $conn->error);
        }
        
        $stmt_endereco->bind_param(
            "sssssssi", 
            $cep, 
            $logradouro, 
            $numero, 
            $complemento, 
            $bairro, 
            $cidade, 
            $estado, 
            $endereco_id
        );
        
        if (!$stmt_endereco->execute()) {
            throw new Exception("Erro ao atualizar endereço: " . $stmt_endereco->error);
        }
        $stmt_endereco->close();

        // 2. Atualiza o fornecedor
        $sql_fornecedor = "UPDATE fornecedor SET 
                          cpf_cnpj = ?, 
                          nome = ?, 
                          email = ?, 
                          telefone = ?
                          WHERE id_fornecedor = ?";
        
        $stmt_fornecedor = $conn->prepare($sql_fornecedor);
        if (!$stmt_fornecedor) {
            throw new Exception("Erro ao preparar atualização de fornecedor: " . $conn->error);
        }
        
        $stmt_fornecedor->bind_param(
            "ssssi", 
            $cpf_cnpj, 
            $nome, 
            $email, 
            $telefone, 
            $id_fornecedor
        );
        
        if (!$stmt_fornecedor->execute()) {
            throw new Exception("Erro ao atualizar fornecedor: " . $stmt_fornecedor->error);
        }
        $stmt_fornecedor->close();

        // Confirma a transação
        $conn->commit();

        // Mensagem de sucesso
        $_SESSION['mensagem'] = 'Fornecedor e endereço atualizados com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
        header('Location: cadastro_fornecedores.php');
        exit();

    } catch (Exception $e) {
        // Desfaz a transação em caso de erro
        if (isset($conn) && $conn) {
            $conn->rollback();
        }
        
        $_SESSION['mensagem'] = "Erro ao atualizar: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: editar_cadastro_fornecedor.php?id=' . $id_fornecedor);
        exit();
    }
} else {
    // Se não for POST, redireciona
    $_SESSION['mensagem'] = 'Método de requisição inválido!';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: cadastro_fornecedores.php');
    exit();
}
?>