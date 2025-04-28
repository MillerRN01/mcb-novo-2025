<?php
// Gerar dados aleatórios para demonstração
$meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
$vendas2023 = array_map(function() { return rand(1000, 5000); }, $meses);
$vendas2024 = array_map(function() { return rand(1000, 5000); }, $meses);
?>

<div class="chart-container">
    <canvas data-chart-type="line" data-chart-data='{
        "labels": <?= json_encode($meses) ?>,
        "datasets": [
            {
                "label": "Vendas 2023",
                "data": <?= json_encode($vendas2023) ?>,
                "backgroundColor": "rgba(37, 117, 252, 0.2)",
                "borderColor": "rgba(37, 117, 252, 1)",
                "borderWidth": 2,
                "tension": 0.4
            },
            {
                "label": "Vendas 2024",
                "data": <?= json_encode($vendas2024) ?>,
                "backgroundColor": "rgba(106, 17, 203, 0.2)",
                "borderColor": "rgba(106, 17, 203, 1)",
                "borderWidth": 2,
                "tension": 0.4
            }
        ]
    }'></canvas>
</div>