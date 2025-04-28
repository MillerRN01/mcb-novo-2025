<?php
require_once 'conexao_pdo.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Configurações de período padrão (últimos 30 dias)
$dataInicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$dataFim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

// Consultas para os relatórios
try {
    // 1. Relatório de Vendas por Período
    $sqlVendas = "SELECT DATE(data_venda) as data, SUM(total) as total 
                 FROM vendas 
                 WHERE DATE(data_venda) BETWEEN :dataInicio AND :dataFim
                 GROUP BY DATE(data_venda) 
                 ORDER BY data";
    $stmtVendas = $pdo->prepare($sqlVendas);
    $stmtVendas->execute([':dataInicio' => $dataInicio, ':dataFim' => $dataFim]);
    $vendasPorDia = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);

    // 2. Relatório de Produtos Mais Vendidos
    $sqlProdutos = "SELECT p.produto, SUM(v.quantidade) as quantidade, SUM(v.total) as total
                   FROM vendas v
                   JOIN produtos p ON v.produto_id = p.id_produto
                   WHERE DATE(v.data_venda) BETWEEN :dataInicio AND :dataFim
                   GROUP BY p.produto
                   ORDER BY quantidade DESC
                   LIMIT 10";
    $stmtProdutos = $pdo->prepare($sqlProdutos);
    $stmtProdutos->execute([':dataInicio' => $dataInicio, ':dataFim' => $dataFim]);
    $produtosMaisVendidos = $stmtProdutos->fetchAll(PDO::FETCH_ASSOC);

    // 3. Relatório de Situação do Estoque
    $sqlEstoque = "SELECT p.produto, e.quantidade_estoque, e.estoque_minimo, e.estoque_maximo
                  FROM estoque e
                  JOIN produtos p ON e.produto_id = p.id_produto
                  ORDER BY e.quantidade_estoque ASC";
    $stmtEstoque = $pdo->prepare($sqlEstoque);
    $stmtEstoque->execute();
    $situacaoEstoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);

    // 4. Relatório de Clientes (PF e PJ)
    $sqlClientesPF = "SELECT nome, email, telefone, limite_credito 
                     FROM clientes_pf 
                     WHERE status = 'ativo'
                     ORDER BY nome";
    $stmtClientesPF = $pdo->prepare($sqlClientesPF);
    $stmtClientesPF->execute();
    $clientesPF = $stmtClientesPF->fetchAll(PDO::FETCH_ASSOC);

    $sqlClientesPJ = "SELECT nome_fantasia, email, telefone, limite_credito 
                     FROM clientes_pj 
                     WHERE status = 'ativo'
                     ORDER BY nome_fantasia";
    $stmtClientesPJ = $pdo->prepare($sqlClientesPJ);
    $stmtClientesPJ->execute();
    $clientesPJ = $stmtClientesPJ->fetchAll(PDO::FETCH_ASSOC);

    // 5. Total de Vendas no Período
    $sqlTotalVendas = "SELECT SUM(total) as total 
                      FROM vendas 
                      WHERE DATE(data_venda) BETWEEN :dataInicio AND :dataFim";
    $stmtTotalVendas = $pdo->prepare($sqlTotalVendas);
    $stmtTotalVendas->execute([':dataInicio' => $dataInicio, ':dataFim' => $dataFim]);
    $totalVendas = $stmtTotalVendas->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['mensagem'] = "Erro ao gerar relatórios: " . $e->getMessage();
    $_SESSION['tipo_mensagem'] = "danger";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Consolidados - MeuComérciodeBolso</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-relatorio {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .card-relatorio:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .table-responsive {
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h2 class="mb-4">Relatórios Consolidados</h2>
        
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="data_inicio" class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= $dataInicio ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="data_fim" class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= $dataFim ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resumo Financeiro -->
        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Total de Vendas</div>
                    <div class="card-body">
                        <h3 class="card-title">R$ <?= number_format($totalVendas['total'] ?? 0, 2, ',', '.') ?></h3>
                        <p class="card-text">Período: <?= date('d/m/Y', strtotime($dataInicio)) ?> a <?= date('d/m/Y', strtotime($dataFim)) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-header">Clientes Ativos</div>
                    <div class="card-body">
                        <h3 class="card-title"><?= count($clientesPF) + count($clientesPJ) ?></h3>
                        <p class="card-text"><?= count($clientesPF) ?> PF / <?= count($clientesPJ) ?> PJ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning mb-3">
                    <div class="card-header">Produtos em Estoque</div>
                    <div class="card-body">
                        <h3 class="card-title"><?= count($situacaoEstoque) ?></h3>
                        <p class="card-text"><?= array_reduce($situacaoEstoque, function($carry, $item) {
                            return $carry + ($item['quantidade_estoque'] < $item['estoque_minimo'] ? 1 : 0);
                        }, 0) ?> com estoque baixo</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Vendas -->
        <div class="card card-relatorio mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Vendas por Dia</h5>
            </div>
            <div class="card-body">
                <canvas id="graficoVendas" height="300"></canvas>
            </div>
        </div>

        <!-- Tabela de Produtos Mais Vendidos -->
        <div class="card card-relatorio mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Produtos Mais Vendidos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Total Vendido</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtosMaisVendidos as $produto): ?>
                                <tr>
                                    <td><?= htmlspecialchars($produto['produto']) ?></td>
                                    <td><?= $produto['quantidade'] ?></td>
                                    <td>R$ <?= number_format($produto['total'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Situação do Estoque -->
        <div class="card card-relatorio mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Situação do Estoque</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Estoque Atual</th>
                                <th>Mínimo</th>
                                <th>Máximo</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($situacaoEstoque as $estoque): 
                                $status = '';
                                $class = '';
                                if ($estoque['quantidade_estoque'] <= 0) {
                                    $status = 'Esgotado';
                                    $class = 'danger';
                                } elseif ($estoque['quantidade_estoque'] < $estoque['estoque_minimo']) {
                                    $status = 'Abaixo do Mínimo';
                                    $class = 'warning';
                                } else {
                                    $status = 'Normal';
                                    $class = 'success';
                                }
                            ?>
                                <tr class="table-<?= $class ?>">
                                    <td><?= htmlspecialchars($estoque['produto']) ?></td>
                                    <td><?= $estoque['quantidade_estoque'] ?></td>
                                    <td><?= $estoque['estoque_minimo'] ?></td>
                                    <td><?= $estoque['estoque_maximo'] ?></td>
                                    <td><?= $status ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Clientes Ativos -->
        <div class="row">
            <div class="col-md-6">
                <div class="card card-relatorio mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Clientes (Pessoa Física)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>Contato</th>
                                        <th>Limite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clientesPF as $cliente): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cliente['nome']) ?></td>
                                            <td>
                                                <div><?= $cliente['email'] ?></div>
                                                <small><?= $cliente['telefone'] ?></small>
                                            </td>
                                            <td>R$ <?= number_format($cliente['limite_credito'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-relatorio mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Clientes (Pessoa Jurídica)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome Fantasia</th>
                                        <th>Contato</th>
                                        <th>Limite</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clientesPJ as $cliente): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cliente['nome_fantasia']) ?></td>
                                            <td>
                                                <div><?= $cliente['email'] ?></div>
                                                <small><?= $cliente['telefone'] ?></small>
                                            </td>
                                            <td>R$ <?= number_format($cliente['limite_credito'], 2, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Gráfico de Vendas
        const ctx = document.getElementById('graficoVendas').getContext('2d');
        const vendasData = {
            labels: [<?php foreach ($vendasPorDia as $venda): ?>"<?= date('d/m', strtotime($venda['data'])) ?>",<?php endforeach; ?>],
            datasets: [{
                label: 'Vendas por Dia (R$)',
                data: [<?php foreach ($vendasPorDia as $venda): ?><?= $venda['total'] ?>,<?php endforeach; ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                tension: 0.1
            }]
        };

        const vendasChart = new Chart(ctx, {
            type: 'line',
            data: vendasData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.raw.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>