<?php
include_once 'conexao_pdo.php'; // Inclua sua conexão com o banco de dados
session_start(); // Inicia a sessão
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="assets/site/favicon.png" type="image/x-icon">
    <title>Carregando...</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
            text-align: center; /* Adicionando alinhamento central para o texto */
        }
        .spinner {
            border: 8px solid #f3f3f3; /* Cor do fundo */
            border-top: 8px solid #3498db; /* Cor do spinner */
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto; /* Garantindo que o spinner esteja centralizado */
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            margin-top: 20px;
            font-size: 18px;
            color: #333;
        }
    </style>
</head>
<body>
    <div>
        <div class="spinner"></div>
        <div class="loading-text">Carregando, por favor aguarde...</div>
    </div>
    <script>
    setTimeout(function() {
        window.location.href = 'index.php'; // Substitua pelo seu arquivo de sucesso
    }, 2000); // Tempo de espera em milissegundos (2 segundos)
    </script>
</body>
</html>
