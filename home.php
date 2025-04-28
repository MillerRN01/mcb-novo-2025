<?php
require_once 'conexao_pdo.php'; 
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Obter widgets disponíveis
$widgets = [];
try {
    $stmt = $pdo->query("SELECT * FROM dashboard_widgets WHERE ativo = 1 ORDER BY ordem_padrao");
    $widgets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar widgets: " . $e->getMessage());
}

// Obter preferências do usuário
$preferencias = [];
if (isset($_SESSION['id_login'])) {
    try {
        $stmt = $pdo->prepare("SELECT p.*, w.titulo, w.icone, w.tipo, w.componente 
                              FROM dashboard_preferencias p
                              JOIN dashboard_widgets w ON p.widget_id = w.id_widget
                              WHERE p.usuario_id = ? AND p.visivel = 1
                              ORDER BY p.posicao");
        $stmt->execute([$_SESSION['id_login']]);
        $preferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erro ao buscar preferências: " . $e->getMessage());
    }
}

// Se não houver preferências, usar ordem padrão
if (empty($preferencias)) {
    $preferencias = $widgets;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Comércio de Bolso - Dashboard</title>
    <link rel="shortcut icon" href="assets/site/favicon.jpg" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 1rem 1rem;
        }
        .widget-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
            height: 100%;
        }
        .widget-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .widget-card .card-header {
            background-color: rgba(255, 255, 255, 0.9);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .widget-icon {
            font-size: 1.25rem;
            color: #6c757d;
        }
        .quick-actions .btn {
            border-radius: 0.75rem;
            padding: 0.75rem;
            margin: 0.25rem;
            flex: 1;
            min-width: 120px;
        }
        .grid-stack-item {
            padding: 0.5rem;
        }
        .widget-settings {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            z-index: 10;
        }
        .widget-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 0.5rem;
            z-index: 100;
            min-width: 200px;
        }
        .widget-settings:hover .widget-menu {
            display: block;
        }
        .summary-widget .value {
            font-size: 2rem;
            font-weight: 700;
            color: #2575fc;
        }
        .summary-widget .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .recent-activity .activity-item {
            border-left: 3px solid #2575fc;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <!-- Cabeçalho do Dashboard -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
                    <p class="lead mb-0">Visão geral do seu negócio</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#widgetsModal">
                        <i class="bi bi-grid"></i> Personalizar
                    </button>
                    <div class="btn-group">
                        <button class="btn btn-outline-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-calendar"></i> Hoje
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Hoje</a></li>
                            <li><a class="dropdown-item" href="#">Ontem</a></li>
                            <li><a class="dropdown-item" href="#">Esta Semana</a></li>
                            <li><a class="dropdown-item" href="#">Este Mês</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Personalizado</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Atalhos Rápidos -->
        <div class="row mb-4 quick-actions">
            <div class="col-12 d-flex flex-wrap">
                <a href="vendas.php" class="btn btn-primary">
                    <i class="bi bi-cart-plus"></i> Nova Venda
                </a>
                <a href="produtos.php" class="btn btn-success">
                    <i class="bi bi-box-seam"></i> Cadastrar Produto
                </a>
                <a href="clientes.php" class="btn btn-info">
                    <i class="bi bi-person-plus"></i> Novo Cliente
                </a>
                <a href="relatorios.php" class="btn btn-warning">
                    <i class="bi bi-graph-up"></i> Relatórios
                </a>
                <a href="configuracoes.php" class="btn btn-secondary">
                    <i class="bi bi-gear"></i> Configurações
                </a>
            </div>
        </div>

        <!-- Widgets -->
        <div class="row" id="dashboard-widgets">
            <?php foreach ($preferencias as $widget): ?>
                <div class="col-lg-4 col-md-6 widget-col" data-widget-id="<?= $widget['id_widget'] ?>">
                    <div class="card widget-card">
                        <div class="card-header">
                            <span><i class="bi bi-<?= $widget['icone'] ?> widget-icon me-2"></i> <?= $widget['titulo'] ?></span>
                            <div class="widget-settings">
                                <button class="btn btn-sm btn-link text-muted">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="widget-menu p-2">
                                    <button class="btn btn-sm btn-outline-secondary w-100 mb-1 widget-remove">
                                        <i class="bi bi-eye-slash"></i> Ocultar
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary w-100 widget-refresh">
                                        <i class="bi bi-arrow-clockwise"></i> Atualizar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php include 'widgets/' . $widget['componente'] . '.php'; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para personalização -->
    <div class="modal fade" id="widgetsModal" tabindex="-1" aria-labelledby="widgetsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="widgetsModalLabel">Personalizar Dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <?php foreach ($widgets as $widget): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input widget-toggle" type="checkbox" 
                                               id="widget-<?= $widget['id_widget'] ?>" 
                                               data-widget-id="<?= $widget['id_widget'] ?>"
                                               <?= in_array($widget['id_widget'], array_column($preferencias, 'widget_id')) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="widget-<?= $widget['id_widget'] ?>">
                                            <i class="bi bi-<?= $widget['icone'] ?>"></i> <?= $widget['titulo'] ?>
                                        </label>
                                    </div>
                                    <p class="small text-muted mb-0"><?= $widget['descricao'] ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="saveWidgets">Salvar Configurações</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        // Inicializar gráficos
        document.querySelectorAll('.chart-container').forEach(container => {
            const ctx = container.getContext('2d');
            const chartType = container.dataset.chartType || 'bar';
            const data = JSON.parse(container.dataset.chartData);
            
            new Chart(ctx, {
                type: chartType,
                data: data,
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });

        // Tornar widgets arrastáveis
        new Sortable(document.getElementById('dashboard-widgets'), {
            animation: 150,
            handle: '.card-header',
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                saveWidgetPositions();
            }
        });

        // Salvar posições dos widgets
        function saveWidgetPositions() {
            const positions = [];
            document.querySelectorAll('.widget-col').forEach((col, index) => {
                positions.push({
                    widget_id: col.dataset.widgetId,
                    position: index + 1
                });
            });
            
            fetch('salvar_widgets.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    usuario_id: <?= $_SESSION['id_login'] ?? 0 ?>,
                    widgets: positions
                })
            });
        }

        // Salvar configurações de widgets
        document.getElementById('saveWidgets').addEventListener('click', function() {
            const enabledWidgets = [];
            document.querySelectorAll('.widget-toggle:checked').forEach(checkbox => {
                enabledWidgets.push(checkbox.dataset.widgetId);
            });
            
            fetch('salvar_widgets.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    usuario_id: <?= $_SESSION['id_login'] ?? 0 ?>,
                    widgets: enabledWidgets.map((id, index) => ({
                        widget_id: id,
                        position: index + 1,
                        visivel: true
                    }))
                })
            }).then(response => {
                if (response.ok) {
                    location.reload();
                }
            });
        });

        // Atualizar widget
        document.querySelectorAll('.widget-refresh').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.widget-card');
                card.classList.add('refreshing');
                setTimeout(() => {
                    // Simular atualização
                    card.classList.remove('refreshing');
                }, 1000);
            });
        });

        // Ocultar widget
        document.querySelectorAll('.widget-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const widgetId = this.closest('.widget-col').dataset.widgetId;
                
                fetch('salvar_widgets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        usuario_id: <?= $_SESSION['id_login'] ?? 0 ?>,
                        widgets: [{
                            widget_id: widgetId,
                            visivel: false
                        }]
                    })
                }).then(response => {
                    if (response.ok) {
                        this.closest('.widget-col').remove();
                    }
                });
            });
        });
    </script>
</body>
</html>