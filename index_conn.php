<?php
require_once 'conexao_pdo.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitiza e valida as entradas
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
    $senha = filter_input(INPUT_POST, 'senha', FILTER_SANITIZE_STRING);

    try {
        // Preparar a consulta
        $stmt = $pdo->prepare("SELECT id_login, senha, email, foto, dante FROM login WHERE usuario = :usuario");
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->execute();
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->execute();

        // Verifica se o usuário existe
        if ($stmt->rowCount() === 1) {
            // Obtém os dados do usuário
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stored_password = $row['senha']; // A senha armazenada no banco

            // Verifica se a senha é válida
            if (password_verify($senha, $stored_password)) {
                // Login bem-sucedido, configurar as variáveis de sessão
                $_SESSION['dante'] = $row['dante'];  // Armazena o papel do usuário (por exemplo, 'admin', 'funcionario')
                $_SESSION['usuario'] = $usuario;     // Armazena o nome do usuário
                $_SESSION['email'] = $row['email'];  // Armazena o email do usuário
                $_SESSION['logado'] = true;          // Marca o usuário como logado
                $_SESSION['foto'] = $row['foto'];    // Armazena a foto do usuário

                // Redireciona para a página inicial
                header('Location: home.php');
                exit;
            } else {
                // Senha inválida
                $_SESSION['mensagem'] = 'Senha inválida!';
                $_SESSION['tipo_mensagem'] = 'danger';
                header('Location: index.php');
                exit;
            }
        } else {
            // Usuário não encontrado
            $_SESSION['mensagem'] = 'Usuário não encontrado!';
            $_SESSION['tipo_mensagem'] = 'danger';  
            header('Location: index.php');
            exit; 
        }
    } catch (PDOException $e) {
        $_SESSION['mensagem'] = "Erro ao verificar login: " . $e->getMessage();
        $_SESSION['tipo_mensagem'] = 'danger';   
        header('Location: index.php');
        exit; 
    }
}
?>