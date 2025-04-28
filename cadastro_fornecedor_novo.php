<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Fornecedor</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    /* Estilo geral do corpo */
    body {
        background-color: #f8f9fa;
        font-family: Arial, sans-serif;
    }

    /* Container do formulário */
    .container {
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin: 20px auto;
        max-width: 600px;
    }
    
    .header-actions {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }
</style>

<body>
    <div class="container mt-4">
        <h1>Cadastrar Fornecedor</h1>
          <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <ul class="nav nav-tabs mb-4" id="fornecedorTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basicas-tab" data-bs-toggle="tab" data-bs-target="#basicas"
                    type="button" role="tab" aria-controls="basicas" aria-selected="true">Informações Básicas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="adicionais-tab" data-bs-toggle="tab" data-bs-target="#adicionais"
                    type="button" role="tab" aria-controls="adicionais" aria-selected="false">Informações
                    Adicionais</button>
            </li>
        </ul>

        <form method="POST" action="cadastro_fornecedor_novo_conn.php">
            <div class="tab-content" id="fornecedorTabsContent">
                <!-- Aba de Informações Básicas -->
                <div class="tab-pane fade show active" id="basicas" role="tabpanel" aria-labelledby="basicas-tab">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Fornecedor</label>
                        <input type="text" class="form-control" id="nome" name="nome" required placeholder="Nome do Fornecedor">
                    </div>
                    <div class="mb-3">
                        <label for="cpf_cnpj" class="form-label">CNPJ/CPF</label>
                        <input type="text" class="form-control" id="cpf_cnpj" name="cpf_cnpj" oninput="formatarCpfCnpj(this)" maxlength="18" required placeholder="CNPJ ou CPF">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email do Fornecedor</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Email do Fornecedor">
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone do Fornecedor</label>
                        <input type="text" class="form-control" id="telefone" name="telefone" placeholder="Telefone do Fornecedor" maxlength="15" oninput="formatarTelefone(this)">
                    </div>
                </div>

                <!-- Aba de Informações Adicionais -->
                <div class="tab-pane fade" id="adicionais" role="tabpanel" aria-labelledby="adicionais-tab">
                    <div class="mb-3">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="cep" name="cep" onblur="buscarEndereco()" required placeholder="CEP" maxlength="9" oninput="formatarCep(this)">
                    </div>
                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="endereco" name="endereco" required placeholder="Endereço">
                    </div>
                    <div class="mb-3">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero" required placeholder="Número" maxlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" required placeholder="Bairro">
                    </div>
                    <div class="mb-3">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade" required placeholder="Cidade">
                    </div>
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <input type="text" class="form-control" id="estado" name="estado" required maxlength="2" pattern="[A-Z]{2}" title="Por favor, insira a sigla do estado (ex: SP, RJ)" oninput="this.value = this.value.toUpperCase()" placeholder="Estado (sigla)">
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="cadastro_fornecedores.php" class="btn btn-danger btn-sm">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-sm">Cadastrar Fornecedor</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formata CPF/CNPJ
        function formatarCpfCnpj(input) {
            const value = input.value.replace(/\D/g, '');

            if (value.length <= 11) {
                // Formata CPF (000.000.000-00)
                input.value = value.replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{2})$/, '$1-$2');
            } else {
                // Formata CNPJ (00.000.000/0000-00)
                input.value = value.replace(/(\d{2})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1/$2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            }
        }

        // Busca endereço via API ViaCEP
        function buscarEndereco() {
            const cep = document.getElementById('cep').value.replace(/\D/g, '');

            if (cep.length !== 8) return;

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('endereco').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
                        document.getElementById('estado').value = data.uf || '';
                        // Foca no campo número após preencher
                        document.getElementById('numero').focus();
                    } else {
                        alert('CEP não encontrado');
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar CEP:', error);
                    alert('Erro ao buscar CEP. Verifique sua conexão.');
                });
        }

        // Formata Telefone/WhatsApp para números brasileiros
        function formatarTelefone(input) {
            const value = input.value.replace(/\D/g, '');
            
            // Formato brasileiro padrão (XX) XXXX-XXXX ou (XX) XXXXX-XXXX
            if (value.length > 2) {
                input.value = value.replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4,5})(\d{4})$/, '$1-$2');
            }
        }

        // Formata CEP (xxxxx-xxx)
        function formatarCep(input) {
            const value = input.value.replace(/\D/g, '');
            if (value.length > 5) {
                input.value = value.replace(/(\d{5})(\d)/, '$1-$2');
            } else {
                input.value = value;
            }
        }

        // Adicionando os eventos quando o DOM estiver carregado
        document.addEventListener('DOMContentLoaded', function () {
            // Telefone
            document.getElementById('telefone').addEventListener('input', function (event) {
                formatarTelefone(this);
            });

            // CEP
            document.getElementById('cep').addEventListener('input', function (event) {
                formatarCep(this);
            });
        });
    </script>
</body>
</html>