<?php
require_once 'conexao_pdo.php';
include_once 'mensagem_por_session_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela de Login</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            margin: auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-primary {
            width: 100%;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
        }

        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .error-message i {
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <div class="login-container">
            <h2>Bem Vindo</h2>
            <!-- Exibição de mensagens -->
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($mensagem) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <form action="index_conn.php" method="post">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required>
                </div>
                <div class="mb-3">
                    <label for="senha" class="form-label">Senha</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="senha" name="senha" required>
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                            <i class="bi bi-eye-slash" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Entrar</button>
            </form>

            <div class="register-link">
                <a href="tela_cadastro.php" class="text-decoration-none">Não tem uma conta? Cadastre-se aqui!</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('senha');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function() {
            // Alterna o tipo de entrada entre "password" e "text"
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Alterna o ícone entre "eye" e "eye-slash"
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>

</html>