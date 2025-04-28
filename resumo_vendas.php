<?php
// Simular dados - na prática você buscaria do banco
$totalVendas = rand(10000, 20000) + (rand(0, 99) / 100);
$comparacao = rand(-20, 20); // porcentagem
?>

<div class="summary-widget text-center">
    <div class="value">R$ <?= number_format($totalVendas, 2, ',', '.') ?></div>
    <div class="label">Vendas no período</div>
    <div class="mt-2 small <?= $comparacao >= 0 ? 'text-success' : 'text-danger' ?>">
        <i class="bi bi-arrow-<?= $comparacao >= 0 ? 'up' : 'down' ?>"></i>
        <?= abs($comparacao) ?>% em relação ao período anterior
    </div>
</div>