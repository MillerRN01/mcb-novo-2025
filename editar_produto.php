<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

if (isset($_GET['id'])) {
    $id_produto = intval($_GET['id']); // Captura o ID do produto da URL e converte para inteiro

    // Busca os dados do produto com informações de estoque
    $sql_produto = "SELECT p.*, c.nome AS categoria_nome, f.nome AS fornecedor_nome,
                   e.quantidade_estoque, e.estoque_minimo, e.estoque_maximo
                   FROM produtos p
                   LEFT JOIN categorias_produto c ON p.categoria_id = c.id_categoria
                   LEFT JOIN fornecedor f ON p.fornecedor_id = f.id_fornecedor
                   LEFT JOIN (
                       SELECT produto_id, quantidade_estoque, estoque_minimo, estoque_maximo
                       FROM estoque
                       WHERE id_estoque = (
                           SELECT MAX(id_estoque)
                           FROM estoque
                           WHERE produto_id = ?
                       )
                   ) e ON p.id_produto = e.produto_id
                   WHERE p.id_produto = ?";

    $stmt_produto = $conn->prepare($sql_produto);
    $stmt_produto->bind_param("ii", $id_produto, $id_produto);
    $stmt_produto->execute();
    $resultado_produto = $stmt_produto->get_result();

    if ($resultado_produto->num_rows === 0) {
        echo "Produto não encontrado.";
        exit;
    }

    $produto = $resultado_produto->fetch_assoc();

    // Busca categorias para o select
    $sql_categorias = "SELECT id_categoria, nome FROM categorias_produto ORDER BY nome";
    $resultado_categorias = $conn->query($sql_categorias);
    $categorias = [];
    if ($resultado_categorias) {
        while ($categoria = $resultado_categorias->fetch_assoc()) {
            $categorias[] = $categoria;
        }
    }

    // Busca fornecedores para o select
    $sql_fornecedores = "SELECT id_fornecedor, nome FROM fornecedor ORDER BY nome";
    $resultado_fornecedores = $conn->query($sql_fornecedores);
    $fornecedores = [];
    if ($resultado_fornecedores) {
        while ($fornecedor = $resultado_fornecedores->fetch_assoc()) {
            $fornecedores[] = $fornecedor;
        }
    }
} else {
    // Se o ID do produto não foi passado, redireciona ou exibe uma mensagem de erro
    $_SESSION['mensagem'] = "ID do produto não fornecido.";
    $_SESSION['tipo_mensagem'] = "danger";
    header("Location: cadastro_produto.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .required-field::after {
            content: " *";
            color: red;
        }

        body {
            background-color: #f8f9fa;
        }

        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            max-width: 600px;
        }

        h2 {
            color: #343a40;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <h2>Editar Produto</h2>
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <form method="POST" action="editar_produto_conn.php" enctype="multipart/form-data">
            <input type="hidden" name="id_produto" value="<?= $id_produto ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="produto" class="form-label required-field">Nome do Produto</label>
                    <input type="text" class="form-control" id="produto" name="produto" value="<?= htmlspecialchars($produto['produto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="preco" class="form-label required-field">Preço</label>
                    <input type="text" class="form-control" id="preco" name="preco"
                        value="<?= number_format($produto['preco'] ?? 0, 2, ',', '.') ?>" required
                        onfocus="limparFormato(event)" onblur="formatarMoeda(event)">
                </div>

                <div class="col-md-6">
                    <label for="quantidade_estoque" class="form-label required-field">Quantidade em Estoque</label>
                    <input type="number" class="form-control" id="quantidade_estoque" name="quantidade_estoque"
                        value="<?= htmlspecialchars($produto['quantidade_estoque'] ?? 0, ENT_QUOTES, 'UTF-8') ?>" required min="0">
                </div>

                <div class="col-md-6">
                    <label for="categoria_id" class="form-label required-field">Categoria</label>
                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>"
                                <?= ($categoria['id_categoria'] == $produto['categoria_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="fornecedor_id" class="form-label required-field">Fornecedor</label>
                    <select class="form-select" id="fornecedor_id" name="fornecedor_id" required>
                        <option value="">Nenhum fornecedor</option>
                        <?php foreach ($fornecedores as $fornecedor): ?>
                            <option value="<?= htmlspecialchars($fornecedor['id_fornecedor']) ?>"
                                <?= ($fornecedor['id_fornecedor'] == $produto['fornecedor_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fornecedor['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="status" class="form-label required-field">Status</label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="ativo" <?= ($produto['status'] == 'ativo') ? 'selected' : '' ?>>Ativo</option>
                        <option value="esgotado" <?= ($produto['status'] == 'esgotado') ? 'selected' : '' ?>>Esgotado</option>
                        <option value="vencido" <?= ($produto['status'] == 'vencido') ? 'selected' : '' ?>>Vencido</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="3"><?= htmlspecialchars($produto['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>

                <div class="col-md-4">
                    <label for="estoque_minimo" class="form-label">Estoque Mínimo</label>
                    <input type="number" class="form-control" id="estoque_minimo" name="estoque_minimo"
                        value="<?= htmlspecialchars($produto['estoque_minimo'] ?? 0, ENT_QUOTES, 'UTF-8') ?>" min="0">
                </div>

                <div class="col-md-4">
                    <label for="estoque_maximo" class="form-label">Estoque Máximo</label>
                    <input type="number" class="form-control" id="estoque_maximo" name="estoque_maximo"
                        value="<?= htmlspecialchars($produto['estoque_maximo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" min="0">
                </div>

                <div class="col-md-4">
                    <label for="data_validade" class="form-label">Data de Validade</label>
                    <input type="date" class="form-control" id="data_validade" name="data_validade"
                        value="<?= htmlspecialchars($produto['data_validade'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    <a href="cadastro_produto.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funções para formatação de moeda
        function limparFormato(e) {
            let valor = e.target.value.replace(/[^\d,-]/g, '').replace(',', '.');
            e.target.value = valor;
        }

        function formatarMoeda(e) {
            let valor = e.target.value;
            if (valor === '') return;

            valor = valor.replace(/[^\d,-]/g, '').replace(',', '.');
            valor = parseFloat(valor).toFixed(2);
            e.target.value = valor.replace('.', ',').replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.');
        }
    </script>
</body>

</html>