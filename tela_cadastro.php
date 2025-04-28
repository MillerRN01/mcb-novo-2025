<?php
require_once 'conexao_pdo.php';
include_once 'mensagem_por_session_conn.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tela de Cadastro</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --error-color: #e74a3b;
            --success-color: #1cc88a;
        }
        
        body {
            background-color: var(--secondary-color);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .register-container {
            max-width: 600px;
            margin: 5rem auto;
            padding: 2.5rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }
        
        .form-label {
            font-weight: 600;
            color: #5a5c69;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            border: 1px solid #d1d3e2;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .form-control.is-invalid {
            border-color: var(--error-color);
            background-image: none;
        }
        
        .form-control.is-invalid:focus {
            box-shadow: 0 0 0 0.2rem rgba(231, 74, 59, 0.25);
        }
        
        .invalid-feedback {
            color: var(--error-color);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #858796;
            margin-top: 0.25rem;
            display: none;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem;
            font-weight: 600;
            width: 100%;
            border-radius: 0.35rem;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #858796;
        }
        
        .login-link a {
            color: var(--primary-color);
            font-weight: 600;
            transition: color 0.3s;
        }
        
        .login-link a:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .input-group-text {
            background-color: #eaecf4;
            border: 1px solid #d1d3e2;
        }
        
        .password-toggle {
            cursor: pointer;
            color: #6e707e;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        /* Animação para campos inválidos */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        
        .is-invalid {
            animation: shake 0.5s;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <h2><i class="fas fa-user-plus me-2"></i>Cadastro</h2>
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <i class="fas <?= $tipo_mensagem == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <form id="registrationForm" method="POST" action="tela_cadastro_conn.php" enctype="multipart/form-data" novalidate>
            <div class="mb-3">
                <label for="nome" class="form-label"><i class="fas fa-user me-1"></i> Nome Completo</label>
                <input type="text" class="form-control" id="nome" name="nome" required>
                <div class="invalid-feedback">Por favor, insira seu nome completo.</div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label"><i class="fas fa-envelope me-1"></i> E-mail</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <div class="invalid-feedback">Por favor, insira um e-mail válido.</div>
            </div>
            <div class="mb-3">
                <label for="usuario" class="form-label"><i class="fas fa-at me-1"></i> Nome de Usuário</label>
                <input type="text" class="form-control" id="usuario" name="usuario" required>
                <div class="invalid-feedback">Por favor, insira um nome de usuário.</div>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label"><i class="fas fa-lock me-1"></i> Senha</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="senha" name="senha" required>
                    <span class="input-group-text password-toggle" id="togglePassword">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
                <div class="password-requirements mt-2">
                    <small>
                        <i class="fas fa-info-circle me-1"></i>
                        A senha deve conter:
                        <ul class="mt-1 mb-0 ps-3">
                            <li class="req-length">Pelo menos 8 caracteres</li>
                            <li class="req-number">Pelo menos 1 número</li>
                            <li class="req-special">Pelo menos 1 caractere especial</li>
                        </ul>
                    </small>
                </div>
                <div class="invalid-feedback">A senha não atende aos requisitos.</div>
            </div>
            <div class="mb-3">
                <label for="dante" class="form-label"><i class="fas fa-user-tag me-1"></i> Tipo de Usuário</label>
                <select class="form-select" id="dante" name="dante" required>
                    <option value="" disabled selected>Selecione um tipo</option>
                    <option value="admin">Administrador</option>
                    <option value="funcionario">Funcionário</option>
                </select>
                <div class="invalid-feedback">Por favor, selecione um tipo de usuário.</div>
            </div>
            <div class="mb-4">
                <label for="foto" class="form-label"><i class="fas fa-camera me-1"></i> Foto de Perfil</label>
                <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                <small class="text-muted">(Opcional)</small>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i>Cadastrar
            </button>
        </form>
        <div class="login-link">
            Já tem uma conta? <a href="index.php">Faça login aqui!</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/esconder senha
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('senha');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });

        // Validação em tempo real
        const inputs = document.querySelectorAll('#registrationForm input, #registrationForm select');
        inputs.forEach(input => {
            // Validação quando o usuário sai do campo
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            // Remove a classe de erro quando o usuário começa a digitar
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                }
                
                // Validação específica para o campo de senha
                if (this.id === 'senha') {
                    validatePassword(this.value);
                }
            });
        });

        // Função para validar campos
        function validateField(field) {
            if (field.required && !field.value.trim()) {
                field.classList.add('is-invalid');
                return false;
            }
            
            // Validação específica para email
            if (field.type === 'email' && field.value.trim()) {
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(field.value)) {
                    field.classList.add('is-invalid');
                    return false;
                }
            }
            
            // Validação específica para senha
            if (field.id === 'senha' && field.value.trim()) {
                if (!validatePassword(field.value)) {
                    field.classList.add('is-invalid');
                    return false;
                }
            }
            
            return true;
        }

        // Função para validar a senha
        function validatePassword(password) {
            const passwordPattern = /^(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/;
            const isValid = passwordPattern.test(password);
            
            // Atualiza os requisitos visuais
            document.querySelector('.req-length').style.color = password.length >= 8 ? 'var(--success-color)' : 'inherit';
            document.querySelector('.req-number').style.color = /\d/.test(password) ? 'var(--success-color)' : 'inherit';
            document.querySelector('.req-special').style.color = /[!@#$%^&*(),.?":{}|<>]/.test(password) ? 'var(--success-color)' : 'inherit';
            
            // Mostra/oculta os requisitos
            const requirements = document.querySelector('.password-requirements');
            if (!isValid && password.length > 0) {
                requirements.style.display = 'block';
            } else {
                requirements.style.display = 'none';
            }
            
            return isValid;
        }

        // Validação do formulário ao ser enviado
        document.getElementById('registrationForm').addEventListener('submit', function(event) {
            let formIsValid = true;
            
            // Valida todos os campos
            inputs.forEach(input => {
                if (!validateField(input)) {
                    formIsValid = false;
                    
                    // Rola até o primeiro campo inválido
                    if (formIsValid === false) {
                        input.focus();
                    }
                }
            });
            
            // Validação adicional para a senha
            const senha = document.getElementById('senha').value;
            if (!validatePassword(senha)) {
                document.getElementById('senha').classList.add('is-invalid');
                formIsValid = false;
            }
            
            // Impede o envio se o formulário for inválido
            if (!formIsValid) {
                event.preventDefault();
                event.stopPropagation();
                
                // Encontra o primeiro campo inválido e dá foco
                const firstInvalid = document.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
            }
        });
    </script>
</body>

</html>