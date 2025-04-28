<?php
include_once 'conexao.php';
require_once 'verifica_session_conn.php';

// Verifica se o método de requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtém os dados do formulário
    $id_funcionario = $_POST['id_funcionario'];
    $nome_completo = $_POST['nome_completo'];
    $cpf = $_POST['cpf'];
    $telefone = $_POST['telefone'];
    $data_admissao = $_POST['data_admissao'];
    $cargo = $_POST['cargo'];
    $status = $_POST['status'];
    $usuario = $_POST['usuario'];
    $email = $_POST['email'];
    $cep = $_POST['cep'];
    $logradouro = $_POST['logradouro']; 
    $numero = $_POST['numero'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $complemento = $_POST['complemento'];

    // Inicia a transação
    $conn->begin_transaction();

    try {
        // 1. Atualiza os dados de login
        $sqlLogin = "UPDATE login SET usuario = ?, email = ? WHERE id_login = (SELECT login_id FROM funcionario WHERE id_funcionario = ?)";
        $stmtLogin = $conn->prepare($sqlLogin);
        $stmtLogin->bind_param("ssi", $usuario, $email, $id_funcionario);
        $stmtLogin->execute();
        $stmtLogin->close();

        // 2. Atualiza os dados de endereço (CORREÇÃO: 8 parâmetros - 7 strings e 1 inteiro)
        $sqlEndereco = "UPDATE enderecos SET cep = ?, logradouro = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ? WHERE id_endereco = (SELECT endereco_id FROM funcionario WHERE id_funcionario = ?)";
        $stmtEndereco = $conn->prepare($sqlEndereco);
        $stmtEndereco->bind_param("sssssssi", $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $id_funcionario);
        $stmtEndereco->execute();
        $stmtEndereco->close();

        // 3. Atualiza os dados do funcionário
        $sqlFuncionario = "UPDATE funcionario SET nome_completo = ?, cpf = ?, telefone = ?, data_admissao = ?, cargo = ?, status = ? WHERE id_funcionario = ?";
        $stmtFuncionario = $conn->prepare($sqlFuncionario);
        $stmtFuncionario->bind_param("ssssssi", $nome_completo, $cpf, $telefone, $data_admissao, $cargo, $status, $id_funcionario);
        $stmtFuncionario->execute();
        $stmtFuncionario->close();

        // 4. Atualiza a senha se foi fornecida
        if (!empty($_POST['senha']) && $_POST['senha'] === $_POST['confirmar_senha']) {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $sqlSenha = "UPDATE login SET senha = ? WHERE id_login = (SELECT login_id FROM funcionario WHERE id_funcionario = ?)";
            $stmtSenha = $conn->prepare($sqlSenha);
            $stmtSenha->bind_param("si", $senha, $id_funcionario);
            $stmtSenha->execute();
            $stmtSenha->close();
        }

        // Se tudo correr bem, confirma a transação
        $conn->commit();

        $_SESSION['mensagem'] = 'Funcionário atualizado com sucesso!';
        $_SESSION['tipo_mensagem'] = 'success';
        header('Location: cadastro_funcionario.php'); // Melhor redirecionar para a lista
        exit();
    } catch (Exception $e) {
        // Se ocorrer um erro, reverte a transação
        $conn->rollback();
        $_SESSION['mensagem'] = 'Erro ao atualizar funcionário: ' . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: editar_funcionario.php?id=' . $id_funcionario);
        exit();
    }
} else {
    $_SESSION['mensagem'] = 'Método de requisição inválido';
    $_SESSION['tipo_mensagem'] = 'danger';
    header('Location: editar_funcionario.php');
    exit();
}
?>