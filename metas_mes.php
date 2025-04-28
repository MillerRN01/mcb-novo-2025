<?php
// Simular dados - na prática você buscaria do banco
$metaMensal = 30000.00;
$vendasAtuais = 18500.00;
$percentual = ($vendasAtuais / $metaMensal) * 100;
$restante = $metaMensal - $vendasAtuais;
$diasRestantes = 10; // Dias até o final do mês
$projecao = $vendasAtuais + ($vendasAtuais / (date('j') - 1) * $diasRestantes);
?>

<div class="meta-widget">
    <h5 class="mb-3">Meta Mensal de Vendas</h5>
    
    <div class="progress mb-3" style="height: 25px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated 
                    <?= $percentual >= 70 ? 'bg-success' : ($percentual >= 30 ? 'bg-warning' : 'bg-danger') ?>" 
             role="progressbar" style="width: <?= min($percentual, 100) ?>%" 
             aria-valuenow="<?= $percentual ?>" aria-valuemin="0" aria-valuemax="100">
            <?= number_format($percentual, 1) ?>%
        </div>
    </div>
    
    <div class="row text-center">
        <div class="col-md-4 mb-2">
            <div class="card bg-light">
                <div class="card-body p-2">
                    <div class="small text-muted">Meta</div>
                    <div class="fw-bold">R$ <?= number_format($metaMensal, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card bg-light">
                <div class="card-body p-2">
                    <div class="small text-muted">Alcançado</div>
                    <div class="fw-bold">R$ <?= number_format($vendasAtuais, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card bg-light">
                <div class="card-body p-2">
                    <div class="small text-muted">Restante</div>
                    <div class="fw-bold">R$ <?= number_format($restante, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-3 small">
        <i class="bi bi-calendar"></i> <?= $diasRestantes ?> dias restantes<br>
        <i class="bi bi-graph-up"></i> Projeção: R$ <?= number_format($projecao, 2, ',', '.') ?> 
        <span class="badge bg-<?= $projecao >= $metaMensal ? 'success' : 'danger' ?>">
            <?= number_format(($projecao / $metaMensal) * 100, 1) ?>%
        </span>
    </div>
</div>

<style>
.meta-widget {
    padding: 0.5rem;
}
.progress-bar {
    font-size: 0.8rem;
    font-weight: bold;
}
</style>