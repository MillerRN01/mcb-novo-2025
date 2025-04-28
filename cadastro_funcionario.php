<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

function sanitizeInput($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

// Inicializa a variável de pesquisa
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Consulta para listagem geral (sem detalhes de endereço)
try {
    $sql = "SELECT f.id_funcionario, f.nome_completo, l.usuario, l.email, f.cargo 
            FROM funcionario f
            JOIN login l ON f.login_id = l.id_login
            WHERE f.nome_completo LIKE ?"; // Adiciona a cláusula WHERE

    $stmt = $conn->prepare($sql);

    // Prepara o termo de pesquisa
    $searchParam = '%' . $searchTerm . '%'; // Adiciona os caracteres de wildcard
    $stmt->bind_param("s", $searchParam);

    $stmt->execute();
    $funcionarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $mensagem = "Erro ao carregar funcionários: " . $e->getMessage();
    $tipo_mensagem = "danger";
    $funcionarios = [];
}

// Inicializa variável para evitar undefined
$funcionario_detalhes = null;

// Busca detalhes apenas se houver ID na URL
if (isset($_GET['id_funcionario']) && is_numeric($_GET['id_funcionario'])) {
    $id = (int)$_GET['id_funcionario'];

    try {
        $sql = "SELECT f.*, e.*, l.email, l.usuario 
                FROM funcionario f
                JOIN enderecos e ON f.endereco_id = e.id_endereco
                JOIN login l ON f.login_id = l.id_login
                WHERE f.id_funcionario = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $funcionario_detalhes = $result->fetch_assoc();
        $stmt->close();
    } catch (Exception $e) {
        $mensagem = "Erro ao carregar detalhes: " . $e->getMessage();
        $tipo_mensagem = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Funcionários</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            margin-top: 20px;
            max-width: 1000px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            background-color: white;
        }

        body {
            background-color: #f8f9fa;
            padding-bottom: 60px;
        }

        h1 {
            color: #343a40;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            vertical-align: middle;
        }

        .btn-action {
            margin: 2px;
            min-width: 80px;
        }

        .table-responsive {
            margin-bottom: 20px;
        }

        .search-container {
            margin-bottom: 20px;
        }

        .tab-content {
            padding: 15px;
            border-left: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0 0 5px 5px;
        }

        .nav-tabs {
            margin-bottom: 0;
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 20px;
            padding-top: 30px;
            top: 50%;
            transform: translateY(-50%);
        }

        .password-container {
            position: relative;
        }
    </style>
</head>

<body>
    <?php include_once 'navbar.php'; ?>


    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users-cog me-2"></i>Gerenciamento de Funcionários</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFuncionarioModal">
                <i class="fas fa-user-plus me-1"></i> Adicionar Funcionário
            </button>
        </div>

        <!-- Barra de pesquisa -->
        <div class="search-container">
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" name="search" id="searchInput" class="form-control" placeholder="Pesquisar funcionários..." value="<?= htmlspecialchars($searchTerm) ?>">
                    <button class="btn btn-outline-secondary" type="submit" id="searchButton">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Exibição de mensagens -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Tabela de Funcionários -->
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="funcionariosTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%;">Nome Completo</th>
                        <th style="width: 20%;">Email</th>
                        <th style="width: 25%;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($funcionarios)): ?>
                        <?php foreach ($funcionarios as $funcionario): ?>
                            <tr>
                                <td><?= htmlspecialchars($funcionario['nome_completo'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($funcionario['email'] ?? 'N/A') ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm text-white" onclick="window.location.href='editar_funcionario.php?id=<?= $funcionario['id_funcionario'] ?>'">
                                        <i class="fas fa-edit me-1"></i> Editar
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-action" onclick="openDeleteModal(<?= $funcionario['id_funcionario'] ?>)">
                                        <i class="fas fa-trash-alt me-1"></i> Excluir
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Nenhum funcionário encontrado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Adicionar Funcionário -->
    <div class="modal fade" id="addFuncionarioModal" tabindex="-1" aria-labelledby="addFuncionarioModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addFuncionarioModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Adicionar Funcionário
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="cadastro_funcionario_conn.php" id="addFuncionarioForm">
                        <ul class="nav nav-tabs" id="addFuncionarioTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="dados-basicos-tab" data-bs-toggle="tab" data-bs-target="#dados-basicos" type="button" role="tab" aria-controls="dados-basicos" aria-selected="true">Dados Básicos</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="dados-acesso-tab" data-bs-toggle="tab" data-bs-target="#dados-acesso" type="button" role="tab" aria-controls="dados-acesso" aria-selected="false">Dados de Acesso</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="dados-endereco-tab" data-bs-toggle="tab" data-bs-target="#dados-endereco" type="button" role="tab" aria-controls="dados-endereco" aria-selected="false">Endereço</button>
                            </li>
                        </ul>

                        <div class="tab-content p-3 border border-top-0 rounded-bottom">
                            <!-- Dados Básicos -->
                            <div class="tab-pane fade show active" id="dados-basicos" role="tabpanel" aria-labelledby="dados-basicos-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nome_completo" class="form-label">Nome Completo *</label>
                                        <input type="text" name="nome_completo" class="form-control" required placeholder="Digite o nome completo">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cpf" class="form-label">CPF *</label>
                                        <input type="text" name="cpf" id="cpf" class="form-control" maxlength="14" required placeholder="000.000.000-00" oninput="formatarCpf(this)">
                                        <small class="text-muted">Apenas números</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="telefone" class="form-label">Telefone *</label>
                                        <input type="text" name="telefone" id="telefone" class="form-control" maxlength="14" required placeholder="(00) 00000-0000" oninput="formatarTelefone(this)">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="cargo" class="form-label">Cargo *</label>
                                    <select name="cargo" class="form-select" required>
                                        <option value="" disabled selected>Selecione um cargo</option>
                                        <option value="Gerente">Gerente</option>
                                        <option value="Vendedor">Vendedor</option>
                                        <option value="Atendente">Atendente</option>
                                        <option value="Estoquista">Estoquista</option>
                                        <option value="Financeiro">Financeiro</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Dados de Acesso -->
                            <div class="tab-pane fade" id="dados-acesso" role="tabpanel" aria-labelledby="dados-acesso-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="usuario" class="form-label">Usuário *</label>
                                        <input type="text" name="usuario" class="form-control" required placeholder="Digite o nome de usuário">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control" requiredplaceholder="exemplo@empresa.com">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3 password-container">
                                        <label for="senha" class="form-label">Senha *</label>
                                        <input type="password" name="senha" id="senha" class="form-control" requiredplaceholder="Digite a senha">
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('senha')"></i>
                                    </div>
                                    <div class="col-md-6 mb-3 password-container">
                                        <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" required placeholder="Confirme a senha">
                                        <i class="fas fa-eye password-toggle" onclick="togglePassword('confirmar_senha')"></i>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <small>
                                        <i class="fas fa-info-circle me-1"></i>A senha deve conter pelo menos 8 caracteres, incluindo letras maiúsculas, minúsculas e números.
                                    </small>
                                </div>
                            </div>

                            <!-- Dados de Endereço -->
                            <div class="tab-pane fade" id="dados-endereco" role="tabpanel" aria-labelledby="dados-endereco-tab">
                                <div class="mb-3">
                                    <label for="cep" class="form-label">CEP</label>
                                    <input type="text" class="form-control" id="cep" name="cep" onblur="buscarEndereco()" maxlength="9" required placeholder="CEP" oninput="formatarCep(this);">
                                </div>
                                <div class="mb-3">
                                    <label for="endereco" class="form-label">Endereço</label>
                                    <input type="text" class="form-control" id="endereco" name="endereco" required placeholder="Endereço">
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
                                    <input type="text" class="form-control" id="estado" name="estado" required maxlength="2" pattern="[A-Z]{2}" title="Por favor, insira a sigla do estado (ex: SP, RJ)" oninput="this.value = this.value.toUpperCase()" placeholder="Estado (sigla)">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="complemento" class="form-label">Complemento</label>
                                <textarea class="form-control" id="complemento" name="complemento" placeholder="Digite suas Complemento aqui"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary" name="add_funcionario">
                                    <i class="fas fa-save me-1"></i> Salvar Funcionário
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="excluir_funcionario_conn.php">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_funcionario" id="deleteFuncionarioId">
                        <p>Tem certeza que deseja excluir este funcionário?</p>
                        <p class="fw-bold">Esta ação não pode ser desfeita!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger" name="delete_funcionario">Confirmar Exclusão</button>
                    </div>
                </form>
            </div>
        </div>
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

        // Formata RG (opcional, formato comum: 00.000.000-0)
        function formatarRG(input) {
            const value = input.value.replace(/\D/g, '');
            if (value.length <= 8) {
                input.value = value.replace(/(\d{2})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d{1})$/, '$1-$2');
            } else {
                // Formato alternativo para RGs maiores
                input.value = value.replace(/(\d{2})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1.$2')
                    .replace(/(\d{3})(\d)/, '$1-$2');
            }
        }

        function togglePassword(inputId) {
            const inputField = document.getElementById(inputId);
            const inputType = inputField.getAttribute('type');

            if (inputType === 'password') {
                inputField.setAttribute('type', 'text');
            } else {
                inputField.setAttribute('type', 'password');
            }
        }

        // Busca endereço via API ViaCEP
        function buscarEndereco() {
            const cep = document.getElementById('cep').value.replace(/\D/g, '');

            if (cep.length !== 8) {
                alert('Por favor, insira um CEP válido.');
                return;
            }

            // Exibir um carregando ou feedback visual
            const loadingMessage = document.createElement('div');
            loadingMessage.innerText = 'Buscando endereço...';
            document.getElementById('dados-endereco').appendChild(loadingMessage);

            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    // Remover mensagem de carregando
                    loadingMessage.remove();

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
                    // Remover mensagem de carregando
                    loadingMessage.remove();
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
        document.addEventListener('DOMContentLoaded', function() {
            // CPF
            document.getElementById('cpf').addEventListener('input', function(event) {
                formatarCpfCnpj(this);
            });

            // Telefone
            document.getElementById('telefone').addEventListener('input', function(event) {
                formatarTelefone(this);
            });

            // WhatsApp
            document.getElementById('whatsapp').addEventListener('input', function(event) {
                formatarTelefone(this);
            });

            // CEP
            document.getElementById('cep').addEventListener('input', function(event) {
                formatarCep(this);
            });

            // Auto-completar endereço quando sair do campo CEP
            document.getElementById('cep').addEventListener('blur', buscarEndereco);
        });
        //modal delete
        // Função para abrir o modal de exclusão e definir o ID
        function openDeleteModal(id) {
            document.getElementById('deleteFuncionarioId').value = id;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
</body>

</html>