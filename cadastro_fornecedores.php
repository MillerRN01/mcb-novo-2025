<?php
require_once 'conexao.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Captura os filtros
$pesquisa_fornecedor = isset($_POST['pesquisa_fornecedor']) ? trim($_POST['pesquisa_fornecedor']) : '';
$filtroCadastro = isset($_POST['filtroCadastro']) ? $_POST['filtroCadastro'] : 'nome';
$status_fornecedor = isset($_POST['status_fornecedor']) ? $_POST['status_fornecedor'] : '';

// Monta a consulta SQL para os fornecedores e seus endereços
$sql = "SELECT cf.*, e.* FROM fornecedor cf LEFT JOIN enderecos e ON cf.endereco_id = e.id_endereco WHERE 1=1";
$params = [];

// Adiciona filtro de pesquisa
if (!empty($pesquisa_fornecedor)) {
    if ($filtroCadastro === 'status' && !empty($status_fornecedor)) {
        $sql .= " AND cf.status = ?";
        $params[] = $status_fornecedor;
    } else {
        $sql .= " AND cf.$filtroCadastro LIKE ?";
        $params[] = "%" . $pesquisa_fornecedor . "%";
    }
}

// Monta a consulta SQL para contar os fornecedores
$count_sql = "SELECT COUNT(*) as total FROM fornecedor cf LEFT JOIN enderecos e ON cf.endereco_id = e.id_endereco WHERE 1=1";
$count_params = [];

// Adiciona filtro de pesquisa
if (!empty($pesquisa_fornecedor)) {
    if ($filtroCadastro === 'status' && !empty($status_fornecedor)) {
        $count_sql .= " AND cf.status = ?";
        $count_params[] = $status_fornecedor;
    } else {
        $count_sql .= " AND cf.$filtroCadastro LIKE ?";
        $count_params[] = "%" . $pesquisa_fornecedor . "%";
    }
}

// Prepara e executa a consulta de contagem
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt === false) {
    die('Erro na preparação da consulta: ' . htmlspecialchars($conn->error));
}

if (!empty($count_params)) {
    $types = str_repeat('s', count($count_params)); // Corrigido para usar apenas 's'
    $count_stmt->bind_param($types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_itens = $count_result->fetch_assoc()['total'];
$count_stmt->close();

// Reutilizando a consulta para obter os fornecedores
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Erro na preparação da consulta: ' . htmlspecialchars($conn->error));
}

if (!empty($params)) {
    // Ajuste para incluir os parâmetros corretamente
    $types = str_repeat('s', count($params)); // Corrigido para usar apenas 's'
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - Sistema de Vendas</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<style>
    #container_pesquisa {
        width: 900px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 15px;
        background-color: #ffffff;
        border-radius: 0.5rem;
    }

    /* Estilo geral do corpo */
    body {
        background-color: #f8f9fa;
        font-family: Arial, sans-serif;
    }

    .text-uniform {
        font-family: Arial, sans-serif;
        /* Altere para a fonte desejada */
        font-size: 14px;
        /* Altere para o tamanho desejado */
    }
</style>

<body>
    <?php include_once 'navbar.php'; ?>

    <!-- Filtros e Pesquisa -->
    <div class="container mt-4" id="container_pesquisa">
        <h1>Gerenciamento de Fornecedores</h1>
        
        <div class="filters-section mb-4">
            <form method="POST" action="" class="custom-width">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" name="pesquisa_fornecedor" class="form-control"
                        placeholder="Buscar fornecedores..."
                        value="<?php echo htmlspecialchars($pesquisa_fornecedor); ?>">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="cadastro_fornecedor_novo.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> Novo Fornecedor
                    </a>
                    <a href="exporta_fornecedor.php" class="btn btn-secondary">
                        <i class="fas fa-file-export"></i> Exportar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="container mt-4">
    <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <!-- Lista de Fornecedores -->
        <div class="fornecedores-grid" id="fornecedoresGrid">
            <div class="row">
                <?php
                $stmt = $conn->prepare($sql);
                if (!empty($params)) {
                    $types = str_repeat('s', count($params)); // Use apenas count($params)
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $resultado = $stmt->get_result();

                // Verifica se há resultados
                if ($resultado->num_rows > 0) {
                    while ($row = $resultado->fetch_assoc()) {
                        echo '<div class="col-md-3 mb-3">'; // Muda de col-md-4 para col-md-3
                        echo '    <div class="card shadow-sm border-light">';
                        echo '        <div class="card-body">';
                        echo '            <h5 class="card-title">' . htmlspecialchars($row["nome"] ?? 'Sem nome disponível') . '</h5>';

                        echo '            <p class="card-text text-uniform"><i class="fas fa-id-card"></i> CNPJ/CPF: ' . htmlspecialchars($row["cpf_cnpj"] ?? 'Sem CPF/CNPJ disponível') . '</p>';
                        echo '            <p class="card-text text-uniform"><i class="fas fa-envelope"></i> Email: ' . htmlspecialchars($row["email"] ?? 'Sem e-mail disponível') . '</p>';
                        echo '            <p class="card-text text-uniform"><i class="fas fa-phone"></i> Telefone: ' . htmlspecialchars($row["telefone"] ?? 'Sem telefone disponível') . '</p>';

                        echo '            <div class="button-container">';
                        echo '                <button class="btn btn-danger btn-sm" onclick="openDeleteModal(' . $row['id_fornecedor'] . ')" title="Excluir">';
                        echo '                    <i class="fas fa-trash-alt icon"></i> Excluir';
                        echo '                </button>';
                        echo '                <a href="editar_cadastro_fornecedor_novo.php?id=' . $row['id_fornecedor'] . '" class="btn btn-info btn-sm text-white" title="Editar">';
                        echo '                    <i class="fas fa-edit icon"></i> Editar';
                        echo '                </a>';
                        echo '                <button class="btn btn-primary btn-sm btn-custom-small expand-button" style="font-size: 12px; padding: 5px 10px; width: 80px;" onclick="toggleAdditionalInfo(this)">Ver mais</button>';
                        echo '            </div>';

                        echo '            <div class="additional-info" style="display: none;">';
                        echo '                <p class="text-uniform"><strong>Endereço:</strong> ' . htmlspecialchars($row["logradouro"] ?? 'Sem endereço disponível') . '</p>';
                        echo '                <p class="text-uniform"><strong>CEP:</strong> ' . htmlspecialchars($row["cep"] ?? 'Sem CEP disponível') . '</p>';
                        echo '                <p class="text-uniform"><strong>Bairro:</strong> ' . htmlspecialchars($row["bairro"] ?? 'Sem bairro disponível') . '</p>';
                        echo '                <p class="text-uniform"><strong>Estado:</strong> ' . htmlspecialchars($row["estado"] ?? 'Sem estado disponível') . '</p>';
                        echo '            </div>';
                        echo '        </div>';
                        echo '    </div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="no-results">Nenhum fornecedor encontrado.</p>'; // Mensagem caso não haja fornecedores
                }
                $stmt->close();
                $conn->close();
                ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Você tem certeza de que deseja excluir este fornecedor? Esta ação não pode ser desfeita.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Excluir</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        //ver infomação
        function toggleAdditionalInfo(button) {
            const additionalInfo = button.closest('.card-body').querySelector('.additional-info');
            if (additionalInfo.style.display === "none" || additionalInfo.style.display === "") {
                additionalInfo.style.display = "block";
                button.textContent = "Ver menos"; // Muda o texto do botão
            } else {
                additionalInfo.style.display = "none";
                button.textContent = "Ver mais"; // Restaura o texto do botão
            }
        }
    </script>
</body>

</html>