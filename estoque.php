<?php
require_once 'conexao_pdo.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Ações do CRUD
$acao = isset($_GET['acao']) ? $_GET['acao'] : 'listar';

// Processamento do formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($acao == 'cadastrar') {
        try {
            $pdo->beginTransaction();
            
            $produto_id = $_POST['produto_id'];
            $tipo_movimentacao = $_POST['tipo_movimentacao'];
            $quantidade = $_POST['quantidade'];
            $observacoes = $_POST['observacoes'];
            
            // Busca estoque atual
            $stmt = $pdo->prepare("SELECT quantidade_estoque FROM estoque WHERE produto_id = ?");
            $stmt->execute([$produto_id]);
            $estoque_atual = $stmt->fetchColumn();
            
            // Calcula novo estoque
            $nova_quantidade = ($tipo_movimentacao == 'entrada') 
                ? $estoque_atual + $quantidade 
                : $estoque_atual - $quantidade;
            
            // Insere movimentação
            $stmt = $pdo->prepare("INSERT INTO estoque 
                                  (produto_id, quantidade, tipo_movimentacao, quantidade_estoque, observacoes) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $produto_id,
                $quantidade,
                $tipo_movimentacao,
                $nova_quantidade,
                $observacoes
            ]);
            
            // Atualiza status do produto se necessário
            if ($nova_quantidade <= 0) {
                $stmt = $pdo->prepare("UPDATE produtos SET status = 'esgotado' WHERE id_produto = ?");
                $stmt->execute([$produto_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE produtos SET status = 'ativo' WHERE id_produto = ?");
                $stmt->execute([$produto_id]);
            }
            
            $pdo->commit();
            
            $_SESSION['mensagem'] = "Movimentação registrada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: estoque.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao registrar movimentação: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
}

// Listagem de estoque
if ($acao == 'listar') {
    $stmt = $pdo->prepare("SELECT e.*, p.produto, p.preco, p.status as produto_status
                          FROM estoque e
                          JOIN produtos p ON e.produto_id = p.id_produto
                          ORDER BY e.data_movimentacao DESC");
    $stmt->execute();
    $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Resumo do estoque atual
    $stmt = $pdo->prepare("SELECT p.id_produto, p.produto, p.preco, 
                          COALESCE(e.quantidade_estoque, 0) as estoque_atual,
                          p.status
                          FROM produtos p
                          LEFT JOIN (
                              SELECT produto_id, quantidade_estoque
                              FROM estoque
                              WHERE id_estoque IN (
                                  SELECT MAX(id_estoque)
                                  FROM estoque
                                  GROUP BY produto_id
                              )
                          ) e ON p.id_produto = e.produto_id");
    $stmt->execute();
    $resumo_estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Busca de produtos para o autocomplete
if ($acao == 'buscar_produtos') {
    $termo = isset($_GET['term']) ? $_GET['term'] : '';
    
    $produtos = $pdo->prepare("SELECT id_produto, produto as nome 
                              FROM produtos 
                              WHERE produto LIKE ? AND status != 'vencido'
                              LIMIT 10");
    $produtos->execute(["%$termo%"]);
    $result = $produtos->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($result);
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Comércio de Bolso - Estoque</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        .autocomplete-suggestions { 
            border: 1px solid #ddd; 
            background: #FFF; 
            overflow: auto; 
            cursor: pointer;
        }
        .autocomplete-suggestion { 
            padding: 5px 10px; 
            white-space: nowrap; 
            overflow: hidden; 
        }
        .autocomplete-selected { 
            background: #f0f0f0; 
        }
        .badge-estoque {
            font-size: 0.9em;
        }
        .estoque-baixo {
            background-color: #ffc107;
            color: #000;
        }
        .estoque-critico {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-box-seam"></i> Gerenciamento de Estoque</h2>
        
        <?php include 'mensagem.php'; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'listar' ? 'active' : '' ?>" href="estoque.php">Estoque Atual</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'cadastrar' ? 'active' : '' ?>" href="estoque.php?acao=cadastrar">Movimentação</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'historico' ? 'active' : '' ?>" href="estoque.php?acao=historico">Histórico</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <?php if ($acao == 'listar'): ?>
                    <!-- Resumo do Estoque Atual -->
                    <h4 class="mb-3">Resumo do Estoque</h4>
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabela-estoque">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th>Preço</th>
                                    <th>Estoque Atual</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resumo_estoque as $item): ?>
                                <tr>
                                    <td><?= $item['produto'] ?></td>
                                    <td>R$ <?= number_format($item['preco'], 2, ',', '.') ?></td>
                                    <td>
                                        <span class="badge badge-estoque 
                                            <?= $item['estoque_atual'] <= 5 ? 'estoque-critico' : 
                                              ($item['estoque_atual'] <= 10 ? 'estoque-baixo' : 'bg-secondary') ?>">
                                            <?= $item['estoque_atual'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $badge_class = [
                                            'ativo' => 'bg-success',
                                            'esgotado' => 'bg-danger',
                                            'vencido' => 'bg-warning text-dark'
                                        ];
                                        ?>
                                        <span class="badge <?= $badge_class[$item['status']] ?? 'bg-secondary' ?>">
                                            <?= ucfirst($item['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="estoque.php?acao=cadastrar&produto_id=<?= $item['id_produto'] ?>" 
                                           class="btn btn-sm btn-primary" title="Movimentar">
                                            <i class="bi bi-arrow-left-right"></i>
                                        </a>
                                        <a href="estoque.php?acao=historico&produto_id=<?= $item['id_produto'] ?>" 
                                           class="btn btn-sm btn-info" title="Histórico">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($acao == 'cadastrar'): ?>
                    <!-- Formulário de movimentação de estoque -->
                    <form method="post" id="form-movimentacao">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="produto" class="form-label">Produto</label>
                                <input type="text" class="form-control" id="produto" name="produto" 
                                       value="<?= isset($_GET['produto_id']) ? 'Carregando...' : '' ?>" 
                                       placeholder="Digite o nome do produto" required>
                                <input type="hidden" id="produto_id" name="produto_id" value="<?= $_GET['produto_id'] ?? '' ?>">
                                <div id="sugestoes-produtos" class="autocomplete-suggestions"></div>
                            </div>
                            <div class="col-md-3">
                                <label for="tipo_movimentacao" class="form-label">Tipo</label>
                                <select class="form-select" id="tipo_movimentacao" name="tipo_movimentacao" required>
                                    <option value="entrada">Entrada</option>
                                    <option value="saida">Saída</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="quantidade" class="form-label">Quantidade</label>
                                <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" value="1" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-9">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2" 
                                          placeholder="Motivo da movimentação, lote, etc."></textarea>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="d-grid w-100">
                                    <button type="submit" class="btn btn-primary">Registrar Movimentação</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                <?php elseif ($acao == 'historico'): ?>
                    <!-- Histórico de movimentações -->
                    <h4 class="mb-3">Histórico de Movimentações</h4>
                    <?php if (isset($_GET['produto_id'])): ?>
                        <?php
                        $stmt = $pdo->prepare("SELECT e.*, p.produto 
                                              FROM estoque e
                                              JOIN produtos p ON e.produto_id = p.id_produto
                                              WHERE e.produto_id = ?
                                              ORDER BY e.data_movimentacao DESC");
                        $stmt->execute([$_GET['produto_id']]);
                        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <div class="mb-3">
                            <a href="estoque.php?acao=listar" class="btn btn-sm btn-secondary">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                            <h5 class="mt-2">Produto: <?= $historico[0]['produto'] ?? 'Não encontrado' ?></h5>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-striped" id="tabela-historico">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Quantidade</th>
                                        <th>Estoque Resultante</th>
                                        <th>Observações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historico as $mov): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></td>
                                        <td>
                                            <span class="badge <?= $mov['tipo_movimentacao'] == 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($mov['tipo_movimentacao']) ?>
                                            </span>
                                        </td>
                                        <td><?= $mov['quantidade'] ?></td>
                                        <td><?= $mov['quantidade_estoque'] ?></td>
                                        <td><?= $mov['observacoes'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped" id="tabela-historico-geral">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Produto</th>
                                        <th>Tipo</th>
                                        <th>Quantidade</th>
                                        <th>Estoque Resultante</th>
                                        <th>Observações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimentacoes as $mov): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($mov['data_movimentacao'])) ?></td>
                                        <td><?= $mov['produto'] ?></td>
                                        <td>
                                            <span class="badge <?= $mov['tipo_movimentacao'] == 'entrada' ? 'bg-success' : 'bg-danger' ?>">
                                                <?= ucfirst($mov['tipo_movimentacao']) ?>
                                            </span>
                                        </td>
                                        <td><?= $mov['quantidade'] ?></td>
                                        <td><?= $mov['quantidade_estoque'] ?></td>
                                        <td><?= $mov['observacoes'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // DataTables
            $('#tabela-estoque, #tabela-historico, #tabela-historico-geral').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });
            
            // Autocomplete para produtos
            $('#produto').on('input', function() {
                const termo = $(this).val();
                if (termo.length < 2) {
                    $('#sugestoes-produtos').hide().empty();
                    return;
                }
                
                $.get('estoque.php?acao=buscar_produtos&term=' + termo, function(data) {
                    const sugestoes = $('#sugestoes-produtos');
                    sugestoes.empty();
                    
                    if (data.length > 0) {
                        data.forEach(function(produto) {
                            sugestoes.append(
                                `<div class="autocomplete-suggestion" 
                                    data-id="${produto.id_produto}"
                                    data-nome="${produto.nome}">
                                    ${produto.nome}
                                </div>`
                            );
                        });
                        sugestoes.show();
                    } else {
                        sugestoes.hide();
                    }
                });
            });
            
            // Seleção de produto
            $(document).on('click', '.autocomplete-suggestion', function() {
                $('#produto').val($(this).data('nome'));
                $('#produto_id').val($(this).data('id'));
                $('#sugestoes-produtos').hide().empty();
            });
            
            // Fechar sugestões ao clicar fora
            $(document).click(function(e) {
                if (!$(e.target).closest('.autocomplete-suggestions').length && 
                    !$(e.target).is('#produto')) {
                    $('.autocomplete-suggestions').hide();
                }
            });
            
            // Carregar nome do produto se veio com ID na URL
            <?php if (isset($_GET['produto_id'])): ?>
                $.get('estoque.php?acao=buscar_produtos&term=', function(data) {
                    const produtoId = <?= $_GET['produto_id'] ?>;
                    const produto = data.find(p => p.id_produto == produtoId);
                    if (produto) {
                        $('#produto').val(produto.nome);
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>