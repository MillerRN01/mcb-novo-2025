<?php
require_once 'conexao_pdo.php'; 
require_once 'verifica_session_conn.php';
include_once 'mensagem_por_session_conn.php';

// Busca FAQs do banco de dados
$faqs = [];
try {
    $stmt = $pdo->query("SELECT * FROM ajuda_faq ORDER BY categoria, ordem");
    $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar FAQs: " . $e->getMessage());
}

// Busca tutoriais
$tutoriais = [];
try {
    $stmt = $pdo->query("SELECT * FROM ajuda_tutoriais ORDER BY categoria, titulo");
    $tutoriais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar tutoriais: " . $e->getMessage());
}

// Busca contatos
$contatos = [];
try {
    $stmt = $pdo->query("SELECT * FROM ajuda_contatos ORDER BY ordem");
    $contatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar contatos: " . $e->getMessage());
}

// Busca redes sociais
$redes_sociais = [];
try {
    $stmt = $pdo->query("SELECT * FROM ajuda_redes_sociais ORDER BY ordem");
    $redes_sociais = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar redes sociais: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Comércio de Bolso - Ajuda e Suporte</title>
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .help-section {
            margin-bottom: 3rem;
        }
        .faq-category {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .accordion-button:not(.collapsed) {
            background-color: rgba(13, 110, 253, 0.1);
        }
        .video-card {
            transition: transform 0.3s;
            margin-bottom: 1.5rem;
        }
        .video-card:hover {
            transform: translateY(-5px);
        }
        .contact-card {
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            margin-bottom: 1rem;
            height: 100%;
        }
        .social-icon {
            font-size: 2rem;
            margin: 0 0.5rem;
            color: #6c757d;
            transition: color 0.3s;
        }
        .social-icon:hover {
            color: #0d6efd;
        }
        .search-box {
            position: relative;
            margin-bottom: 2rem;
        }
        .search-box .form-control {
            padding-left: 2.5rem;
        }
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1><i class="bi bi-question-circle"></i> Central de Ajuda</h1>
                <p class="lead">Encontre respostas para suas dúvidas ou entre em contato com nosso suporte</p>
                
                <div class="search-box col-md-8 mx-auto">
                    <i class="bi bi-search"></i>
                    <input type="text" class="form-control form-control-lg" placeholder="Pesquise por palavras-chave...">
                </div>
            </div>
        </div>

        <!-- Seção FAQ -->
        <div class="help-section" id="faq">
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4"><i class="bi bi-patch-question"></i> Perguntas Frequentes</h2>
                    
                    <?php 
                    // Agrupa FAQs por categoria
                    $faqs_por_categoria = [];
                    foreach ($faqs as $faq) {
                        $faqs_por_categoria[$faq['categoria']][] = $faq;
                    }
                    
                    foreach ($faqs_por_categoria as $categoria => $faqs_categoria): 
                    ?>
                        <div class="faq-category">
                            <h4 class="mb-3"><?= htmlspecialchars($categoria) ?></h4>
                            
                            <div class="accordion" id="accordion<?= md5($categoria) ?>">
                                <?php foreach ($faqs_categoria as $index => $faq): ?>
                                <div class="accordion-item">
                                    <h3 class="accordion-header" id="heading<?= $faq['id_faq'] ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse<?= $faq['id_faq'] ?>" aria-expanded="false" 
                                                aria-controls="collapse<?= $faq['id_faq'] ?>">
                                            <?= htmlspecialchars($faq['pergunta']) ?>
                                        </button>
                                    </h3>
                                    <div id="collapse<?= $faq['id_faq'] ?>" class="accordion-collapse collapse" 
                                         aria-labelledby="heading<?= $faq['id_faq'] ?>" 
                                         data-bs-parent="#accordion<?= md5($categoria) ?>">
                                        <div class="accordion-body">
                                            <?= nl2br(htmlspecialchars($faq['resposta'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Seção Tutoriais -->
        <div class="help-section" id="tutoriais">
            <div class="row">
                <div class="col-12">
                    <h2 class="mb-4"><i class="bi bi-film"></i> Tutoriais em Vídeo</h2>
                    <p class="mb-4">Aprenda a usar o sistema com nossos tutoriais passo a passo</p>
                    
                    <div class="row">
                        <?php foreach ($tutoriais as $tutorial): ?>
                        <div class="col-md-4">
                            <div class="card video-card">
                                <div class="ratio ratio-16x9">
                                    <iframe src="<?= htmlspecialchars($tutorial['url_video']) ?>" 
                                            title="<?= htmlspecialchars($tutorial['titulo']) ?>" 
                                            allowfullscreen></iframe>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($tutorial['titulo']) ?></h5>
                                    <p class="card-text text-muted small">
                                        <i class="bi bi-clock"></i> <?= htmlspecialchars($tutorial['duracao']) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção Contato -->
        <div class="help-section" id="contato">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="mb-4"><i class="bi bi-headset"></i> Fale Conosco</h2>
                    <p>Nossa equipe está pronta para te ajudar com qualquer dúvida ou problema.</p>
                    
                    <div class="row">
                        <?php foreach ($contatos as $contato): ?>
                        <div class="col-12 mb-3">
                            <div class="contact-card">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-<?= htmlspecialchars($contato['icone']) ?> fs-3 me-3"></i>
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars(ucfirst($contato['tipo'])) ?></h5>
                                        <p class="mb-1">
                                            <a href="<?= $contato['tipo'] === 'whatsapp' ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $contato['valor']) : ($contato['tipo'] === 'email' ? 'mailto:' . $contato['valor'] : 'tel:' . preg_replace('/[^0-9]/', '', $contato['valor'])) ?>">
                                                <?= htmlspecialchars($contato['valor']) ?>
                                            </a>
                                        </p>
                                        <?php if (!empty($contato['horario_funcionamento'])): ?>
                                        <p class="small text-muted mb-0">
                                            <i class="bi bi-clock"></i> <?= htmlspecialchars($contato['horario_funcionamento']) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h2 class="mb-4"><i class="bi bi-chat-square-text"></i> Formulário de Contato</h2>
                    <form>
                        <div class="mb-3">
                            <label for="nome" class="form-label">Seu Nome</label>
                            <input type="text" class="form-control" id="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Seu E-mail</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="assunto" class="form-label">Assunto</label>
                            <select class="form-select" id="assunto" required>
                                <option value="">Selecione...</option>
                                <option>Dúvida</option>
                                <option>Problema técnico</option>
                                <option>Sugestão</option>
                                <option>Outro</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="mensagem" class="form-label">Mensagem</label>
                            <textarea class="form-control" id="mensagem" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar Mensagem</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Seção Redes Sociais -->
        <div class="help-section" id="redes-sociais">
            <div class="row text-center">
                <div class="col-12">
                    <h2 class="mb-4"><i class="bi bi-people"></i> Siga-nos nas Redes Sociais</h2>
                    <p class="mb-4">Acompanhe nossas novidades e atualizações</p>
                    
                    <div class="d-flex justify-content-center flex-wrap">
                        <?php foreach ($redes_sociais as $rede): ?>
                        <a href="<?= htmlspecialchars($rede['url']) ?>" target="_blank" class="text-decoration-none">
                            <i class="bi bi-<?= htmlspecialchars($rede['icone']) ?> social-icon"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtro de busca para FAQs
        document.querySelector('.search-box input').addEventListener('input', function(e) {
            const termo = e.target.value.toLowerCase();
            
            document.querySelectorAll('.accordion-button').forEach(button => {
                const pergunta = button.textContent.toLowerCase();
                const item = button.closest('.accordion-item');
                
                if (pergunta.includes(termo)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>