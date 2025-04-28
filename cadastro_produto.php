<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Obtém o termo de busca e o filtro de categoria
$pesquisa = isset($_GET['pesquisa']) ? trim($_GET['pesquisa']) : '';
$filtroCategoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0;

// Sanitiza as entradas
$pesquisa = htmlspecialchars($pesquisa, ENT_QUOTES, 'UTF-8');

// Consulta SQL corrigida para join com estoque
$sql = "SELECT p.id_produto, p.produto, c.nome AS categoria, p.preco, 
        COALESCE(e.quantidade_estoque, 0) AS quantidade, p.status 
        FROM produtos p
        LEFT JOIN categorias_produto c ON p.categoria_id = c.id_categoria
        LEFT JOIN (
            SELECT produto_id, quantidade_estoque 
            FROM estoque 
            WHERE id_estoque IN (
                SELECT MAX(id_estoque) 
                FROM estoque 
                GROUP BY produto_id
            )
        ) e ON p.id_produto = e.produto_id
        WHERE p.produto LIKE ?";

// Adiciona filtro de categoria se aplicável
if ($filtroCategoria > 0) {
    $sql .= " AND p.categoria_id = ?";
}

$sql .= " ORDER BY p.produto ASC";

// Prepara e executa a consulta
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}

$termoPesquisa = "%" . $pesquisa . "%";

if ($filtroCategoria > 0) {
    $stmt->bind_param("si", $termoPesquisa, $filtroCategoria);
} else {
    $stmt->bind_param("s", $termoPesquisa);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Erro na consulta: " . $stmt->error);
}

// Consulta para obter todas as categorias (precisa ser antes de fechar a conexão)
$sql_categoria = "SELECT id_categoria, nome FROM categorias_produto ORDER BY nome";
$resultado_categoria = $conn->query($sql_categoria);
$categorias = [];
if ($resultado_categoria) {
    while ($categoria = $resultado_categoria->fetch_assoc()) {
        $categorias[] = $categoria;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produtos - Meu Comercio de Bolso</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link rel="stylesheet" href="cadastro_produto.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <?php include_once 'navbar.php'; ?>

    <div class="container shadow box">
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <main class="main-content">
            <div class="container mt-4">
                <div class="content-header d-flex justify-content-between align-items-center">
                    <h1>Gerenciamento de Produtos</h1>
                    <div class="header-actions">
                        <a href="cadastro_produto_novo.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> Novo Produto
                        </a>
                        <button class="btn btn-secondary" onclick="exportProducts()">
                            <i class="fas fa-file-export"></i> Exportar
                        </button>
                        <a href="cadastro_categoria.php" class="btn btn-warning text-white">
                            <i class="fas fa-plus"></i> Nova Categoria
                        </a>
                    </div>
                </div>
                <!-- barra de pesquisa -->
                <div class="col-md-8">
                    <form id="searchForm" method="GET" action="">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="pesquisaProduto" name="pesquisa" class="form-control" placeholder="Buscar produtos..." value="<?= htmlspecialchars($pesquisa) ?>">
                            <select id="filtroCategoria" name="categoria" class="form-select">
                                <option value="0">Todas as Categorias</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>" <?= $categoria['id_categoria'] == $filtroCategoria ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary" type="submit">Busca</button>
                        </div>
                    </form>
                </div>
            </div>
        </main><br>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Quantidade no Estoque</th>
                        <th>Status do Produto</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['produto'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['categoria'] ?? 'Sem categoria', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>R$ <?= number_format($row['preco'] ?? 0, 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($row['quantidade'] ?? 0, ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge bg-<?= $row['status'] == 'ativo' ? 'success' : ($row['status'] == 'esgotado' ? 'danger' : 'warning') ?>">
                                        <?= htmlspecialchars($row['status'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href='editar_produto.php?id=<?= intval($row['id_produto']) ?>' class='btn btn-warning btn-sm text-white'>
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal" data-id="<?= intval($row['id_produto']) ?>" data-produto="<?= htmlspecialchars($row['produto']) ?>">
                                        <i class="fas fa-trash"></i> Excluir
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan='6' class="text-center">Nenhum produto encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o produto <strong id="produtoNome"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Esta ação não pode ser desfeita!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <form method="POST" action="excluir_produto_conn.php" class="d-inline">
                        <input type="hidden" name="id_produto" id="modalProdutoId" value="">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Confirmar Exclusão
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configura o modal de exclusão com os dados do produto
        document.addEventListener('DOMContentLoaded', function() {
            var deleteModal = document.getElementById('confirmDeleteModal');
            deleteModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var productId = button.getAttribute('data-id');
                var productName = button.getAttribute('data-produto');

                document.getElementById('modalProdutoId').value = productId;
                document.getElementById('produtoNome').textContent = productName;
            });
        });

        function exportProducts() {
            // Implemente sua função de exportação aqui
            alert('Função de exportação será implementada aqui');
        }
    </script>
</body>

</html>