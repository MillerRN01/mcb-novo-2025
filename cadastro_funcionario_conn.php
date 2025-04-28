<?php
include_once 'conexao.php';
require_once 'verifica_session_conn.php';

// Adicionar novo funcionário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_funcionario'])) {
    // Dados Básicos
    $nome_completo = trim($_POST['nome_completo']);
    $cpf = preg_replace('/[^0-9]/', '', trim($_POST['cpf']));
    $telefone = preg_replace('/[^0-9]/', '', trim($_POST['telefone']));
    $cargo = trim($_POST['cargo']);
    $data_admissao = date('Y-m-d'); // Data atual como data de admissão
    
    // Dados de Acesso
    $usuario = trim($_POST['usuario']); // ESSE CAMPO É OBRIGATÓRIO
    $email = trim($_POST['email']);
    $senha = password_hash(trim($_POST['senha']), PASSWORD_DEFAULT);
    $dante = isset($_POST['dante']) ? trim($_POST['dante']) : 'funcionario';
    
    // Dados de Endereço
    $cep = isset($_POST['cep']) ? preg_replace('/[^0-9]/', '', trim($_POST['cep'])) : null;
    $logradouro = trim($_POST['endereco'] ?? '');
    $numero = trim($_POST['numero'] ?? '');
    $complemento = trim($_POST['complemento'] ?? '');
    $bairro = trim($_POST['bairro'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $estado = trim($_POST['estado'] ?? '');

    // Validação dos campos obrigatórios
    $camposObrigatorios = [
        'Nome Completo' => $nome_completo,
        'Usuário' => $usuario, // ADICIONADO NA VALIDAÇÃO
        'Email' => $email,
        'CPF' => $cpf,
        'Telefone' => $telefone,
        'Cargo' => $cargo,
        'Senha' => $_POST['senha']
    ];
    
    foreach ($camposObrigatorios as $campo => $valor) {
        if (empty($valor)) {
            $_SESSION['mensagem'] = "Por favor, preencha o campo {$campo}";
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: tela_cadastro.php'); 
            exit();
        }
    }

    // Verificar se as senhas coincidem
    if ($_POST['senha'] !== $_POST['confirmar_senha']) {
        $_SESSION['mensagem'] = "As senhas não coincidem!";
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php'); 
        exit();
    }

    // Verificar se CPF já existe
    $stmt_check = $conn->prepare("SELECT id_funcionario FROM funcionario WHERE cpf = ?");
    if (!$stmt_check) {
        $_SESSION['mensagem'] = "Erro ao preparar consulta de CPF: " . $conn->error;
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }
    
    $stmt_check->bind_param("s", $cpf);
    if (!$stmt_check->execute()) {
        $_SESSION['mensagem'] = "Erro ao verificar CPF: " . $stmt_check->error;
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensagem'] = "CPF já cadastrado no sistema!";
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }
    $stmt_check->close();

    // Verificar se usuário já existe
    $stmt_check = $conn->prepare("SELECT id_login FROM login WHERE usuario = ?");
    if (!$stmt_check) {
        $_SESSION['mensagem'] = "Erro ao preparar consulta de usuário: " . $conn->error;
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }
    
    $stmt_check->bind_param("s", $usuario);
    if (!$stmt_check->execute()) {
        $_SESSION['mensagem'] = "Erro ao verificar usuário: " . $stmt_check->error;
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $_SESSION['mensagem'] = "Nome de usuário já está em uso!";
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }
    $stmt_check->close();

    // Iniciar transação
    $conn->begin_transaction();

    try {
        // 1. Inserir login
        $stmt_login = $conn->prepare("INSERT INTO login (usuario, email, senha, dante) VALUES (?, ?, ?, ?)");
        if (!$stmt_login) {
            throw new Exception("Erro ao preparar inserção de login: " . $conn->error);
        }
        
        $stmt_login->bind_param("ssss", $usuario, $email, $senha, $dante);
        if (!$stmt_login->execute()) {
            throw new Exception("Erro ao inserir login: " . $stmt_login->error);
        }
        
        $login_id = $stmt_login->insert_id;
        $stmt_login->close();

        // 2. Inserir endereço (se fornecido)
        $endereco_id = null;
        if (!empty($cep) && !empty($logradouro) && !empty($numero) && !empty($bairro) && !empty($cidade) && !empty($estado)) {
            $stmt_endereco = $conn->prepare("INSERT INTO enderecos 
                (cep, logradouro, numero, complemento, bairro, cidade, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt_endereco) {
                throw new Exception("Erro ao preparar inserção de endereço: " . $conn->error);
            }
            
            $stmt_endereco->bind_param("sssssss", $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado);
            if (!$stmt_endereco->execute()) {
                throw new Exception("Erro ao inserir endereço: " . $stmt_endereco->error);
            }
            
            $endereco_id = $stmt_endereco->insert_id;
            $stmt_endereco->close();
        }

        // 3. Inserir funcionário
        $stmt_funcionario = $conn->prepare("INSERT INTO funcionario 
            (nome_completo, cargo, cpf, telefone, endereco_id, login_id, data_admissao, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo')");
        
        if (!$stmt_funcionario) {
            throw new Exception("Erro ao preparar inserção de funcionário: " . $conn->error);
        }
        
        $stmt_funcionario->bind_param("ssssiis", 
            $nome_completo, $cargo, $cpf, $telefone, 
            $endereco_id, $login_id, $data_admissao);
        
        if (!$stmt_funcionario->execute()) {
            throw new Exception("Erro ao inserir funcionário: " . $stmt_funcionario->error);
        }
        
        $funcionario_id = $stmt_funcionario->insert_id;
        $stmt_funcionario->close();

        $conn->commit();
        
        $_SESSION['mensagem'] = "Funcionário cadastrado com sucesso!";
        $_SESSION['tipo_mensagem'] = 'success';
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['mensagem'] = "Erro ao cadastrar funcionário: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
    }
    
    header('Location: cadastro_funcionario.php'); 
    exit();
}
?>