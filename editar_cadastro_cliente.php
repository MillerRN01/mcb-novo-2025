<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Verifica se o ID do cliente e o tipo foram passados na URL
if (isset($_GET['id']) && isset($_GET['tipo'])) {
    $id_cliente = intval($_GET['id']);
    $tipo_cliente = $_GET['tipo'];

    // Define a tabela com base no tipo de cliente
    $tabela = $tipo_cliente === 'pf' ? 'clientes_pf' : 'clientes_pj';

    // Recupera os dados do cliente com informações de endereço
    $sql = "SELECT c.*, e.cep, e.logradouro, e.numero, e.complemento, e.bairro, e.cidade, e.estado 
            FROM $tabela c 
            LEFT JOIN enderecos e ON c.endereco_id = e.id_endereco 
            WHERE c.id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id_cliente);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verifica se o cliente foi encontrado
    if ($resultado->num_rows > 0) {
        $cliente = $resultado->fetch_assoc();
    } else {
        die('Cliente não encontrado.');
    }
} else {
    die('ID do cliente ou tipo não especificado.');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente - MeuComerciodeBolso</title>
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
            --warning-color: #f6c23e;
        }

        body {
            background-color: var(--secondary-color);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: 700;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #5a5c69;
        }

        .form-control,
        .form-select {
            padding: 0.75rem 1rem;
            border-radius: 0.35rem;
            border: 1px solid #d1d3e2;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: var(--error-color);
            background-image: none;
        }

        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            box-shadow: 0 0 0 0.2rem rgba(231, 74, 59, 0.25);
        }

        .invalid-feedback {
            color: var(--error-color);
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .nav-tabs {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs .nav-link {
            color: #5a5c69;
            font-weight: 600;
            border: none;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s;
        }

        .nav-tabs .nav-link:hover {
            color: var(--primary-color);
            border: none;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: transparent;
            border: none;
            border-bottom: 3px solid var(--primary-color);
        }

        .tab-content {
            background-color: white;
            border-radius: 0 0 0.35rem 0.35rem;
            padding: 1.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 0.35rem;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 0.35rem;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
        }

        .required-field::after {
            content: " *";
            color: var(--error-color);
        }

        /* Animação para campos inválidos */
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            20%,
            60% {
                transform: translateX(-5px);
            }

            40%,
            80% {
                transform: translateX(5px);
            }
        }

        .is-invalid {
            animation: shake 0.5s;
        }

        .loading-cep {
            display: none;
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }

        .loading-cep i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .input-group-text {
            background-color: #eaecf4;
            border: 1px solid #d1d3e2;
        }

        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .status-ativo {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-inativo {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>
</head>

<body>

    <div class="container mt-5">
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <i class="fas <?= $tipo_mensagem == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h2><i class="fas fa-user-edit me-2"></i>Editar Cliente <?= $tipo_cliente === 'pf' ? 'Pessoa Física' : 'Pessoa Jurídica' ?></h2>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basicas-tab" data-bs-toggle="tab" data-bs-target="#basicas" type="button" role="tab" aria-controls="basicas" aria-selected="true">
                    <i class="fas fa-user me-2"></i>Informações Básicas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="adicionais-tab" data-bs-toggle="tab" data-bs-target="#adicionais" type="button" role="tab" aria-controls="adicionais" aria-selected="false">
                    <i class="fas fa-map-marker-alt me-2"></i>Endereço
                </button>
            </li>
        </ul>

        <form method="POST" action="editar_cadastro_cliente_conn.php" id="editClienteForm" novalidate>
            <input type="hidden" name="id_cliente" value="<?= $id_cliente ?>">
            <input type="hidden" name="tipo_cliente" value="<?= $tipo_cliente ?>">
            <input type="hidden" name="endereco_id" value="<?= $cliente['endereco_id'] ?? '' ?>">

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="basicas" role="tabpanel" aria-labelledby="basicas-tab">
                    <?php if ($tipo_cliente === 'pf'): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label required-field">Nome Completo</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="nome" name="nome"
                                        value="<?= htmlspecialchars($cliente['nome'] ?? '') ?>" required>
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <div class="invalid-feedback">Por favor, insira o nome completo.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label required-field">CPF</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="cpf" name="cpf"
                                        value="<?= htmlspecialchars($cliente['cpf'] ?? '') ?>" required
                                        oninput="formatarCpf(this)">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                </div>
                                <div class="invalid-feedback">Por favor, insira um CPF válido.</div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="rg" class="form-label">RG</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="rg" name="rg"
                                        value="<?= htmlspecialchars($cliente['rg'] ?? '') ?>">
                                    <span class="input-group-text"><i class="fas fa-address-card"></i></span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="data_nascimento" class="form-label">Data de Nascimento</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="data_nascimento" name="data_nascimento"
                                        value="<?= htmlspecialchars($cliente['data_nascimento'] ?? '') ?>">
                                    <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="razao_social" class="form-label required-field">Razão Social</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="razao_social" name="razao_social"
                                        value="<?= htmlspecialchars($cliente['razao_social'] ?? '') ?>" required>
                                    <span class="input-group-text"><i class="fas fa-building"></i></span>
                                </div>
                                <div class="invalid-feedback">Por favor, insira a razão social.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia"
                                        value="<?= htmlspecialchars($cliente['nome_fantasia'] ?? '') ?>">
                                    <span class="input-group-text"><i class="fas fa-store"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cnpj" class="form-label required-field">CNPJ</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="cnpj" name="cnpj"
                                        value="<?= htmlspecialchars($cliente['cnpj'] ?? '') ?>" required
                                        oninput="formatarCnpj(this)">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                </div>
                                <div class="invalid-feedback">Por favor, insira um CNPJ válido.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="ie" class="form-label">Inscrição Estadual</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="ie" name="ie"
                                        value="<?= htmlspecialchars($cliente['ie'] ?? '') ?>">
                                    <span class="input-group-text"><i class="fas fa-file-alt"></i></span>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label required-field">Email</label>
                            <div class="input-group">
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= htmlspecialchars($cliente['email'] ?? '') ?>" required>
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                            <div class="invalid-feedback">Por favor, insira um e-mail válido.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="telefone" class="form-label required-field">Telefone</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="telefone" name="telefone"
                                    value="<?= htmlspecialchars($cliente['telefone'] ?? '') ?>" required
                                    oninput="formatarTelefone(this)">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            </div>
                            <div class="invalid-feedback">Por favor, insira um telefone válido.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="whatsapp" class="form-label">WhatsApp</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="whatsapp" name="whatsapp"
                                    value="<?= htmlspecialchars($cliente['whatsapp'] ?? '') ?>"
                                    oninput="formatarTelefone(this)">
                                <span class="input-group-text"><i class="fab fa-whatsapp"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="limite_credito" class="form-label">Limite de Crédito (R$)</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" step="0.01" class="form-control" id="limite_credito" name="limite_credito"
                                    value="<?= htmlspecialchars($cliente['limite_credito'] ?? '0.00') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label required-field">Status</label>
                            <div class="input-group">
                                <select class="form-select" id="status" name="status" required>
                                    <option value="ativo" <?= ($cliente['status'] ?? '') === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                                    <option value="inativo" <?= ($cliente['status'] ?? '') === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                                </select>
                                <span class="input-group-text">
                                    <span class="status-badge status-<?= ($cliente['status'] ?? '') === 'ativo' ? 'ativo' : 'inativo' ?>">
                                        <?= ($cliente['status'] ?? '') === 'ativo' ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <div class="input-group">
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="1"><?= htmlspecialchars($cliente['observacoes'] ?? '') ?></textarea>
                                <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="adicionais" role="tabpanel" aria-labelledby="adicionais-tab">
                    <div class="mb-3">
                        <label for="cep" class="form-label required-field">CEP</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="cep" name="cep"
                                value="<?= htmlspecialchars($cliente['cep'] ?? '') ?>"
                                oninput="formatarCep(this)" required>
                            <button class="btn btn-outline-primary" type="button" onclick="buscarEndereco()">
                                <i class="fas fa-search me-1"></i>Buscar
                            </button>
                        </div>
                        <div class="loading-cep" id="loadingCep">
                            <i class="fas fa-spinner me-2"></i>Buscando endereço...
                        </div>
                        <div class="invalid-feedback">Por favor, insira um CEP válido.</div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="logradouro" class="form-label required-field">Logradouro</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="logradouro" name="logradouro"
                                    value="<?= htmlspecialchars($cliente['logradouro'] ?? '') ?>" required>
                                <span class="input-group-text"><i class="fas fa-road"></i></span>
                            </div>
                            <div class="invalid-feedback">Por favor, insira o logradouro.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="numero" class="form-label required-field">Número</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="numero" name="numero"
                                    value="<?= htmlspecialchars($cliente['numero'] ?? '') ?>" required>
                                <span class="input-group-text"><i class="fas fa-home"></i></span>
                            </div>
                            <div class="invalid-feedback">Por favor, insira o número.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="complemento" class="form-label">Complemento</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="complemento" name="complemento"
                                    value="<?= htmlspecialchars($cliente['complemento'] ?? '') ?>">
                                <span class="input-group-text"><i class="fas fa-info-circle"></i></span>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="bairro" class="form-label required-field">Bairro</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="bairro" name="bairro"
                                    value="<?= htmlspecialchars($cliente['bairro'] ?? '') ?>" required>
                                <span class="input-group-text"><i class="fas fa-map-marked-alt"></i></span>
                            </div>
                            <div class="invalid-feedback">Por favor, insira o bairro.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cidade" class="form-label required-field">Cidade</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="cidade" name="cidade"
                                    value="<?= htmlspecialchars($cliente['cidade'] ?? '') ?>" required>
                                <span class="input-group-text"><i class="fas fa-city"></i></span>
                            </div>
                            <div class="invalid-feedback">Por favor, insira a cidade.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estado" class="form-label required-field">Estado</label>
                            <div class="input-group">
                                <input type="text" class="form-control text-uppercase" id="estado" name="estado"
                                    value="<?= htmlspecialchars($cliente['estado'] ?? '') ?>" maxlength="2" required
                                    pattern="[A-Z]{2}" title="Por favor, insira a sigla do estado (ex: SP, RJ)"
                                    oninput="this.value = this.value.toUpperCase()">
                                <span class="input-group-text"><i class="fas fa-map-pin"></i></span>
                            </div>
                            <div class="invalid-feedback">Por favor, insira a sigla do estado (ex: SP).</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="cadastro_cliente.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formata CPF (000.000.000-00)
        function formatarCpf(input) {
            const value = input.value.replace(/\D/g, '');
            input.value = value.replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{2})$/, '$1-$2');
        }

        // Formata CNPJ (00.000.000/0000-00)
        function formatarCnpj(input) {
            const value = input.value.replace(/\D/g, '');
            input.value = value.replace(/(\d{2})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1/$2')
                .replace(/(\d{4})(\d{2})$/, '$1-$2');
        }

        // Busca endereço via API ViaCEP
        function buscarEndereco() {
            const cepInput = document.getElementById('cep');
            const cep = cepInput.value.replace(/\D/g, '');
            const loadingElement = document.getElementById('loadingCep');

            if (cep.length !== 8) {
                cepInput.classList.add('is-invalid');
                return;
            }

            // Mostra o loading
            loadingElement.style.display = 'block';

            // Limpa erros anteriores
            cepInput.classList.remove('is-invalid');

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    loadingElement.style.display = 'none';

                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                        document.getElementById('numero').focus();
                    } else {
                        cepInput.classList.add('is-invalid');
                        document.getElementById('logradouro').value = '';
                        document.getElementById('bairro').value = '';
                        document.getElementById('cidade').value = '';
                        document.getElementById('estado').value = '';
                        alert('CEP não encontrado. Preencha os campos manualmente.');
                    }
                })
                .catch(error => {
                    loadingElement.style.display = 'none';
                    console.error('Erro ao buscar CEP:', error);
                    alert('Erro ao buscar CEP. Verifique sua conexão.');
                });
        }

        // Formata Telefone (00) 00000-0000
        function formatarTelefone(input) {
            const value = input.value.replace(/\D/g, '');

            if (value.length > 2) {
                input.value = value.replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{5})(\d{4})$/, '$1-$2');
            }
        }

        // Formata CEP (00000-000)
        function formatarCep(input) {
            const value = input.value.replace(/\D/g, '');
            if (value.length > 5) {
                input.value = value.replace(/(\d{5})(\d)/, '$1-$2');
            } else {
                input.value = value;
            }
        }

        // Validação do formulário
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editClienteForm');
            const inputs = form.querySelectorAll('input, select, textarea');

            // Validação em tempo real quando o usuário sai do campo
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });

                // Remove a classe de erro quando o usuário começa a digitar
                input.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        this.classList.remove('is-invalid');
                    }
                });
            });

            // Validação ao enviar o formulário
            form.addEventListener('submit', function(event) {
                let formIsValid = true;
                let firstInvalid = null;

                inputs.forEach(input => {
                    if (!validateField(input)) {
                        formIsValid = false;

                        if (!firstInvalid) {
                            firstInvalid = input;
                        }
                    }
                });

                if (!formIsValid) {
                    event.preventDefault();
                    event.stopPropagation();

                    // Mostra a aba com o primeiro erro
                    if (firstInvalid) {
                        const tabPane = firstInvalid.closest('.tab-pane');
                        if (tabPane && !tabPane.classList.contains('active')) {
                            const tabId = tabPane.id;
                            const tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
                            if (tabButton) {
                                new bootstrap.Tab(tabButton).show();
                            }
                        }

                        firstInvalid.focus();
                    }
                }
            });

            // Atualiza o badge de status quando o select muda
            document.getElementById('status').addEventListener('change', function() {
                const statusBadge = document.querySelector('.status-badge');
                if (this.value === 'ativo') {
                    statusBadge.className = 'status-badge status-ativo';
                    statusBadge.textContent = 'Ativo';
                } else {
                    statusBadge.className = 'status-badge status-inativo';
                    statusBadge.textContent = 'Inativo';
                }
            });

            // Função para validar campos individuais
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

                // Validação específica para CPF
                if (field.id === 'cpf' && field.value.trim()) {
                    const cpf = field.value.replace(/\D/g, '');
                    if (cpf.length !== 11) {
                        field.classList.add('is-invalid');
                        return false;
                    }
                }

                // Validação específica para CNPJ
                if (field.id === 'cnpj' && field.value.trim()) {
                    const cnpj = field.value.replace(/\D/g, '');
                    if (cnpj.length !== 14) {
                        field.classList.add('is-invalid');
                        return false;
                    }
                }

                // Validação específica para CEP
                if (field.id === 'cep' && field.value.trim()) {
                    const cep = field.value.replace(/\D/g, '');
                    if (cep.length !== 8) {
                        field.classList.add('is-invalid');
                        return false;
                    }
                }

                // Validação específica para estado (UF)
                if (field.id === 'estado' && field.value.trim()) {
                    if (field.value.length !== 2) {
                        field.classList.add('is-invalid');
                        return false;
                    }
                }

                return true;
            }
        });
    </script>
</body>

</html>