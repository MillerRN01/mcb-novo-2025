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
            
            // Dados da venda
            $cliente_id = $_POST['cliente_id'];
            $tipo_cliente = $_POST['tipo_cliente'];
            $funcionario_id = $_SESSION['id_funcionario']; // Assume que o funcionário está logado
            $produto_id = $_POST['produto_id'];
            $quantidade = $_POST['quantidade'];
            $observacoes = $_POST['observacoes'];
            
            // Busca informações do produto
            $stmt = $pdo->prepare("SELECT p.produto, p.preco, e.quantidade_estoque 
                                  FROM produtos p
                                  LEFT JOIN estoque e ON p.id_produto = e.produto_id
                                  WHERE p.id_produto = ?");
            $stmt->execute([$produto_id]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$produto) {
                throw new Exception("Produto não encontrado!");
            }
            
            if ($produto['quantidade_estoque'] < $quantidade) {
                throw new Exception("Estoque insuficiente! Disponível: " . $produto['quantidade_estoque']);
            }
            
            $total = $produto['preco'] * $quantidade;
            
            // Insere a venda
            $stmt = $pdo->prepare("INSERT INTO vendas 
                                  (cliente_id, tipo_cliente, funcionario_id, produto_id, quantidade, total, observacoes) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $cliente_id, 
                $tipo_cliente, 
                $funcionario_id, 
                $produto_id, 
                $quantidade, 
                $total, 
                $observacoes
            ]);
            
            // Atualiza o estoque
            $novo_estoque = $produto['quantidade_estoque'] - $quantidade;
            $stmt = $pdo->prepare("UPDATE estoque 
                                  SET quantidade_estoque = ?, quantidade = ?, tipo_movimentacao = 'saida'
                                  WHERE produto_id = ?");
            $stmt->execute([$novo_estoque, $quantidade, $produto_id]);
            
            $pdo->commit();
            
            $_SESSION['mensagem'] = "Venda cadastrada com sucesso!";
            $_SESSION['tipo_mensagem'] = "success";
            header("Location: vendas.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['mensagem'] = "Erro ao cadastrar venda: " . $e->getMessage();
            $_SESSION['tipo_mensagem'] = "danger";
        }
    }
}

// Listagem de vendas
if ($acao == 'listar') {
    $filtro_data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
    $filtro_data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');
    
    $sql = "SELECT v.*, 
                   p.produto as produto_nome, 
                   p.preco as produto_preco,
                   CASE 
                       WHEN v.tipo_cliente = 'pf' THEN (SELECT nome FROM clientes_pf WHERE id_cliente = v.cliente_id)
                       WHEN v.tipo_cliente = 'pj' THEN (SELECT razao_social FROM clientes_pj WHERE id_cliente = v.cliente_id)
                   END as cliente_nome,
                   f.nome_completo as funcionario_nome
            FROM vendas v
            JOIN produtos p ON v.produto_id = p.id_produto
            LEFT JOIN funcionario f ON v.funcionario_id = f.id_funcionario
            WHERE DATE(v.data_venda) BETWEEN ? AND ?
            ORDER BY v.data_venda DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$filtro_data_inicio, $filtro_data_fim]);
    $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Busca de clientes para o autocomplete
if ($acao == 'buscar_clientes') {
    $termo = isset($_GET['term']) ? $_GET['term'] : '';
    
    $clientes_pf = $pdo->prepare("SELECT id_cliente, nome, cpf FROM clientes_pf WHERE nome LIKE ? LIMIT 10");
    $clientes_pf->execute(["%$termo%"]);
    $result_pf = $clientes_pf->fetchAll(PDO::FETCH_ASSOC);
    
    $clientes_pj = $pdo->prepare("SELECT id_cliente, razao_social as nome, cnpj FROM clientes_pj WHERE razao_social LIKE ? LIMIT 10");
    $clientes_pj->execute(["%$termo%"]);
    $result_pj = $clientes_pj->fetchAll(PDO::FETCH_ASSOC);
    
    $clientes = array_merge($result_pf, $result_pj);
    
    echo json_encode($clientes);
    exit();
}

// Busca de produtos para o autocomplete
if ($acao == 'buscar_produtos') {
    $termo = isset($_GET['term']) ? $_GET['term'] : '';
    
    $produtos = $pdo->prepare("SELECT p.id_produto, p.produto as nome, p.preco, e.quantidade_estoque as estoque 
                              FROM produtos p
                              LEFT JOIN estoque e ON p.id_produto = e.produto_id
                              WHERE p.produto LIKE ? AND p.status = 'ativo' LIMIT 10");
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
    <title>Meu Comércio de Bolso - Vendas</title>
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
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-cart"></i> Gerenciamento de Vendas</h2>
        
        <?php include 'mensagem.php'; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'listar' ? 'active' : '' ?>" href="vendas.php">Listagem</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $acao == 'cadastrar' ? 'active' : '' ?>" href="vendas.php?acao=cadastrar">Nova Venda</a>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <?php if ($acao == 'listar'): ?>
                    <!-- Filtros -->
                    <form method="get" class="mb-4">
                        <input type="hidden" name="acao" value="listar">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="data_inicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $filtro_data_inicio ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="data_fim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $filtro_data_fim ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Tabela de vendas -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="tabela-vendas">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data</th>
                                    <th>Cliente</th>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Preço Unit.</th>
                                    <th>Total</th>
                                    <th>Vendedor</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vendas as $venda): ?>
                                <tr>
                                    <td><?= $venda['id_venda'] ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                                    <td><?= $venda['cliente_nome'] ?></td>
                                    <td><?= $venda['produto_nome'] ?></td>
                                    <td><?= $venda['quantidade'] ?></td>
                                    <td>R$ <?= number_format($venda['produto_preco'], 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($venda['total'], 2, ',', '.') ?></td>
                                    <td><?= $venda['funcionario_nome'] ?? '--' ?></td>
                                    <td>
                                        <a href="vendas.php?acao=visualizar&id=<?= $venda['id_venda'] ?>" class="btn btn-sm btn-info" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="vendas.php?acao=editar&id=<?= $venda['id_venda'] ?>" class="btn btn-sm btn-warning" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="vendas.php?acao=excluir&id=<?= $venda['id_venda'] ?>" class="btn btn-sm btn-danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta venda?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                <?php elseif ($acao == 'cadastrar'): ?>
                    <!-- Formulário de cadastro de venda -->
                    <form method="post" id="form-venda">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cliente" class="form-label">Cliente</label>
                                <input type="text" class="form-control" id="cliente" name="cliente" placeholder="Digite o nome do cliente" required>
                                <input type="hidden" id="cliente_id" name="cliente_id">
                                <input type="hidden" id="tipo_cliente" name="tipo_cliente">
                                <div id="sugestoes-clientes" class="autocomplete-suggestions"></div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="produto" class="form-label">Produto</label>
                                <input type="text" class="form-control" id="produto" name="produto" placeholder="Digite o nome do produto" required>
                                <input type="hidden" id="produto_id" name="produto_id">
                                <div id="sugestoes-produtos" class="autocomplete-suggestions"></div>
                            </div>
                            <div class="col-md-3">
                                <label for="quantidade" class="form-label">Quantidade</label>
                                <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <label for="preco_unitario" class="form-label">Preço Unitário</label>
                                <input type="text" class="form-control" id="preco_unitario" readonly>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                            </div>
                            <div class="col-md-3 offset-md-3">
                                <label for="total" class="form-label">Total</label>
                                <input type="text" class="form-control fs-4 fw-bold text-success" id="total" readonly>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="vendas.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">Registrar Venda</button>
                        </div>
                    </form>
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
            // DataTable para listagem
            $('#tabela-vendas').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                }
            });
            
            // Autocomplete para clientes
            $('#cliente').on('input', function() {
                const termo = $(this).val();
                if (termo.length < 2) {
                    $('#sugestoes-clientes').hide().empty();
                    return;
                }
                
                $.get('vendas.php?acao=buscar_clientes&term=' + termo, function(data) {
                    const sugestoes = $('#sugestoes-clientes');
                    sugestoes.empty();
                    
                    if (data.length > 0) {
                        data.forEach(function(cliente) {
                            const tipo = cliente.cpf ? 'PF' : 'PJ';
                            const doc = cliente.cpf || cliente.cnpj;
                            
                            sugestoes.append(
                                `<div class="autocomplete-suggestion" 
                                    data-id="${cliente.id_cliente}" 
                                    data-tipo="${tipo == 'PF' ? 'pf' : 'pj'}"
                                    data-nome="${cliente.nome}">
                                    ${cliente.nome} (${tipo} - ${doc})
                                </div>`
                            );
                        });
                        sugestoes.show();
                    } else {
                        sugestoes.hide();
                    }
                });
            });
            
            // Seleção de cliente
            $(document).on('click', '.autocomplete-suggestion', function() {
                $('#cliente').val($(this).data('nome'));
                $('#cliente_id').val($(this).data('id'));
                $('#tipo_cliente').val($(this).data('tipo'));
                $('#sugestoes-clientes').hide().empty();
            });
            
            // Autocomplete para produtos
            $('#produto').on('input', function() {
                const termo = $(this).val();
                if (termo.length < 2) {
                    $('#sugestoes-produtos').hide().empty();
                    return;
                }
                
                $.get('vendas.php?acao=buscar_produtos&term=' + termo, function(data) {
                    const sugestoes = $('#sugestoes-produtos');
                    sugestoes.empty();
                    
                    if (data.length > 0) {
                        data.forEach(function(produto) {
                            sugestoes.append(
                                `<div class="autocomplete-suggestion" 
                                    data-id="${produto.id_produto}" 
                                    data-preco="${produto.preco}"
                                    data-estoque="${produto.estoque}"
                                    data-nome="${produto.nome}">
                                    ${produto.nome} (R$ ${produto.preco.toFixed(2)} | Estoque: ${produto.estoque})
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
                $('#preco_unitario').val('R$ ' + parseFloat($(this).data('preco')).toFixed(2));
                $('#sugestoes-produtos').hide().empty();
                calcularTotal();
            });
            
            // Cálculo do total
            $('#quantidade').on('input', calcularTotal);
            
            function calcularTotal() {
                const preco = parseFloat($('#preco_unitario').val().replace('R$ ', '').replace(',', '.')) || 0;
                const quantidade = parseInt($('#quantidade').val()) || 0;
                const total = preco * quantidade;
                
                if (!isNaN(total)) {
                    $('#total').val('R$ ' + total.toFixed(2).replace('.', ','));
                }
            }
            
            // Fechar sugestões ao clicar fora
            $(document).click(function(e) {
                if (!$(e.target).closest('.autocomplete-suggestions').length && 
                    !$(e.target).is('#cliente, #produto')) {
                    $('.autocomplete-suggestions').hide();
                }
            });
        });
    </script>
</body>
</html>