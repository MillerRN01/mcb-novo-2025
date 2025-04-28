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
    <title>Cadastrar Cliente - Pessoa Jurídica</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<style>
    body {
        background-color: #f9f9f9;
    }

    .container {
        background-color: #ffffff;
        max-width: 600px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    h2 {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: bold;
    }

    .btn-primary {
        background-color: #ff9800;
        border-color: #ff9800;
    }

    .btn-primary:hover {
        background-color: #fb8c00;
        border-color: #fb8c00;
    }

    .btn-secondary {
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .btn-secondary:hover {
        background-color: #ffca28;
        border-color: #ffca28;
    }

    .alert {
        margin-top: 20px;
        background-color: #fff3e0;
        color: #ff6f20;
    }
</style>

<body>

    <div class="container mt-5">
        <h2>Cadastrar Cliente - Pessoa Jurídica</h2>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="basicas-tab" data-bs-toggle="tab" href="#basicas" role="tab"
                    aria-controls="basicas" aria-selected="true">Informações Básicas</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="adicionais-tab" data-bs-toggle="tab" href="#adicionais" role="tab"
                    aria-controls="adicionais" aria-selected="false">Informações Adicionais</a>
            </li>
        </ul>

        <form method="POST" action="cadastrar_cliente_pj_conn.php">
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="basicas" role="tabpanel" aria-labelledby="basicas-tab">
                <div class="mb-3">
                        <label for="razao_social" class="form-label">Nome da Impresa</label>
                        <input type="text" class="form-control" id="razao_social" name="razao_social" required placeholder="Digite a razão social da empresa">
                    </div>
                    <div class="mb-3">
                        <label for="cnpj" class="form-label">CNPJ</label>
                        <input type="text" name="cnpj" id="cnpj" class="form-control" maxlength="18" required placeholder="Seu CNPJ" title="Apenas números são permitidos."oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="Digite o email do cliente">
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" class="form-control" id="telefone" name="telefone"placeholder="Digite o telefone (ex: +55 11 91234-5678)" maxlength="15" title="Apenas números são permitidos." oninput="formatarTelefone(this);">
                    </div>
                    <div class="mb-3">
                        <label for="nome_fantasia" class="form-label">Nome Fantasia</label>
                        <input type="text" class="form-control" id="nome_fantasia" name="nome_fantasia"placeholder="Digite o nome fantasia da empresa">
                    </div>
                    <div class="mb-3">
                        <label for="ie" class="form-label">Inscrição Estadual</label>
                        <input type="text" class="form-control" id="ie" name="ie"placeholder="Digite a Inscrição Estadual">
                    </div>
                </div>
                <div class="tab-pane fade" id="adicionais" role="tabpanel" aria-labelledby="adicionais-tab">
                    <div class="mb-3">
                        <label for="whatsapp" class="form-label">WhatsApp</label>
                        <input type="text" class="form-control" id="whatsapp" name="whatsapp" maxlength="15"placeholder="Digite o número do WhatsApp (ex: +55 11 91234-5678)"title="Apenas números são permitidos." oninput="formatarTelefone(this);">
                    </div>
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes"
                            placeholder="Digite suas observações aqui"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="cep" name="cep" onblur="buscarEndereco()" required
                            placeholder="CEP" oninput="formatarCep(this);">
                    </div>
                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="endereco" name="endereco" required
                            placeholder="Endereço">
                    </div>
                    <div class="mb-3">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero" required placeholder="Número">
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
                        <input type="text" class="form-control" id="estado" name="estado" required maxlength="2"pattern="[A-Z]{2}" title="Por favor, insira a sigla do estado (ex: SP, RJ)"oninput="this.value = this.value.toUpperCase()" placeholder="Estado (sigla)">
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <button type="submit" class="btn btn-primary">Cadastrar</button>
                <a href="cadastro_cliente.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formata CPF/CNPJ
        function formatarCpfCnpj(input) {
            const value = input.value.replace(/\D/g, ''); // Remove tudo que não é número

            if (value.length <= 11) {
                // Formata CPF (000.000.000-00)
                input.value = value.replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{2})$/, '$1-$2');
            } else {
                // Formata CNPJ (00.000.000/0000-00)
                input.value = value.replace(/(\d{2})(\d)/, '$1.$2') // Adiciona o primeiro ponto
                    .replace(/(\d{3})(\d)/, '$1.$2') // Adiciona o segundo ponto
                    .replace(/(\d{3})(\d)/, '$1/$2') // Adiciona a barra
                    .replace(/(\d{4})(\d{2})$/, '$1-$2'); // Adiciona o traço
            }
        }

        // Adicionando o evento de formatação ao campo CNPJ
        document.getElementById('cnpj').addEventListener('input', function (event) {
            formatarCpfCnpj(this); // Formatar CNPJ
        });

       
// Busca endereço via API ViaCEP
function buscarEndereco() {
    const cep = document.getElementById('cep').value.replace(/\D/g, '');

    // Valida o CEP
    if (cep.length !== 8) {
        alert('CEP inválido. Deve conter 8 dígitos.');
        return;
    }

    // Exibe um feedback visual (opcional)
    document.getElementById('endereco').value = 'Buscando...';
    document.getElementById('bairro').value = '';
    document.getElementById('cidade').value = '';
    document.getElementById('estado').value = '';

    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (!data.erro) {
                document.getElementById('endereco').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                document.getElementById('cidade').value = data.localidade || ''; // Corrigido para localidade
                document.getElementById('estado').value = data.uf || '';
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

        // Formata Telefone/WhatsApp para números brasileiros e internacionais
        function formatarTelefone(input) {
            const value = input.value.replace(/\D/g, '');

            // Se começar com código de país (ex: 55 para Brasil)
            if (value.length > 11) {
                input.value = value.replace(/(\d{2})(\d)/, '+$1 $2')
                    .replace(/(\d{2})(\d)/, '$1 $2')
                    .replace(/(\d{4,5})(\d{4})$/, '$1-$2');
            }
            // Formato brasileiro padrão
            else if (value.length > 2) {
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

        // Adicionando os eventos
        document.addEventListener('DOMContentLoaded', function () {
            // CPF
            document.getElementById('cpf').addEventListener('input', function (event) {
                formatarCpfCnpj(this);
            });

            // RG
            document.getElementById('rg').addEventListener('input', function (event) {
                formatarRG(this);
            });

            // Telefone
            document.getElementById('telefone').addEventListener('input', function (event) {
                formatarTelefone(this);
            });

            // WhatsApp
            document.getElementById('whatsapp').addEventListener('input', function (event) {
                formatarTelefone(this);
            });

            // CEP
            document.getElementById('cep').addEventListener('input', function (event) {
                formatarCep(this);
            });

            // Auto-completar endereço quando sair do campo CEP
            document.getElementById('cep').addEventListener('blur', buscarEndereco);
        });
    </script>
</body>

</html>