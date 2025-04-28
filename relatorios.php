<?php
require_once 'conexao.php';
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Consultas para os relatórios
$clientes_pf_count = $conn->query("SELECT COUNT(*) as total FROM clientes_pf")->fetch_assoc()['total'];
$clientes_pj_count = $conn->query("SELECT COUNT(*) as total FROM clientes_pj")->fetch_assoc()['total'];
$produtos_count = $conn->query("SELECT COUNT(*) as total FROM produtos")->fetch_assoc()['total'];
$fornecedores_count = $conn->query("SELECT COUNT(*) as total FROM fornecedor")->fetch_assoc()['total'];
$funcionarios_count = $conn->query("SELECT COUNT(*) as total FROM funcionario")->fetch_assoc()['total'];

// Status de clientes
$clientes_pf_status = $conn->query("SELECT status, COUNT(*) as count FROM clientes_pf GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$clientes_pj_status = $conn->query("SELECT status, COUNT(*) as count FROM clientes_pj GROUP BY status")->fetch_all(MYSQLI_ASSOC);

// Produtos por categoria
$produtos_categoria = $conn->query("
    SELECT c.nome as categoria, COUNT(p.id_produto) as quantidade 
    FROM produtos p
    LEFT JOIN categorias_produto c ON p.categoria_id = c.id_categoria
    GROUP BY c.nome
")->fetch_all(MYSQLI_ASSOC);

// Estoque crítico (produtos com quantidade abaixo do estoque mínimo)
// Estoque crítico (produtos com quantidade abaixo do estoque mínimo)
$estoque_critico = $conn->query("
    SELECT p.id_produto, p.produto, e.quantidade_estoque, e.estoque_minimo, 
           (e.quantidade_estoque - e.estoque_minimo) as diferenca
    FROM produtos p
    JOIN estoque e ON p.id_produto = e.produto_id
    WHERE e.quantidade_estoque <= e.estoque_minimo
    ORDER BY diferenca ASC
");

// Verificação adicional para ver se a query retornou resultados
if ($estoque_critico && $estoque_critico->num_rows > 0) {
    $itens_criticos = $estoque_critico->fetch_all(MYSQLI_ASSOC);
} else {
    // Consulta de diagnóstico para ver quais produtos deveriam aparecer
    $diagnostico = $conn->query("
        SELECT p.id_produto, p.produto, e.quantidade_estoque, e.estoque_minimo
        FROM produtos p
        JOIN estoque e ON p.id_produto = e.produto_id
        ORDER BY (e.quantidade_estoque - e.estoque_minimo) ASC
    ")->fetch_all(MYSQLI_ASSOC);
}

// Top 10 produtos mais vendidos
$produtos_mais_vendidos = $conn->query("
    SELECT p.produto, SUM(v.quantidade) as total_vendido, SUM(v.total) as valor_total
    FROM vendas v
    JOIN produtos p ON v.produto_id = p.id_produto
    GROUP BY p.produto
    ORDER BY total_vendido DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Lista completa de clientes PF
$clientes_pf_lista = $conn->query("
    SELECT c.nome, c.cpf, c.email, c.telefone, c.status, 
           e.cidade, e.estado, c.criacao
    FROM clientes_pf c
    JOIN enderecos e ON c.endereco_id = e.id_endereco
    ORDER BY c.nome
")->fetch_all(MYSQLI_ASSOC);

// Lista completa de clientes PJ
$clientes_pj_lista = $conn->query("
    SELECT c.razao_social, c.nome_fantasia, c.cnpj, c.email, c.telefone, c.status, 
           e.cidade, e.estado, c.criacao
    FROM clientes_pj c
    JOIN enderecos e ON c.endereco_id = e.id_endereco
    ORDER BY c.razao_social
")->fetch_all(MYSQLI_ASSOC);

// Lista de funcionários
$funcionarios_lista = $conn->query("
    SELECT f.nome_completo, f.cargo, f.cpf, f.status, 
           e.cidade, e.estado, f.data_admissao
    FROM funcionario f
    LEFT JOIN enderecos e ON f.endereco_id = e.id_endereco
    ORDER BY f.nome_completo
")->fetch_all(MYSQLI_ASSOC);

// Lista de fornecedores
$fornecedores_lista = $conn->query("
    SELECT f.nome, f.razao_social, f.cpf_cnpj, f.email, f.telefone, f.status, 
           e.cidade, e.estado, f.criacao
    FROM fornecedor f
    LEFT JOIN enderecos e ON f.endereco_id = e.id_endereco
    ORDER BY f.nome
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Gerenciais</title>
    <link rel="stylesheet" href="relatorios.css">
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php include_once 'navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h2 class="mb-4">Relatórios Gerenciais</h2>

        <!-- Cards de Resumo -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card-counter primary">
                    <i class="fa fa-users"></i>
                    <span class="count-numbers"><?= $clientes_pf_count + $clientes_pj_count ?></span>
                    <span class="count-name">Clientes</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-counter success">
                    <i class="fa fa-boxes"></i>
                    <span class="count-numbers"><?= $produtos_count ?></span>
                    <span class="count-name">Produtos</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-counter info">
                    <i class="fa fa-truck"></i>
                    <span class="count-numbers"><?= $fornecedores_count ?></span>
                    <span class="count-name">Fornecedores</span>
                </div>
            </div>
        </div>

        <!-- Abas para diferentes relatórios -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">Dashboard</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="clientes-tab" data-bs-toggle="tab" data-bs-target="#clientes" type="button" role="tab">Clientes</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="produtos-tab" data-bs-toggle="tab" data-bs-target="#produtos" type="button" role="tab">Produtos</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="funcionarios-tab" data-bs-toggle="tab" data-bs-target="#funcionarios" type="button" role="tab">Funcionários</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="fornecedores-tab" data-bs-toggle="tab" data-bs-target="#fornecedores" type="button" role="tab">Fornecedores</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Dashboard -->
            <div class="tab-pane fade show active" id="dashboard" role="tabpanel">
                <!-- Gráficos -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5>Status de Clientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="clientesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5>Produtos por Categoria</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="categoriasChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabelas de Relatórios -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5>Estoque Crítico (abaixo ou no mínimo)</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Produto</th>
                                                <th>Estoque Atual</th>
                                                <th>Estoque Mínimo</th>
                                                <th>Diferença</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($estoque_critico as $item):
                                                $diferenca = $item['quantidade_estoque'] - $item['estoque_minimo'];
                                                $status_class = ($diferenca < 0) ? 'text-danger' : 'text-warning';
                                                $status_text = ($diferenca < 0) ? 'Abaixo do mínimo' : 'No mínimo';
                                            ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($item['produto']) ?></td>
                                                    <td><?= $item['quantidade_estoque'] ?></td>
                                                    <td><?= $item['estoque_minimo'] ?></td>
                                                    <td class="<?= $status_class ?>"><?= $diferenca ?></td>
                                                    <td class="<?= $status_class ?>"><?= $status_text ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($estoque_critico)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-success">Nenhum produto com estoque crítico</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5>Top 10 Produtos Mais Vendidos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Quantidade Vendida</th>
                                            <th>Valor Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($produtos_mais_vendidos as $produto): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($produto['produto']) ?></td>
                                                <td><?= $produto['total_vendido'] ?></td>
                                                <td>R$ <?= number_format($produto['valor_total'], 2, ',', '.') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($produtos_mais_vendidos)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center">Nenhuma venda registrada</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clientes -->
        <div class="tab-pane fade" id="clientes" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Clientes Pessoa Física</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="tabelaClientesPF">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>CPF</th>
                                            <th>Localização</th>
                                            <th>Status</th>
                                            <th>Cadastro</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientes_pf_lista as $cliente): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($cliente['nome']) ?></td>
                                                <td><?= $cliente['cpf'] ?></td>
                                                <td><?= $cliente['cidade'] ?>/<?= $cliente['estado'] ?></td>
                                                <td>
                                                    <span class="badge badge-status badge-<?= $cliente['status'] ?>">
                                                        <?= ucfirst($cliente['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($cliente['criacao'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5>Clientes Pessoa Jurídica</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="tabelaClientesPJ">
                                    <thead>
                                        <tr>
                                            <th>Razão Social</th>
                                            <th>CNPJ</th>
                                            <th>Localização</th>
                                            <th>Status</th>
                                            <th>Cadastro</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($clientes_pj_lista as $cliente): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($cliente['razao_social']) ?></td>
                                                <td><?= $cliente['cnpj'] ?></td>
                                                <td><?= $cliente['cidade'] ?>/<?= $cliente['estado'] ?></td>
                                                <td>
                                                    <span class="badge badge-status badge-<?= $cliente['status'] ?>">
                                                        <?= ucfirst($cliente['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($cliente['criacao'])) ?></td>
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

        <!-- Produtos -->
        <div class="tab-pane fade" id="produtos" role="tabpanel">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5>Produtos por Categoria</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="categoriasChartFull"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5>Lista Completa de Produtos</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="tabelaProdutos">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th>Categoria</th>
                                            <th>Preço</th>
                                            <th>Estoque</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $produtos_lista = $conn->query("
                                                SELECT p.produto, c.nome as categoria, p.preco, 
                                                       e.quantidade_estoque, p.status
                                                FROM produtos p
                                                LEFT JOIN categorias_produto c ON p.categoria_id = c.id_categoria
                                                LEFT JOIN estoque e ON p.estoque_id = e.id_estoque
                                                ORDER BY p.produto
                                            ")->fetch_all(MYSQLI_ASSOC);

                                        foreach ($produtos_lista as $produto): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($produto['produto']) ?></td>
                                                <td><?= $produto['categoria'] ?: 'Sem categoria' ?></td>
                                                <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                                                <td><?= $produto['quantidade_estoque'] ?></td>
                                                <td>
                                                    <span class="badge badge-status badge-<?= $produto['status'] ?>">
                                                        <?= ucfirst($produto['status']) ?>
                                                    </span>
                                                </td>
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

        <!-- Funcionários -->
        <div class="tab-pane fade" id="funcionarios" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5>Funcionários por Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="funcionariosChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5>Lista de Funcionários</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="tabelaFuncionarios">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Cargo</th>
                                            <th>CPF</th>
                                            <th>Localização</th>
                                            <th>Status</th>
                                            <th>Admissão</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($funcionarios_lista as $funcionario): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($funcionario['nome_completo']) ?></td>
                                                <td><?= $funcionario['cargo'] ?></td>
                                                <td><?= $funcionario['cpf'] ?></td>
                                                <td><?= $funcionario['cidade'] ?>/<?= $funcionario['estado'] ?></td>
                                                <td>
                                                    <span class="badge badge-status badge-<?= $funcionario['status'] ?>">
                                                        <?= ucfirst($funcionario['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($funcionario['data_admissao'])) ?></td>
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

        <!-- Fornecedores -->
        <div class="tab-pane fade" id="fornecedores" role="tabpanel">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5>Lista de Fornecedores</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="tabelaFornecedores">
                                    <thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th>Razão Social</th>
                                            <th>CPF/CNPJ</th>
                                            <th>Localização</th>
                                            <th>Status</th>
                                            <th>Cadastro</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fornecedores_lista as $fornecedor): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($fornecedor['nome']) ?></td>
                                                <td><?= $fornecedor['razao_social'] ?></td>
                                                <td><?= $fornecedor['cpf_cnpj'] ?></td>
                                                <td><?= $fornecedor['cidade'] ?>/<?= $fornecedor['estado'] ?></td>
                                                <td>
                                                    <span class="badge badge-status badge-<?= $fornecedor['status'] ?>">
                                                        <?= ucfirst($fornecedor['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($fornecedor['criacao'])) ?></td>
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
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Inicializa as tabelas com DataTables
        $(document).ready(function() {
            $('#tabelaClientesPF, #tabelaClientesPJ, #tabelaProdutos, #tabelaFuncionarios, #tabelaFornecedores').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                },
                responsive: true
            });
        });

        // Gráfico de Status de Clientes
        const clientesCtx = document.getElementById('clientesChart').getContext('2d');
        const clientesChart = new Chart(clientesCtx, {
            type: 'pie',
            data: {
                labels: ['Ativos PF', 'Inativos PF', 'Ativos PJ', 'Inativos PJ'],
                datasets: [{
                    data: [
                        <?= array_reduce($clientes_pf_status, function ($carry, $item) {
                            return $item['status'] == 'ativo' ? $item['count'] : $carry;
                        }, 0) ?>,
                        <?= array_reduce($clientes_pf_status, function ($carry, $item) {
                            return $item['status'] == 'inativo' ? $item['count'] : $carry;
                        }, 0) ?>,
                        <?= array_reduce($clientes_pj_status, function ($carry, $item) {
                            return $item['status'] == 'ativo' ? $item['count'] : $carry;
                        }, 0) ?>,
                        <?= array_reduce($clientes_pj_status, function ($carry, $item) {
                            return $item['status'] == 'inativo' ? $item['count'] : $carry;
                        }, 0) ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Gráfico de Produtos por Categoria (Dashboard)
        const categoriasCtx = document.getElementById('categoriasChart').getContext('2d');
        const categoriasChart = new Chart(categoriasCtx, {
            type: 'bar',
            data: {
                labels: [<?php
                            $labels = [];
                            foreach ($produtos_categoria as $item) {
                                $labels[] = "'" . htmlspecialchars($item['categoria'] ?: 'Sem Categoria') . "'";
                            }
                            echo implode(',', $labels);
                            ?>],
                datasets: [{
                    label: 'Quantidade de Produtos',
                    data: [<?= implode(',', array_column($produtos_categoria, 'quantidade')) ?>],
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Produtos por Categoria (Full)
        const categoriasFullCtx = document.getElementById('categoriasChartFull').getContext('2d');
        const categoriasFullChart = new Chart(categoriasFullCtx, {
            type: 'doughnut',
            data: {
                labels: [<?= implode(',', $labels) ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($produtos_categoria, 'quantidade')) ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    }
                }
            }
        });

        // Gráfico de Funcionários por Status
        const funcionariosCtx = document.getElementById('funcionariosChart').getContext('2d');
        const funcionariosChart = new Chart(funcionariosCtx, {
            type: 'doughnut',
            data: {
                labels: ['Ativos', 'Férias', 'Licença'],
                datasets: [{
                    data: [
                        <?= array_reduce($funcionarios_lista, function ($carry, $item) {
                            return $item['status'] == 'ativo' ? $carry + 1 : $carry;
                        }, 0) ?>,
                        <?= array_reduce($funcionarios_lista, function ($carry, $item) {
                            return $item['status'] == 'ferias' ? $carry + 1 : $carry;
                        }, 0) ?>,
                        <?= array_reduce($funcionarios_lista, function ($carry, $item) {
                            return $item['status'] == 'licenca' ? $carry + 1 : $carry;
                        }, 0) ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(153, 102, 255, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>

</html>