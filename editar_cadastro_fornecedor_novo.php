<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Inicializa as variáveis
$fornecedor = null;

// Verifica se um ID foi passado na URL
if (isset($_GET['id'])) {
    $id = (int) $_GET['id']; // Obtém o ID do fornecedor

    // Prepara a consulta para buscar os dados do fornecedor
    $sql = "SELECT cf.*, e.* FROM fornecedor cf LEFT JOIN enderecos e ON cf.endereco_id = e.id_endereco WHERE cf.id_fornecedor = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . $conn->error);
    }

    // Vincula o parâmetro
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // Verifica se o fornecedor foi encontrado
    if ($resultado->num_rows > 0) {
        $fornecedor = $resultado->fetch_assoc(); // Obtém os dados do fornecedor
    } else {
        echo "Fornecedor não encontrado.";
        exit(); // Para evitar que o restante do código seja executado
    }
} else {
    echo "ID do fornecedor não fornecido.";
    exit(); // Para evitar que o restante do código seja executado
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Fornecedor</title>
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
</style>

<body>
    <div class="container mt-4">
        <h1>Editar Fornecedor</h1>
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <ul class="nav nav-tabs mb-4" id="fornecedorTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="basicas-tab" data-bs-toggle="tab" data-bs-target="#basicas" type="button" role="tab" aria-controls="basicas" aria-selected="true">Informações Básicas</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="adicionais-tab" data-bs-toggle="tab" data-bs-target="#adicionais" type="button" role="tab" aria-controls="adicionais" aria-selected="false">Informações de Endereço</button>
            </li>
        </ul>

        <form method="POST" action="editar_cadastro_fornecedor_novo_conn.php">
        <input type="hidden" name="id_fornecedor" value="<?php echo htmlspecialchars($fornecedor['id_fornecedor'] ?? ''); ?>">
        <input type="hidden" name="endereco_id" value="<?php echo htmlspecialchars($fornecedor['endereco_id'] ?? ''); ?>">

            <div class="tab-content">
                <div class="tab-pane fade show active" id="basicas" role="tabpanel" aria-labelledby="basicas-tab">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" value="<?php echo htmlspecialchars($fornecedor['nome'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="cpf_cnpj" class="form-label">CNPJ/CPF</label>
                        <input type="text" name="cpf_cnpj" id="cpf_cnpj" class="form-control" value="<?php echo htmlspecialchars($fornecedor['cpf_cnpj'] ?? ''); ?>" maxlength="18" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($fornecedor['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" value="<?php echo htmlspecialchars($fornecedor['telefone'] ?? ''); ?>" maxlength="15" required>
                    </div>
                </div>
                <div class="tab-pane fade" id="adicionais" role="tabpanel" aria-labelledby="adicionais-tab">
                    <div class="mb-3">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" name="cep" id="cep" class="form-control" value="<?php echo htmlspecialchars($fornecedor['cep'] ?? ''); ?>" maxlength="9" required>
                    </div>
                    <div class="mb-3">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" name="endereco" id="endereco" class="form-control" value="<?php echo htmlspecialchars($fornecedor['logradouro'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" name="numero" id="numero" class="form-control" value="<?php echo htmlspecialchars($fornecedor['numero'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="complemento" class="form-label">Complemento</label>
                        <input type="text" name="complemento" id="complemento" class="form-control" value="<?php echo htmlspecialchars($fornecedor['complemento'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" name="bairro" id="bairro" class="form-control" value="<?php echo htmlspecialchars($fornecedor['bairro'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" name="cidade" id="cidade" class="form-control" value="<?php echo htmlspecialchars($fornecedor['cidade'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <input type="text" name="estado" id="estado" class="form-control" value="<?php echo htmlspecialchars($fornecedor['estado'] ?? ''); ?>" maxlength="2" required>
                    </div>
                </div>
            </div>
            <div class="header-actions mt-3">
                <a href="cadastro_fornecedores.php" class="btn btn-danger">Cancelar</a>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Formata CPF/CNPJ
        function formatarCpfCnpj(input) {
            let value = input.value.replace(/\D/g, '');

            if (value.length <= 11) {
                // Formata CPF (000.000.000-00)
                value = value.replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
                // Formata CNPJ (00.000.000/0000-00)
                value = value.replace(/^(\d{2})(\d)/, '$1.$2')
                    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/\.(\d{3})(\d)/, '.$1/$2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            }

            input.value = value;
        }

        // Busca endereço via API ViaCEP
        function buscarEndereco() {
            const cep = document.getElementById('cep').value.replace(/\D/g, '');

            if (cep.length !== 8) {
                alert('CEP deve conter 8 dígitos');
                return;
            }

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('endereco').value = data.logradouro || '';
                        document.getElementById('bairro').value = data.bairro || '';
                        document.getElementById('cidade').value = data.localidade || '';
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

        // Formata Telefone (XX) XXXXX-XXXX
        function formatarTelefone(input) {
            let value = input.value.replace(/\D/g, '');

            if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                if (value.length > 10) {
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                }
            }

            input.value = value;
        }

        // Formata CEP (XXXXX-XXX)
        function formatarCep(input) {
            let value = input.value.replace(/\D/g, '');

            if (value.length > 5) {
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
            }

            input.value = value;
        }

        // Adiciona os eventos quando o DOM estiver carregado
        document.addEventListener('DOMContentLoaded', function() {
            // Configura os eventos de input
            document.getElementById('cpf_cnpj').addEventListener('input', function() {
                formatarCpfCnpj(this);
            });

            document.getElementById('telefone').addEventListener('input', function() {
                formatarTelefone(this);
            });

            document.getElementById('cep').addEventListener('input', function() {
                formatarCep(this);
            });

            // Configura o evento de blur para buscar o endereço
            document.getElementById('cep').addEventListener('blur', function() {
                buscarEndereco();
            });

            // Formata os campos com valores iniciais
            if (document.getElementById('cpf_cnpj').value) {
                formatarCpfCnpj(document.getElementById('cpf_cnpj'));
            }

            if (document.getElementById('telefone').value) {
                formatarTelefone(document.getElementById('telefone'));
            }

            if (document.getElementById('cep').value) {
                formatarCep(document.getElementById('cep'));
            }
        });
    </script>
</body>

</html>