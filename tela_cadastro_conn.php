<?php
require_once 'conexao_pdo.php';
session_start(); // Adicionado - faltava iniciar a sessão

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtendo e limpando os dados do formulário
    $nome_completo = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $dante = trim($_POST['dante'] ?? 'funcionario');
    $senha = $_POST['senha'] ?? '';
    $data_admissao = trim($_POST['data_admissao'] ?? date('Y-m-d')); // Adicione este campo ao formulário

    // Verificando se todos os campos obrigatórios estão preenchidos
    if (empty($nome_completo) || empty($email) || empty($usuario) || empty($senha) || empty($data_admissao)) {
        $_SESSION['mensagem'] = "Todos os campos são obrigatórios!";
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }

    // Validando o formato do e-mail
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensagem'] = "E-mail inválido!";
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }

    // Validação de senha (mínimo de 8 caracteres, 1 número e 1 caractere especial)
    if (strlen($senha) < 8 || !preg_match('/[0-9]/', $senha) || !preg_match('/[\W_]/', $senha)) {
        $_SESSION['mensagem'] = "A senha deve ter pelo menos 8 caracteres, 1 número e 1 caractere especial.";
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }

    // Hash da senha para armazenamento seguro
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Processamento da foto (se houver upload)
    $fotoPath = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $foto = $_FILES['foto'];
        $uploadDir = 'assets/uploads/funcionario/';

        // Garantindo que o diretório de uploads exista
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Gerando um nome único para o arquivo
        $fotoPath = $uploadDir . uniqid() . '_' . basename($foto['name']);

        // Tipos de arquivo permitidos
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($foto['type'], $allowedTypes)) {
            $_SESSION['mensagem'] = "Tipo de arquivo não permitido. Apenas JPG, PNG e GIF são aceitos.";
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: tela_cadastro.php');
            exit();
        }

        // Verificando o tamanho do arquivo (máximo de 2MB)
        if ($foto['size'] > 2 * 1024 * 1024) {
            $_SESSION['mensagem'] = "O arquivo é muito grande. Tamanho máximo permitido é 2MB.";
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: tela_cadastro.php');
            exit();
        }

        // Movendo o arquivo para o diretório de uploads
        if (!move_uploaded_file($foto['tmp_name'], $fotoPath)) {
            $_SESSION['mensagem'] = "Erro ao fazer upload da foto.";
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: tela_cadastro.php');
            exit();
        }
    }

    try {
        $pdo->beginTransaction();

        // Verificando se o e-mail ou o nome de usuário já existem no banco de dados
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login WHERE email = :email OR usuario = :usuario");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':usuario', $usuario);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['mensagem'] = "E-mail ou usuário já estão em uso!";
            $_SESSION['tipo_mensagem'] = 'danger';
            header('Location: tela_cadastro.php');
            exit();
        }

        // Inserindo os dados de login
        $stmt = $pdo->prepare("INSERT INTO login (usuario, email, senha, foto, dante) 
                              VALUES (:usuario, :email, :senha, :foto, :dante)");
        $stmt->bindParam(':usuario', $usuario);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt->bindParam(':foto', $fotoPath);
        $stmt->bindParam(':dante', $dante);
        $stmt->execute();
        
        $login_id = $pdo->lastInsertId();

        // Inserindo os dados do funcionário
        $stmt = $pdo->prepare("INSERT INTO funcionario 
                              (nome_completo, login_id, data_admissao, status) 
                              VALUES (:nome_completo, :login_id, :data_admissao, 'ativo')");
        $stmt->bindParam(':nome_completo', $nome_completo);
        $stmt->bindParam(':login_id', $login_id);
        $stmt->bindParam(':data_admissao', $data_admissao);
        $stmt->execute();

        $pdo->commit();

        // Redirecionando para a página inicial após o cadastro
        $_SESSION['mensagem'] = "Cadastro realizado com sucesso!";
        $_SESSION['tipo_mensagem'] = 'success';
        header("Location: carregamento_login.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['mensagem'] = "Erro ao cadastrar: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';
        header('Location: tela_cadastro.php');
        exit();
    }
}