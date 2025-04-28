<?php
require_once 'conexao_pdo.php'; // Inclua sua conexão com o banco de dados
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Inicializa as mensagens de erro e sucesso
$categorias = [];
$fornecedores = [];

try {
    // Consulta para categorias
    $sql = "SELECT id_categoria, nome FROM categorias_produto ORDER BY nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categorias[] = $row; // Armazena o array associativo completo
        }
    }

    // Consulta para fornecedores
    $sql = "SELECT id_fornecedor, nome FROM fornecedor ORDER BY nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $fornecedores[] = $row; // Armazena o array associativo completo
        }
    }
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="pt_BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produto Novo</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<style>
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

<body>
    <div class="container mt-5">
        <h2>Cadastro de Produto</h2>
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" href="#informacoes-basicas" data-bs-toggle="tab">Informações Básicas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#informacoes-adicionais" data-bs-toggle="tab">Informações Adicionais</a>
            </li>
        </ul>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?= htmlspecialchars($tipo_mensagem) ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($mensagem) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário -->
        <form id="productForm" action="cadastro_produto_novo_conn.php" method="POST" enctype="multipart/form-data">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="informacoes-basicas">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="produto" class="form-label">Nome do Produto</label>
                            <input type="text" id="produto" name="produto" class="form-control" required placeholder="Nome do Produto">
                        </div>

                        <div class="col-12">
                            <label for="preco" class="form-label">Preço</label>
                            <input type="text" id="preco" name="preco" class="form-control" required placeholder="Preço do Produto" onfocus="limparFormato(event)" onblur="formatarMoeda(event)">
                        </div>
                        <div class="col-12">
                            <label for="quantidade_estoque" class="form-label">Quantidade</label>
                            <input type="number" id="quantidade_estoque" name="quantidade_estoque" class="form-control" required placeholder="Quantidade do Produto">
                        </div>
                        <div class="col-12">
                            <label for="categoria_id" class="form-label">Categoria</label>
                            <select id="categoria_id" name="categoria_id" class="form-select" required>
                                <option value="" disabled selected>Selecione a Categoria</option>
                                <?php
                                if (!empty($categorias)) {
                                    // Para categorias
                                    foreach ($categorias as $categoria) {
                                        echo "<option value='{$categoria['id_categoria']}'>{$categoria['nome']}</option>";
                                    }
                                } else {
                                    echo "<option value=''>Nenhuma categoria encontrada</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="fornecedor_id" class="form-label">Fornecedor</label>
                            <select id="fornecedor_id" name="fornecedor_id" class="form-select" required>
                                <option value="" disabled selected>Selecione o Fornecedor</option>
                                <?php
                                if (!empty($fornecedores)) {
                                    // Para fornecedores
                                    foreach ($fornecedores as $fornecedor) {
                                        echo "<option value='{$fornecedor['id_fornecedor']}'>{$fornecedor['nome']}</option>";
                                    }
                                } else {
                                    echo "<option value=''>Nenhum Fornecedor encontrado</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="informacoes-adicionais">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="data_validade" class="form-label">Data de Vencimento</label>
                            <input type="date" id="data_validade" name="data_validade" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto do Produto</label>
                            <input type="file" id="foto_produto" name="foto_produto" accept="image/*" class="form-control">
                        </div>

                        <div class="col-6">
                            <label for="estoque_minimo" class="form-label">Estoque Mínimo</label>
                            <input type="number" id="estoque_minimo" name="estoque_minimo" class="form-control">
                        </div>
                        <div class="col-6">
                            <label for="estoque_maximo" class="form-label">Estoque Máximo</label>
                            <input type="number" id="estoque_maximo" name="estoque_maximo" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Comprovante de Compra</label>
                            <input type="file" id="foto_comprovante" name="foto_comprovante" accept="image/*" class="form-control">
                        </div>
                    </div>
                </div><br>
                <div class="header-actions">
                    <a href="cadastro_produto.php" class="btn btn-danger btn-sm">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-sm">Cadastrar Produto</button>
                </div>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function formatarMoeda(event) {
            const input = event.target;
            let value = input.value.replace(/\D/g, ''); // Remove tudo que não é dígito
            value = (value / 100).toFixed(2); // Divide por 100 para converter centavos em reais
            value = value.replace('.', ','); // Troca o ponto pela vírgula
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1.'); // Adiciona os pontos de milhar
            input.value = 'R$ ' + value; // Adiciona o símbolo de real
        }

        function limparFormato(event) {
            const input = event.target;
            input.value = input.value.replace('R$ ', '').replace(/\./g, '').replace(',', '.'); // Remove o formato
        }
    </script>
</body>

</html>